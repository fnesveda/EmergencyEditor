<?php
	/**
	 * This file contains the definition of the rvsn class used for managing the version control system of the application.
	 */
	/** */
	// include the filesystem handler class
	require_once("scripts/php/fs.php");
	
	// include the class for comparing files
	require_once("scripts/php/diff.php");

	/**
	* Class used for managing the version control system
	*/
	class rvsn {
		/**
		 * Object to handle filesystem actions with the user's project.
		 * @var object
		 */
		protected $fs = null;
		
		/**
		 * Root folder of the version control system data storage
		 * @var string
		 */
		protected $vcRootFolder = "";
		
		// constants defining the position of different fields in the item array
		const VCITEM_NAME_POS = 0;
		const VCITEM_FILEID_POS = 1;
		const VCITEM_TYPE_POS = 2;
		const VCITEM_VCID_POS = 3;
		const VCITEM_MD5_POS = 4;
		
		// constants defining the position of different fields in the commit array
		const VCCOMMIT_ID_POS = 0;
		const VCCOMMIT_TIME_POS = 1;
		const VCCOMMIT_TITLE_POS = 2;
		const VCCOMMIT_COMMENT_POS = 3;
		const VCCOMMIT_PREV_POS = 4;
		
		// constants defining the types of items in the version control system
		const VC_TYPE_FOLDER = 0;
		const VC_TYPE_FILE = 1;
		
		/**
		 * Compares two version control items by name and type
		 *
		 * @param $a First version control item to compare
		 * @param $b Second version control item to compare
		 * @return int Result of the comparison: -1 if $a < $b, 0 if $a == $b and 1 if $a > $b
		 */
		protected static function compareObjects($a, $b) {
			$cmp = strcmp($a[self::VCITEM_NAME_POS], $b[self::VCITEM_NAME_POS]);
			if ($cmp == 0) {
				// let's hope the interpreter optimizes this
				$ta = $a[self::VCITEM_TYPE_POS];
				$tb = $b[self::VCITEM_TYPE_POS];
				$cmp = ($ta == $tb) ? 0 : (($ta < $tb) ? -1 : 1);
			}
			return $cmp;
		}
		
		/**
		 * Compares two version control items by id and type
		 *
		 * @param $a First version control item to compare
		 * @param $b Second version control item to compare
		 * @return int Result of the comparison: -1 if $a < $b, 0 if $a == $b and 1 if $a > $b
		 */
		protected static function compareObjectsByFileID($a, $b) {
			$cmp = strcmp($a[self::VCITEM_FILEID_POS], $b[self::VCITEM_FILEID_POS]);
			if ($cmp == 0) {
				// let's hope the interpreter optimizes this
				$ta = $a[self::VCITEM_TYPE_POS];
				$tb = $b[self::VCITEM_TYPE_POS];
				$cmp = ($ta == $tb) ? 0 : (($ta < $tb) ? -1 : 1);
			}
			return $cmp;
		}
		
		/**
		 * Creates instances of the class.
		 * This constructor creates the filesystem handler and initializes the version control data structures.
		 */
		public function __construct() {
			$this->fs = new fs();
			// read the config of the IDE and get the version control root folder
			$config = json_decode(file_get_contents("config/config.json", true), true);
			if (isset($config['version-control-folder']) && $config['version-control-folder'] != "") {
				$this->vcRootFolder = $config['version-control-folder'];
			}
			else {
				throw new Exception('Cannot initialize version control');
			}
			
			$vcRootFolderPath = $this->fs->path($this->vcRootFolder);

			// if the root folder doesn't exist it means the version control system was never initialized before
			if (!file_exists($vcRootFolderPath)) {
				// create all the necessary items for an empty version control system
				mkdir($vcRootFolderPath);
				file_put_contents($vcRootFolderPath.DIRECTORY_SEPARATOR."commits.json", "[]", LOCK_EX);
				mkdir($vcRootFolderPath."/objects");
				$this->versionControlInfo = array('maxVCID' => -1);
				$this->createNewVCObject("", self::VC_TYPE_FOLDER);
				$this->saveVersionControlInfo();
			}
			else {
				$this->getVersionControlInfo();
			}
			
			if (!is_dir($vcRootFolderPath)) {
				throw new Exception('Cannot initialize version control');
			}
		}
		
		/**
		 * Loads information about the version control system.
		 * This method loads the information about the current state of the version control system from the info.json file.
		 */
		protected function getVersionControlInfo() {
			$vcInfoPath = $this->fs->path($this->vcRootFolder."/info.json");
			$this->versionControlInfo = json_decode(file_get_contents($vcInfoPath, true), true);
		}
		
		/**
		 * Saves information about the version control system.
		 * This method soves the information about the current state of the version control system to the info.json file.
		 */
		protected function saveVersionControlInfo() {
			$vcInfoPath = $this->fs->path($this->vcRootFolder."/info.json");
			$r = file_put_contents($vcInfoPath, json_encode($this->versionControlInfo), LOCK_EX);
			if ($r === FALSE) {
				throw new Exception('Cannot save version control info.');
			}
		}
		
		/**
		 * Loads the list of the commits saved in the version control system.
		 * This method loads the list of the commits saved in the version control system from the commits.json file.
		 * @return array Array of commits saved in the version control system
		 */
		public function getCommits() {
			$commitsFile = $this->fs->path($this->vcRootFolder."/commits.json");
			return json_decode(file_get_contents($commitsFile, true), true);
		}
		
		/**
		 * Loads information about one commit.
		 * This method loads the information about one of the commits saved in the version control system from the commits.json file.
		 * @param $commitID The ID of the commit we want to get information about.
		 * @return array|null Information about the specified commit or null, when that commit doesn't exist.
		 */
		public function getCommitInfo($commitID) {
			$commits = $this->getCommits();
			$i = 0;
			$commitCount = count($commits);
			
			while($i < $commitCount) {
				if ($commits[$i][self::VCCOMMIT_ID_POS] == $commitID) {
					return $commits[$i];
				}
				$i++;
			}
			return null;
		}
		
		/**
		 * Returns the list of commits made after a specific one.
		 * This method loads the list of the commits made after a specific one.
		 * @param $commitID The ID of the specified commit.
		 * @return array Array of the commits made after the specified one.
		 */
		public function getCommitsAfter($commitID) {
			$commits = $this->getCommits();
			
			if ($commitID == "initial") {
				return $commits;
			}
			
			$i = 0;
			$commitCount = count($commits);
			
			while($i < $commitCount) {
				if ($commits[$i++][self::VCCOMMIT_ID_POS] == $commitID) {
					break;
				}
			}
			
			return array_splice($commits, $i);
		}
		
		/**
		 * Save the list of the commits made in the version control system.
		 * This method saves the list of the commits made in the version control system to the commits.json file.
		 * @param $commits Array of the commits to save
		 */
		protected function saveCommits($commits) {
			$commitsFilePath = $this->fs->path($this->vcRootFolder."/commits.json");
			file_put_contents($commitsFilePath, json_encode($commits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
		}
		
		/**
		 * Checks if an object was a file or a folder at a specific commit
		 * This method checks, if a filesystem object was a file or a folder in the state of the project in a specific commit.
		 * @param $id ID of the object.
		 * @param $commitID ID of the specific commit.
		 * @return boolean|null Returns true, if the object was a file, false, if the object was a folder, or null, if it didn't exist.
		 */
		public function isObjectFileAtCommit($id, $commitID) {
			if ($id == '' || $id == '/') {
				return false;
			}
			$parentID = dirname($id);
			if ($parentID == '.') {
				$parentID = "/";
			}
			if ($commitID == "current") {
				if ($this->fs->exists($id)) {
					return is_file($this->fs->path($id));
				}
				else {
					return null;
				}
			}
			else {
				$objs = $this->getObjectsAtCommit($parentID, "", $commitID);
				$i = 0;
				$objsLength = count($objs);
				while($i < $objsLength) {
					if ($objs[$i][self::VCITEM_FILEID_POS] == $id) {
						return ($objs[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FILE);
					}
					$i++;
				}
			}
			return null;
		}
		
		/**
		 * Returns a list of all the version control objects which ever were children of a specific version control object.
		 * @param $VCID ID of the specific version control object
		 * @return array The requested list version control items.
		 */
		protected function getAllObjects($VCID) {
			$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID);
			if (!file_exists($vcFolderPath . DIRECTORY_SEPARATOR . 'objects.json')) {
				return [];
			}
			return json_decode(file_get_contents($vcFolderPath . DIRECTORY_SEPARATOR . 'objects.json', true), true);
		}
		
		/**
		 * Saves the list all the version control objects which ever were children of a specific version control object.
		 * @param $objects List of the version control objects.
		 * @param $VCID ID of the specific version control object
		 */
		protected function saveAllObjects($objects, $VCID) {
			$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID);
			file_put_contents($vcFolderPath . DIRECTORY_SEPARATOR . 'objects.json', json_encode($objects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
		}
		
		/**
		 * Saves the list of the version control objects which are currently children of a specific version control object.
		 * @param $objects List of the version control objects.
		 * @param $VCID ID of the specific version control object
		 */
		protected function saveLatestObjects($objects, $VCID) {
			$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID);
			file_put_contents($vcFolderPath . DIRECTORY_SEPARATOR . 'latest.json', json_encode($objects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
		}
		
		/**
		 * Returns the content listing of a folder at a specified commit.
		 * This method lists the contents of a specific folder of the user's project at a specific commit and returns the list in a format suitable for JSTree.
		 * @param $id ID of the folder to get listing of.
		 * @param $commitID ID of the commit at which we want the information
		 * @param $with_root Special parameter for JSTree specifying if we want the root folder of the user's project to be included in the listings as well.
		 * @return array An array containing the listings of the specified folder.
		 */
		public function getNodeAtCommit($id, $commitID, $with_root = false) {
			if ($id == '#') {
				$id = '/';
			}
			if ($commitID == "current") {
				return $this->fs->lst($id, $with_root);
			}
			
			$lst = $this->getObjectsAtCommit($id, "", $commitID);
			// list the contents of the folder in a format suited for jstree
			$res = array();
			foreach($lst as $item) {
				$name = $item[self::VCITEM_NAME_POS];
				$parentPath = $id . '/';
				if ($id == '/') {
					$parentPath = '';
				}
				if ($item[self::VCITEM_TYPE_POS] == self::VC_TYPE_FOLDER) {
					$res[] = array('text' => $name, 'children' => true,  'id' => $parentPath . $name, 'type' => 'folder', 'icon' => 'folder');
				}
				else {
					$res[] = array('text' => $name, 'children' => false, 'id' => $parentPath . $name, 'type' => 'file', 'icon' => 'file file-'.substr($name, strrpos($name,'.') + 1));
				}
			}
			// if we want to show the root folder, return it as well if we're asking for it now
			if($with_root && $id == '/') {
				$res = array(array('text' => '/', 'children' => $res, 'id' => '/', 'icon'=>'folder', 'state' => array('opened' => true, 'disabled' => true)));
			}
			return $res;
		}
		
		/**
		 * Returns the information about a file at a specified commit.
		 * @param $id ID of the file to get information about.
		 * @param $commitID ID of the commit at which we want the information.
		 * @return array An array containing the information about the file.
		 */
		public function getFileInfoAtCommit($id, $commitID) {
			if ($commitID == "current") {
				return $this->fs->info($id);
			}
			else {
				$isFile = $this->isObjectFileAtCommit($id, $commitID);
				if ($isFile === null) {
					return null;
				}
				if (!$isFile) {
					return array('type'=>'folder', 'mimetype' => '', 'content' => '', 'ext' => '', 'size' => '', 'binary' => '');
				}
				
				$VCID = $this->getVCIDForID($id, $commitID);
				$latestFilePath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
				$finalFilePath = $latestFilePath;
				if ($commitID != "latest") {
					$tmpFile = $this->createTmpFileAtCommit($id, $VCID, $commitID);
					$finalFilePath = $tmpFile;
				}
				
				// get file info
				$ext = strpos($id, '.') !== FALSE ? substr($id, strrpos($id, '.') + 1) : '';
				$mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $finalFilePath);
				$size = filesize($finalFilePath);
				
				// check if the file is binary
				$binary = is_file_binary($finalFilePath);
				$contents = array('type' => 'file', 'mimetype' => $mimetype, 'ext' => $ext, 'content' => '', 'size' => $size, 'binary' => $binary);

				if ($commitID != "latest") {
					unlink($tmpFile);
				}
				return $contents;
			}
		}
		
		/**
		 * Returns the contents of a file at a specified commit.
		 * @param $id ID of the file to get contents of.
		 * @param $commitID ID of the commit at which we want the information.
		 * @param $base64 Specifies, if we want the contents of the file to be encoded in base64.
		 * @return array An array containing the information about the file and its contents.
		 */
		public function getFileContentsAtCommit($id, $commitID, $base64 = false) {
			if ($commitID == "current") {
				if (!$this->fs->exists($id)) {
					return null;
				}
				return $this->fs->data($id, $base64);
			}
			else {
				$isFile = $this->isObjectFileAtCommit($id, $commitID);
				if ($isFile === null) {
					return null;
				}
				if (!$isFile) {
					return array('type'=>'folder', 'mimetype' => '', 'content' => '', 'ext' => '', 'size' => '', 'binary' => '');
				}
				$VCID = $this->getVCIDForID($id, $commitID);
				$latestFilePath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
				$finalFilePath = $latestFilePath;
				if ($commitID != "latest") {
					$tmpFile = $this->createTmpFileAtCommit($id, $VCID, $commitID);
					$finalFilePath = $tmpFile;
				}
				
				// get file info
				$ext = strpos($id, '.') !== FALSE ? substr($id, strrpos($id, '.') + 1) : '';
				$mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $finalFilePath);
				$size = filesize($finalFilePath);
				
				// check if the file is binary
				$binary = is_file_binary($finalFilePath);
				if ($base64 || $binary) {
					$contents = array('type' => 'file', 'mimetype' => $mimetype, 'ext' => $ext, 'content' => base64_encode(file_get_contents($finalFilePath)), 'size' => $size, 'binary' => $binary,  'base64' => true);
				}
				else {
					$contents = array('type' => 'file', 'mimetype' => $mimetype, 'ext' => $ext, 'content' => file_get_contents($finalFilePath), 'size' => $size, 'binary' => $binary,  'base64' => false);
				}

				if ($commitID != "latest") {
					unlink($tmpFile);
				}
				return $contents;
			}
		}
		
		/**
		 * Starts a download of a file at a specific commit.
		 * @param $id ID of the file to download.
		 * @param $commitID ID of the commit at which we want the file to download.
		 */
		public function downloadFileAtCommit($id, $commitID) {
			if ($commitID == "current") {
				download_file($this->fs->path($id));
			}
			else {
				$VCID = $this->getVCIDForID($id, $commitID);
				$latestFilePath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
				if (!file_exists($latestFilePath)) {
					return false;
				}
				if ($commitID == "latest") {
					download_file($latestFilePath);
				}
				else {
					$tmpFile = $this->createTmpFileAtCommit($id, $VCID, $commitID);
					// download file, don't enable download resuming and delete the file after download
					$name = basename($id);
					download_file($tmpFile, $name, false, true);
				}
			}
		}
		
		/**
		 * Generates a list of version control items corresponding to files in a specified folder in the current state.
		 * @param $id ID of the folder which to list.
		 * @return array Array of version control items corresponding to files in the specified folder.
		 */
		protected function generateObjects($id) {
			$lst = $this->fs->lst($id);
			
			$items = [];
			foreach($lst as $item) {
				$newItem = array();
				$newItem[self::VCITEM_NAME_POS] = $item['text'];
				$newItem[self::VCITEM_FILEID_POS]   = trim($id.'/'.$item['text'], '/');
				$newItem[self::VCITEM_TYPE_POS] = ($item['type'] == 'folder') ? self::VC_TYPE_FOLDER : self::VC_TYPE_FILE;
				$newItem[self::VCITEM_VCID_POS] = 'xx'; // nonsensical ID, because we don't have the VCIDs for new objects yet before they're committed
				$newItem[self::VCITEM_MD5_POS]  = $item['md5'];
				$items[] = $newItem;
			}
			return $items;
		}
		
		/**
		 * Returns a list of version control items corresponding to files in a specified folder in the specified commit.
		 * @param $id ID of the folder which to list.
		 * @param $VCID ID of the version control object corresponding to the listed folder
		 * @param $commitID ID of the commit at which to list the folder.
		 * @param $deep Specifies, if we want to list even the children of the specified folder.
		 * @param $sort Specifies, if we want the result to be sorted by the IDs of the objects.
		 * @return array Array of version control items corresponding in the specified folder.
		 */
		protected function getObjectsAtCommit($id, $VCID, $commitID, $deep = false, $sort = false) {
			// check if we're not asking for objects at a special commit, which would mean we don't have to reconstruct the objects file complicatedly
			$objs = [];
			if ($commitID == "none") {
				// redundant, just for clarity
				$objs = [];
			}
			else if ($commitID == "current") {
				$objs = $this->generateObjects($id);
			}
			else {
				if ($VCID == "") {
					$VCID = $this->getVCIDForID($id, $commitID);
				}
				if ($commitID == "latest") {
					$latestObjectsPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest.json");
					$objs = json_decode(file_get_contents($latestObjectsPath, true), true);
				}
				else {
					// reconstruct the objects file from diffs between commits
					$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID);
					$commitsToApply = $this->getCommitsAfter($commitID);
					$tmpObjsFile = tempnam(sys_get_temp_dir(), "rvsn_objs_");
					copy($vcFolderPath . DIRECTORY_SEPARATOR . "latest.json", $tmpObjsFile);
					for ($i = count($commitsToApply) - 1; $i >= 0; $i--) {
						if (file_exists($vcFolderPath . DIRECTORY_SEPARATOR . $commitsToApply[$i][self::VCCOMMIT_ID_POS].".diff")) {
							$this->applyDiff($tmpObjsFile, $vcFolderPath . DIRECTORY_SEPARATOR . $commitsToApply[$i][self::VCCOMMIT_ID_POS].".diff");
						}
					}
					$objs = json_decode(file_get_contents($tmpObjsFile, true), true);
					unlink($tmpObjsFile);
				}
			}
			// if we want all items even in subfolders, we need to recursively traverse them and add their items to the list
			if ($deep) {
				$i = 0;
				$objsLength = count($objs);
				while($i < $objsLength) {
					if ($objs[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FOLDER) {
						$innerFolderItems = $this->getObjectsAtCommit($objs[$i][self::VCITEM_FILEID_POS], $objs[$i][self::VCITEM_VCID_POS], $commitID, true);
						$objs = array_merge($objs, $innerFolderItems);
					}
					$i++;
				}
			}
			if ($sort) { // this only makes sense if we added items from subfolders, otherwise the items are sorted
				usort($objs, array("rvsn", "compareObjectsByFileID"));
			}
			return $objs;
		}

		/**
		 * Returns the version control ID of a file or folder at a specified commit.
		 * @param $id ID of the specified file or folder.
		 * @param $commitID ID of the commit from which we want the information.
		 * @return string The requested version control ID.
		 */
		protected function getVCIDForID($id, $commitID = "") {
			if ($commitID == "current") {
				return "";
			}
			$path = rtrim($id, DIRECTORY_SEPARATOR);
			$pathItems = explode(DIRECTORY_SEPARATOR, $path);
			
			// traverse the version control items down to the desired item
			$VCID = "0";
			$pathItemsCount = count($pathItems);
			for ($i = 0; $i < $pathItemsCount; $i++) {
				if ($pathItems[$i] == "") {
					continue;
				}
				$found = false;
				if ($commitID == "") {
					$objects = $this->getAllObjects($VCID);
				}
				else {
					$objects = $this->getObjectsAtCommit("", $VCID, $commitID);
				}
				foreach($objects as $item) {
					if ($item[self::VCITEM_NAME_POS] == $pathItems[$i]) {
						$found = true;
						$VCID = $item[self::VCITEM_VCID_POS];
						break;
					}
				}
				if (!$found) {
					return null;
				}
			}
			return $VCID;
		}
		
		/** 
		 * Applies a diff script to a file.
		 * Takes a file and applies to it all actions specified in a diff script.
		 * @param $targetFilePath Path to the target file.
		 * @param $diffFilePath Path to the diff script.
		 */
		protected function applyDiff($targetFilePath, $diffFilePath) {
			/* calling system commands is not allowed on ms.mff.cuni.cz, so we have to do diffs in PHP
			// with native ED and DIFF commands this would be much faster, though
			// apply the edit script through ed (not ideal, but much faster than a pure PHP solution since it's native)
			passthru('ed -s ' . $targetFilePath . ' < ' . $diffFilePath);
			// append a trailing newline since ed deletes them (better to have it everywhere than nowhere)
			// passthru('echo >> ' . $targetFilePath);
			*/
			$old  = file_get_contents($targetFilePath);
			$diff = file_get_contents($diffFilePath);
			$new = diff::applyDiffScript($old, $diff);
			file_put_contents($targetFilePath, $new);
		}
		
		/** 
		 * Creates a diff script between two files.
		 * Compares two files and creates a diff script between these files.
		 * @param $olderFilePath Path to the older compared file.
		 * @param $newerFilePath Path to the newer compared file.
		 * @param $diffFilePath Path to the resulting diff script.
		 */
		protected function createDiff($olderFilePath, $newerFilePath, $diffFilePath) {
			/* calling system commands is not allowed on ms.mff.cuni.cz, so we have to do diffs in PHP
			// with native ED and DIFF commands this would be much faster, though
			// generate an edit script by diff (not ideal in terms of size, but much faster than a pure PHP solution since it's native)
			passthru('diff -e ' . $newerFilePath .' ' . $olderFilePath . ' > ' . $diffFilePath);
			// append this so ed can use the script
			passthru('printf "w\nq\n" >> ' . $diffFilePath);
			*/
			$older = file_get_contents($olderFilePath);
			$newer = file_get_contents($newerFilePath);
			$diff = diff::createDiffScript($newer, $older);
			file_put_contents($diffFilePath, $diff);
		}
		
		/**
		 * Creates a temporary file holding a version of the desired file at a specified commit
		 * @param $id ID of the desired file.
		 * @param $VCID ID of the version control object corresponding to the desired file.
		 * @param $commitID ID of the specified commit.
		 * @return Path to the created temporary file.
		 */
		protected function createTmpFileAtCommit($id, $VCID, $commitID) {
			$tmpFilePath = tempnam(sys_get_temp_dir(), "rvsn_file_");
			if ($commitID == "current") {
				copy($this->fs->path($id), $tmpFilePath);
			}
			else {
				if ($VCID == "") {
					$VCID = $this->getVCIDForID($id, $commitID);
				}
				if ($commitID == "latest") {
					$latestFilePath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
					copy($this->fs->path($id), $tmpFilePath);
				}
				else {
					$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID);

					$commitsToApply = $this->getCommitsAfter($commitID);
					$commitsToApplyLength = count($commitsToApply);

					$firstFullFilePosition = -1;
					$i = 0;
					while ($i < $commitsToApplyLength) {
						if (file_exists($vcFolderPath . DIRECTORY_SEPARATOR . $commitsToApply[$i][self::VCCOMMIT_ID_POS])) {
							$firstFullFilePosition = $i;
							break;
						}
						$i++;
					}
					
					if ($firstFullFilePosition >= 0) {
						copy($vcFolderPath . DIRECTORY_SEPARATOR . $commitsToApply[$firstFullFilePosition][self::VCCOMMIT_ID_POS], $tmpFilePath);
					}
					else {
						copy($vcFolderPath . DIRECTORY_SEPARATOR . "latest", $tmpFilePath);
						$firstFullFilePosition = $commitsToApplyLength;
					}
					
					for ($i = $firstFullFilePosition - 1; $i >= 0; $i--) {
						$this->applyDiff($tmpFilePath, $vcFolderPath . DIRECTORY_SEPARATOR . $commitsToApply[$i][self::VCCOMMIT_ID_POS].".diff");
					}
				}
			}
			return $tmpFilePath;
		}
		
		/**
		 * Reverts the whole user's project to a specified commit.
		 * @param $commitID ID of the commit to which to revert.
		 */
		public function revertAllToCommit($commitID) {
			$this->revertFolderToCommit('/', $commitID);
		}
		
		/**
		 * Reverts a specific item in the user's project to a specified commit.
		 * @param $itemID ID of the item which to revert.
		 * @param $commitID ID of the commit to which to revert.
		 */
		public function revertItemToCommit($itemID, $commitID) {
			$dir = $this->fs->path($itemID);
			if (is_file($dir)) {
				$this->revertFileToCommit($itemID, $commitID);
			}
			else {
				$this->revertFolderToCommit($itemID, $commitID);
			}
		}
		
		/**
		 * Reverts a specific file in the user's project to a specified commit.
		 * @param $id ID of the file which to revert.
		 * @param $VCID ID of the version control object corresponding file which to revert.
		 * @param $commitID ID of the commit to which to revert.
		 */
		protected function revertFileToCommit($id, $VCID, $commitID) {
			if ($commitID == "current") {
				return true;
				// no reason to do anything
			}
			else {
				if ($VCID == "") {
					$VCID = $this->getVCID($id, $commitID);
				}
				if ($commitID == "latest") {
					$latestFilePath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
					copy($latestFilePath, $this->fs->path($id));
				}
				else {
					$tmpFile = $this->createTmpFileAtCommit($id, $VCID, $commitID);
					copy($tmpFile, $this->fs->path($id));
					unlink($tmpFile);
				}
			}
		}
		
		/**
		 * Reverts a specific folder in the user's project to a specified commit.
		 * @param $itemID ID of the folder which to revert.
		 * @param $commitID ID of the commit to which to revert.
		 */
		protected function revertFolderToCommit($id, $commitID) {
			$VCID = $this->getVCIDForID($id, $commitID);
			$this->revertFolderToCommitRecursive($id, $VCID, $commitID);
		}
		
		/**
		 * Recursively reverts a specific folder in the user's project and all its contents to a specified commit.
		 * @param $id ID of the folder which to revert.
		 * @param $VCID ID of the version control object corresponding folder which to revert.
		 * @param $commitID ID of the commit to which to revert.
		 */
		protected function revertFolderToCommitRecursive($id, $VCID, $commitID) {
			if ($commitID == "current") {
				return true;
			}
			
			$newerItems = $this->getObjectsAtCommit($id, $VCID, "current");
			$olderItems = $this->getObjectsAtCommit($id, $VCID, $commitID);
			
			$i = 0;
			$j = 0;
			
			$newerLength = count($newerItems);
			$olderLength = count($olderItems);
			
			// ugly way to go through both lists of items in alphabetical order and check for matching objects
			while ($i < $olderLength || $j < $newerLength) {
				// ugly way to check if there's an item which is not in the other list
				if ($i < $olderLength && $j < $newerLength) {
					$cmp = self::compareObjects($olderItems[$i], $newerItems[$j]);
				}
				else if ($i < $olderLength) {
					$cmp = -1;
				}
				else {
					$cmp = 1;
				}
				
				if ($cmp < 0) { // some object was deleted since the commit
					if ($olderItems[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FILE) {
						$this->revertFileToCommit($olderItems[$i][self::VCITEM_FILEID_POS], $olderItems[$i][self::VCITEM_VCID_POS], $commitID);
					}
					else {
						$itemPath = $this->fs->path($olderItems[$i][self::VCITEM_FILEID_POS]);
						mkdir($itemPath); 
						$this->revertFolderToCommitRecursive($olderItems[$i][self::VCITEM_FILEID_POS], $olderItems[$i][self::VCITEM_VCID_POS], $commitID);
					}
					$i++;
				}
				else if ($cmp == 0) { // there is an object present both in the commit and in the current state
					$newerItems[$j][self::VCITEM_VCID_POS] = $olderItems[$i][self::VCITEM_VCID_POS];
					// check if the object is a file or a folder
					if ($olderItems[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FILE) {
						// check if md5 sums match
						if ($olderItems[$i][self::VCITEM_MD5_POS] != $newerItems[$j][self::VCITEM_MD5_POS]) {
							// if the file was changed, commit those changes
							$this->revertFileToCommit($olderItems[$i][self::VCITEM_FILEID_POS], $olderItems[$i][self::VCITEM_VCID_POS], $commitID);
						}
					}
					else {
						$this->revertFolderToCommitRecursive($olderItems[$i][self::VCITEM_FILEID_POS], $olderItems[$i][self::VCITEM_VCID_POS], $commitID);
					}
					$i++;
					$j++;
				}
				else { // some object was added since the commit
					$itemPath = $this->fs->path($newerItems[$j][self::VCITEM_FILEID_POS]);
					unlink($itemPath); 
					$j++;
				}
			}
		}
		
		/**
		 * Saves the changes in the user's project to a new commit.
		 * @param $title Title of the commit.
		 * @param $comment Comment of the commit.
		 */
		public function commitChanges($title, $comment) {
			$time = time();
			$commitID = substr(str_shuffle(MD5($time)), 0, 10);
			
			$commits = $this->getCommits();
			$commitsCount = count($commits);
			
			$prevCommitID = "none";
			if ($commitsCount > 0) {
				$prevCommitID = $commits[$commitsCount - 1][self::VCCOMMIT_ID_POS];
			}
			
			$commit = [];
			$commit[self::VCCOMMIT_ID_POS] = $commitID;
			$commit[self::VCCOMMIT_TIME_POS] = $time;
			$commit[self::VCCOMMIT_TITLE_POS] = $title;
			$commit[self::VCCOMMIT_COMMENT_POS] = $comment;
			$commit[self::VCCOMMIT_PREV_POS] = $prevCommitID;
			$commits[] = $commit;
			
			$this->commitChangesRecursive($commitID, "/", "0");
			
			$this->saveCommits($commits);
			$this->saveVersionControlInfo();
		}
		
		/**
		 * Recursively saves the changes in an item of the user's project to a new commit.
		 * @param $commitID ID of the new commit.
		 * @param $id ID of the item to save.
		 * @param $id ID of the version control object corresponding to the item to save.
		 */
		protected function commitChangesRecursive($commitID, $id, $VCID) {
			$id = rtrim($id, '/');
			$dir = $this->fs->path($id);
			if (is_file($dir)) {
				$this->commitFileChanges($commitID, $id, $VCID);
			}
			else {
				$this->commitFolderChanges($commitID, $id, $VCID);
			}
		}
		
		/**
		 * Saves the changes in a folder of the user's project to a new commit.
		 * @param $commitID ID of the new commit.
		 * @param $id ID of the folder to save.
		 * @param $id ID of the version control object corresponding to the folder to save.
		 */
		protected function commitFolderChanges($commitID, $id, $VCID) {
			// format: array of arrays like ["name", "type", "vcFolder", "md5"]
			$allItems   = $this->getAllObjects($VCID);
			$newerItems = $this->getObjectsAtCommit($id, $VCID, "current");
			$olderItems = $this->getObjectsAtCommit($id, $VCID, "latest");

			/*
			// this is not needed, because we're keeping the object lists ordered at all times
			usort($newerItems, array("rvsn", "compareObjects"));
			usort($olderItems, array("rvsn", "compareObjects"));
			usort($allItems,   array("rvsn", "compareObjects"));
			*/
			
			$i = 0;
			$j = 0;
			$k = 0;
			
			$newerLength = count($newerItems);
			$olderLength = count($olderItems);
			$allLength   = count($allItems);
			
			$newItems = [];
			
			$folderChanged = false;
			
			// ugly way to go through both lists of items in alphabetical order and check for matching objects
			while ($i < $olderLength || $j < $newerLength) {
				// ugly way to check if there's an item which is not in the other list
				if ($i < $olderLength && $j < $newerLength) {
					$cmp = self::compareObjects($olderItems[$i], $newerItems[$j]);
				}
				else if ($i < $olderLength) {
					$cmp = -1;
				}
				else {
					$cmp = 1;
				}
				
				if ($cmp < 0) { // some object was deleted since the last commit
					// for now there's nothing to do with this
					// $deletedItem = $olderItems[$i];
					$folderChanged = true;
					$i++;
				}
				else if ($cmp == 0) { // there is an object present both in the latest commit and in the current state
					$newerItems[$j][self::VCITEM_VCID_POS] = $olderItems[$i][self::VCITEM_VCID_POS];
					// check if the object is a file or a folder
					if ($olderItems[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FILE) {
						// check if md5 sums match
						if ($olderItems[$i][self::VCITEM_MD5_POS] != $newerItems[$j][self::VCITEM_MD5_POS]) {
							// if the file was changed, commit those changes
							$folderChanged = true;
							$this->commitFileChanges($commitID, $id.'/'.$newerItems[$j][self::VCITEM_NAME_POS], $newerItems[$j][self::VCITEM_VCID_POS]);
							
							// update MD5 sum in the list of all objects
							while ($k < $allLength && ($cmp2 = self::compareObjects($newerItems[$j], $allItems[$k])) < 0) {
								$k++;
							}
							if ($k < $allLength && $cmp2 == 0) { // this condition should be always true, but let's check just in case
								$allItems[$k][self::VCITEM_MD5_POS] = $newerItems[$j][self::VCITEM_MD5_POS];
							}

						}
					}
					else {
						$this->commitChangesRecursive($commitID, $id.'/'.$newerItems[$j][self::VCITEM_NAME_POS], $newerItems[$j][self::VCITEM_VCID_POS]);
					}
					$i++;
					$j++;
				}
				else { // some object was added since the last commit
					$folderChanged = true;
					$cmp2 = -1;
					while ($k < $allLength && ($cmp2 = self::compareObjects($newerItems[$j], $allItems[$k])) < 0) {
						$k++;
					}
					if ($cmp2 == 0 && $k < $allLength) { // there already was an object with the same name of the same type earlier, but then was deleted
						$newerItems[$j][self::VCITEM_VCID_POS] = $allItems[$k][self::VCITEM_VCID_POS];
						$allItems[$k][self::VCITEM_MD5_POS] = $newerItems[$j][self::VCITEM_MD5_POS];
						$this->commitChangesRecursive($commitID, $id.'/'.$newerItems[$j][self::VCITEM_NAME_POS], $newerItems[$j][self::VCITEM_VCID_POS]);
						$k++;
					}
					else {
						$vcID = $this->createNewVCObject($newerItems[$j][self::VCITEM_TYPE_POS]); 
						$newerItems[$j][self::VCITEM_VCID_POS] = $vcID;
						$newItems[] = $newerItems[$j];
						$this->commitChangesRecursive($commitID, $id.'/'.$newerItems[$j][self::VCITEM_NAME_POS], $newerItems[$j][self::VCITEM_VCID_POS]);
					}
					$j++;
				}
			}
			
			if ($folderChanged) {
				$tmpObjectsFile = tempnam(sys_get_temp_dir(), "rvsn_objs_");
				$latestObjectsPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest.json");
				$diffPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/".$commitID.".diff");
				
				copy($latestObjectsPath, $tmpObjectsFile);
				$this->saveLatestObjects($newerItems, $VCID);
				
				$this->createDiff($tmpObjectsFile, $latestObjectsPath, $diffPath);
				unlink($tmpObjectsFile);
			}

			// ugly hack to be able to pass this function to outside functions
			// the other way would be to pass it's name as a string, which is even uglier
			$compareObjects = function($a, $b) {
				return self::compareObjects($a, $b);
			};
			$allItems = merge_presorted_arrays($allItems, $newItems, $compareObjects);
			$this->saveAllObjects($allItems, $VCID);
		}
		
		/**
		 * Saves the changes in a file in the user's project to a new commit.
		 * @param $commitID ID of the new commit.
		 * @param $id ID of the file to save.
		 * @param $id ID of the version control object corresponding to the file to save.
		 */
		protected function commitFileChanges($commitID, $id, $VCID) {
			$currentPath = $this->fs->path($id);
			$latestPath  = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/latest");
			
			if (file_exists($latestPath)) {
				$binaryCurrent = is_file_binary($currentPath);
				$binaryLatest  = is_file_binary($latestPath);
				
				if (!$binaryCurrent && !$binaryLatest) {
					$diffPath = $this->fs->path($this->vcRootFolder."/objects/".$VCID."/".$commitID);
					$this->createDiff($latestPath, $currentPath, $diffPath . ".diff");
				}
				else {
					copy($latestPath, $diffPath);
				}
			}
			
			copy($currentPath, $latestPath);
		}
		
		/**
		 * Create a new object in the version control data storage.
		 * @param $type Type of the object to create.
		 * @return string ID of the new version control object.
		 */
		protected function createNewVCObject($type) {
			$vcFolderID = ++$this->versionControlInfo['maxVCID'];
			$vcFolderPath = $this->fs->path($this->vcRootFolder."/objects/".$vcFolderID);
			
			if (!file_exists($vcFolderPath)) { // the folder should never exist
				mkdir($vcFolderPath);
				if ($type == self::VC_TYPE_FOLDER) {
					file_put_contents($vcFolderPath.DIRECTORY_SEPARATOR."objects.json", "[]", LOCK_EX);
					file_put_contents($vcFolderPath.DIRECTORY_SEPARATOR."latest.json", "[]", LOCK_EX);
				}
			}
			return $vcFolderID;
		}
		
		/**
		 * Detects and lists the changes in a folder of the user's project between two commits.
		 * @param $olderCommit ID of the older commit used for comparison.
		 * @param $newerCommit ID of the newer commit used for comparison.
		 * @param $id ID of the folders in the user's project for which to list changes.
		 * @param $VCID ID of the version control object responding to the desired folder.
		 * @param $deep Specifies, whether to list all files and subfolders of new and deleted folders.
		 * @return array Array containing lists of new, deleted and modified items between the two commits.
		 */
		public function getChangesBetweenCommits($olderCommit, $newerCommit, $id = "/", $VCID = "0", $deep = false) {
			if ($VCID == "") {
				$VCID = $this->getVCIDForID($id, $newerCommit);
			}
			if ($VCID == "") {
				$VCID = $this->getVCIDForID($id, $olderCommit);
			}
			$changes = $this->getChangesBetweenCommitsRecursive($id, $VCID, $olderCommit, $newerCommit);
			if ($deep) { // if we want to list every new and deleted file, even in subfolders, we need to traverse them
				$newItems = $changes[0];
				$deletedItems = $changes[1];
				$modifiedItems = $changes[2];
				
				$i = 0;
				$newItemsLength = count($newItems);
				while($i < $newItemsLength) {
					if ($newItems[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FOLDER) {
						$innerFolderItems = $this->getObjectsAtCommit($newItems[$i][self::VCITEM_FILEID_POS], '', $newerCommit, true);
						$newItems = array_merge($newItems, $innerFolderItems);
					}
					$i++;
				}
				usort($newItems, array("rvsn", "compareObjectsByFileID"));

				$i = 0;
				$deletedItemsLength = count($deletedItems);
				while($i < $deletedItemsLength) {
					if ($deletedItems[$i][self::VCITEM_TYPE_POS] == self::VC_TYPE_FOLDER) {
						$innerFolderItems = $this->getObjectsAtCommit($deletedItems[$i][self::VCITEM_FILEID_POS], '', $olderCommit, true);
						$deletedItems = array_merge($deletedItems, $innerFolderItems);
					}
					$i++;
				}
				usort($deletedItems, array("rvsn", "compareObjectsByFileID"));
				
				$changes = [$newItems, $deletedItems, $modifiedItems];
			}
			return $changes;
		}

		/**
		 * Recursively detects and lists the changes in a folder of the user's project between two commits.
		 * @param $id ID of the folder in the user's project for which to list changes.
		 * @param $VCID ID of the version control object responding to the desired folder.
		 * @param $olderCommit ID of the older commit used for comparison.
		 * @param $newerCommit ID of the newer commit used for comparison.
		 * @return array Array containing lists of new, deleted and modified items between the two commits.
		 */
		protected function getChangesBetweenCommitsRecursive($id, $VCID, $olderCommit, $newerCommit) {
			$id = rtrim($id, '/');
			if ($VCID == "") {
				$VCID = $this->getVCIDForID($id, $newerCommit);
			}
			if ($VCID == "") {
				$VCID = $this->getVCIDForID($id, $olderCommit);
			}

			$folderChanges = $this->getFolderChangesBetweenCommits($id, $VCID, $olderCommit, $newerCommit);

			$newItems = $folderChanges[0];
			$deletedItems = $folderChanges[1];
			$preservedItems = $folderChanges[2];
			$modifiedItems = $folderChanges[3];
			foreach ($preservedItems as $item) {
				if ($item[self::VCITEM_TYPE_POS] == self::VC_TYPE_FOLDER) {
					$innerFolderChanges = $this->getChangesBetweenCommitsRecursive($id.'/'.$item[self::VCITEM_NAME_POS], $item[self::VCITEM_VCID_POS], $olderCommit, $newerCommit);
					$newItems = array_merge($newItems, $innerFolderChanges[0]);
					$deletedItems = array_merge($deletedItems, $innerFolderChanges[1]);
					$modifiedItems = array_merge($modifiedItems, $innerFolderChanges[2]);
				}
			}
			return [$newItems, $deletedItems, $modifiedItems];
		}

		/**
		 * Detects and lists the changes in a specific folder of the user's project between two commits.
		 * @param $id ID of the folder in the user's project for which to list changes.
		 * @param $VCID ID of the version control object responding to the desired folder.
		 * @param $olderCommit ID of the older commit used for comparison.
		 * @param $newerCommit ID of the newer commit used for comparison.
		 * @return array Array containing lists of new, deleted, preserved and modified items between the two commits.
		 */
		protected function getFolderChangesBetweenCommits($id, $VCID, $olderCommit, $newerCommit) {
			if ($VCID == '') {
				$VCID = $this->getVCIDForID($id, $newerCommit);
			}
			if ($VCID == '') {
				$VCID = $this->getVCIDForID($id, $olderCommit);
			}
			
			$newerItems = $this->getObjectsAtCommit($id, $VCID, $newerCommit);
			$olderItems = $this->getObjectsAtCommit($id, $VCID, $olderCommit);

			/*
			// this is not needed, because we're keeping the object lists ordered at all times
			usort($newerItems, array("rvsn", "compareObjects"));
			usort($olderItems, array("rvsn", "compareObjects"));
			*/ 
			
			$i = 0;
			$j = 0;
			
			$newerLength = count($newerItems);
			$olderLength = count($olderItems);
			
			$deletedItems = [];
			$newItems = [];
			$preservedItems = [];
			$modifiedItems = [];
			
			while ($i < $olderLength || $j < $newerLength) {
				// ugly way to check if there's an item which is not in the other list
				if ($i < $olderLength && $j < $newerLength) {
					$cmp = self::compareObjects($olderItems[$i], $newerItems[$j]);
				}
				else if ($i < $olderLength) {
					$cmp = -1;
				}
				else {
					$cmp = 1;
				}
				
				if ($cmp < 0) { // there's an item in the first list which is not in the second one
					$deletedItems[] = $olderItems[$i++];
				}
				else if ($cmp == 0) { // there's an item in both lists
					// fix when the older commit is "current"
					if ($olderItems[$i][self::VCITEM_VCID_POS] == "xx") {
						$olderItems[$i][self::VCITEM_VCID_POS] = $newerItems[$j][self::VCITEM_VCID_POS];
					}
					// if the items have the same MD5 field they're either folders or identical files
					if ($olderItems[$i][self::VCITEM_MD5_POS] == $newerItems[$j][self::VCITEM_MD5_POS]) {
						$preservedItems[] = $olderItems[$i++];
						$j++;
					}
					else {
						$modifiedItems[] = $olderItems[$i++];
						$j++;
					}
				}
				else { // there's an item in the second list which is not in the first one
					$newItems[] = $newerItems[$j++];
				}
			}
			
			return [$newItems, $deletedItems, $preservedItems, $modifiedItems];
		}

	}
