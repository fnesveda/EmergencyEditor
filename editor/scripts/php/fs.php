<?php
	/**
	 * This file contains the definition of the rvsn class used for managing the files and folders of the user's project.
	 */

	/** 
	 * Class for managing the files and folders of the user's project.
	 */
	class fs {
		/**
		 * Root directory of the user's project
		 * @var string
		 */
		protected $base = null;

		/**
		 * List of files and folders to which the user has forbidden access.
		 * @var array
		 */
		protected $blacklist = array();
		
		/**
		 * Returns the real path on the real filesystem from the virtual path that the user has.
		 * @param $path Virtual path to the requested filesystem item, possibly containing "." or ".." folders.
		 * @return string Real path to the desired filesystem item.
		 */
		protected function real($path) {
			// we can't call realpath() directly on the $path, because that would mean we can't get a real path of a nonexistent file/folder
			// we have to pop out the last item and get realpath of the parent directory
			$path = rtrim($path, DIRECTORY_SEPARATOR);
			$parentPath = explode(DIRECTORY_SEPARATOR, $path);
			$lastItem = array_pop($parentPath);
			$parentPath = implode(DIRECTORY_SEPARATOR, $parentPath);
			$real = realpath($parentPath);
			
			if (!file_exists($real)) {
				throw new Exception('Path '.$parentPath.' does not exist');
			}
			$real = $real.DIRECTORY_SEPARATOR.$lastItem;
			if(strpos($real, $this->base) !== 0) {
				throw new Exception("Path is '".$path."' is not inside base.");
			}
			return $real;
		}
		
		/**
		 * Returns the real path of an item on the real filesystem from the item's identifier.
		 * @param $id Identifier of the item.
		 * @return string Real path to the desired filesystem item.
		 */
		public function path($id) {
			$id = str_replace('/', DIRECTORY_SEPARATOR, $id);
			$id = trim($id, DIRECTORY_SEPARATOR);
			$id = $this->real($this->base . DIRECTORY_SEPARATOR . $id);
			return $id;
		}
		
		/**
		 * Returns the identificator of an item from the item's path on the real filesystem.
		 * @param $path Filesystem path to the item.
		 * @return string Identificator of the filesystem item.
		 */
		protected function id($path) {
			$path = $this->real($path);
			$path = substr($path, strlen($this->base));
			$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
			$path = trim($path, '/');
			return strlen($path) ? $path : '/';
		}

		/**
		 * Checks if the desired file or folder exists.
		 * @param $id ID of the item to check existence of.
		 * @return boolean Whether the file or folder exists.
		 */
		public function exists($id) {
			if ($id == '') {
				return true;
			}
			else {
				$id = trim($id, '/');
				$idPath = explode('/', $id);
				$lastItem = array_pop($idPath);
				$parentID = implode('/', $idPath);
				return $this->exists($parentID) && file_exists($this->path($id)) && $this->allowed($this->path($id));
			}
		}
		
		/**
		 * Checks if a file/folder is not blacklisted and access is allowed to it.
		 * @param $path Filesystem path to the item.
		 * @return boolean Whether the access to the item is allowed.
		 */
		protected function allowed($path) {
			foreach($this->blacklist as $folder) {
				if(strpos($path, $folder) === 0) {
					return false;
				}
			}
			return true;
		}
		
		/**
		 * Checks if a filename is consisted of valid characters.
		 * @param $name Filename to check.
		 * @return boolean Whether the filename is valid.
		 */
		protected function validName($name) {
			return !preg_match('([/?*:;{}\\\\])', $name);
		}
		
		/**
		 * Constructor creating an instance of the class.
		 * Reads the configuration file of the application and does the necessary setup of the instance.
		 */
		public function __construct() {
			// read the config of the IDE
			$config = json_decode(file_get_contents("config/config.json", true), true);
			$this->base = realpath($config['root']);
			
			// set up all the parameters from config
			if(!file_exists($this->base)) {
				throw new Exception('Base directory does not exist.');
			}

			// parse the blacklist
			if (isset($config['blacklist'])) {
				foreach($config['blacklist'] as $folder) {
					if ($folder != "") {
						$this->blacklist[] = $this->base . DIRECTORY_SEPARATOR .$folder;
					}
				}
			}
			
			// add version control folder to the blacklist
			if (isset($config['version-control-folder'])) {
				if ($config['version-control-folder'] != "") {
					$this->blacklist[] = $this->base . DIRECTORY_SEPARATOR . $config['version-control-folder'];
				}
			}
		}
		
		/**
		 * Returns the content listing of a folder.
		 * This method lists the contents of a specific folder of the user's project and returns the list in a format suitable for JSTree.
		 * @param $id ID of the folder to get listing of.
		 * @param $with_root Special parameter for JSTree specifying if we want the root folder of the user's project to be included in the listings as well.
		 * @return array An array containing the listings of the specified folder.
		 */
		public function lst($id, $with_root = false) {
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to access file/directory '.$this->id($dir));
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' does not exist.');
			}
			if (!is_dir($dir)) {
				throw new Exception('Item '.$this->id($dir).' is not a directory.');
			}
			$lst = scandir($dir);
			if($lst === FALSE) {
				throw new Exception('Cannot list path '. $this->id($dir));
			}
			// list the contents of the folder in a format suited for jstree
			$res = array();
			foreach($lst as $item) {
				if ($item == '.' || $item == '..' || $item === null) { continue; }
				if (!$this->allowed($dir . DIRECTORY_SEPARATOR . $item)) { continue; }
				if(is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
					$res[] = array('text' => $item, 'children' => true,  'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'type' => 'folder', 'icon' => 'folder', 'md5' => '');
				}
				else {
					$res[] = array('text' => $item, 'children' => false, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'type' => 'file', 'icon' => 'file file-'.substr($item, strrpos($item,'.') + 1), 'md5' => md5_file($dir . DIRECTORY_SEPARATOR . $item));
				}
			}
			// if we want to show the root folder, return it as well if we're asking for it now
			if($with_root && $this->id($dir) === '/') {
				$res = array(array('text' => '/', 'children' => $res, 'id' => '/', 'icon'=>'folder', 'state' => array('opened' => true, 'disabled' => true)));
			}
			return $res;
		}

		/**
		 * Returns the information about a specific file or folder of the user's project.
		 * @param $id ID of the item to get information about.
		 * @return array An array containing the information about the item.
		 */
		public function info($id) {
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to read file/directory '.$this->id($dir));
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' does not exist.');
			}
			if(is_dir($dir)) {
				// we return content (empty, but still) too, just to be consistent with data()
				return array('type'=>'folder', 'mimetype' => '', 'content' => '', 'ext' => '', 'size' => '', 'binary' => '');
			}
			if(is_file($dir)) {
				// get all the needed info
				$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
				$mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir);
				$size = filesize($dir);
				// check if the file is binary
				$binary = is_file_binary($dir);
				// we return content (empty, but still) too, just to be consistent with data()
				return array('type' => 'file', 'mimetype' => $mimetype, 'content' => '', 'ext' => $ext, 'size' => $size, 'binary' => $binary);
			}
			throw new Exception('Not a valid selection: ' .$this->id($dir));
		}
		
		/**
		 * Returns the contents of a file in the user's project.
		 * @param $id ID of the file to get contents of.
		 * @param $base64 Specifies, if we want the contents of the file to be encoded in base64.
		 * @return array An array containing the information about the file and its contents.
		 */
		public function data($id, $base64 = false) {
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to read file/directory '.$this->id($dir));
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' does not exist.');
			}
			if(is_dir($dir)) {
				// the function is primarily used to read file contents
				return array('type'=>'folder', 'mimetype' => '', 'content' => '', 'ext' => '', 'size' => '', 'binary' => '');
			}
			if(is_file($dir)) {
				$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
				$mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir);
				$size = filesize($dir);
				// check if the file is binary
				$binary = is_file_binary($dir);
				// return the file with info (and if we want it as base64 or it's binary, encode it as such)
				if ($base64 || $binary) {
					return array('type' => 'file', 'mimetype' => $mimetype, 'ext' => $ext, 'content' => base64_encode(file_get_contents($dir)), 'size' => $size, 'binary' => $binary, 'base64' => true);
				}
				else {
					return array('type' => 'file', 'mimetype' => $mimetype, 'ext' => $ext, 'content' => file_get_contents($dir), 'size' => $size, 'binary' => $binary,  'base64' => false);
				}
			}
			throw new Exception('Not a valid selection: '. $this->id($dir));
		}
		
		/**
		 * Creates a new file or folder in the user's project.
		 * @param $id ID of the parent folder where to create the new item.
		 * @param $name Name of the new item.
		 * @param $mkdir Specifies, whether to create a file, or a folder.
		 * @return array An array containing information about the new item.
		 */
		public function create($id, $name, $mkdir = false) {
			// check all the conditions
			if(!$this->validName($name)) {
				throw new Exception('Invalid name: '.$name);
			}
			$dir = $this->path($id).DIRECTORY_SEPARATOR.$name;
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to create file/directory '.$this->id($dir));
			}
			if (file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' already exists.');
			}
			// create the file/directory
			if($mkdir) {
				if (!mkdir($dir)) {
					throw new Exception('Cannot create directory '.$this->id($dir));
				}
			}
			else {
				if (!touch($dir)) {
					throw new Exception('Cannot create file '.$this->id($dir));
				}
			}
			// return this for jstree
			return array('id' => $this->id($dir));
		}
		
		/**
		 * Renames a file or folder in the user's project.
		 * @param $id ID of the item which to rename.
		 * @param $name New name of the item.
		 * @return array An array containing information about the renamed item.
		 */
		public function rename($id, $name) {
			// check all the conditions
			if(!$this->validName($name)) {
				throw new Exception('Invalid name: ' . $name);
			}
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to access file/directory '.$this->id($dir));
			}
			if($dir === $this->base) {
				throw new Exception('Not allowed to rename root directory');
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' does not exist.');
			}
			// prepare the new file path
			$new = explode(DIRECTORY_SEPARATOR, $dir);
			array_pop($new);
			array_push($new, $name);
			$new = implode(DIRECTORY_SEPARATOR, $new);
			// check all the conditions for the new path
			if ($new == $dir) {
				return array('id' => $this->id($new));
			}
			if (!$this->allowed($new)) {
				throw new Exception('Not allowed to rename file/directory to '.$this->id($new));
			}
			if (is_file($new) || is_dir($new)) {
				throw new Exception('File/directory '.$this->id($new).'  already exists');
			}
			if (!rename($dir, $new)) {
				throw new Exception('Could not rename file/directory '.$this->id($dir));
			}
			// return this for jstree
			return array('id' => $this->id($new));
		}
		
		/**
		 * Deletes a file or folder in the user's project.
		 * @param $id ID of the item which to delete.
		 * @return array An array about the status of the operation.
		 */
		public function remove($id) {
			// check all the conditions
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to delete file/directory '.$this->id($dir));
				return;
			}
			if($dir === $this->base) {
				throw new Exception('Not allowed to delete root directory');
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$this->id($dir).' does not exist.');
			}
			// if we're deleting a directory, do it recursively
			if(is_dir($dir)) {
				// delete all the files and subdirectories but the two special ones
				foreach(array_diff(scandir($dir), array(".", "..")) as $f) {
					$this->remove($this->id($dir . DIRECTORY_SEPARATOR . $f));
				}
				// if there are some left, we can't delete this directory
				if (count(scandir($dir)) <= 2) {
					if (!rmdir($dir)) {
						throw new Exception('Cannot delete directory '.$this->id($dir));
					}
				}
			}
			// otherwise just delete the file
			if(is_file($dir)) {
				if (!unlink($dir)) {
					throw new Exception('Cannot delete file '.$this->id($dir));
				}
			}
			// return this for jstree
			return array('status' => 'OK');
		}
		
		/**
		 * Moves a file or folder in the user's project to a new location.
		 * @param $id ID of the item which to move.
		 * @param $destDir Directory to which the item is to be moved to.
		 * @return array An array containing information about the moved item.
		 */
		public function move($id, $destDir) {
			// check all the conditions
			$dir = $this->path($id);
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to move file/directory '.$this->id($dir));
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$$this->id($dir).' does not exist.');
			}
			// prepare the new path
			$destDir = $this->path($destDir);
			$parentPath = explode(DIRECTORY_SEPARATOR, $dir);
			$name = array_pop($parentPath);
			$newPath = $destDir . DIRECTORY_SEPARATOR.$name;
			// check all the conditions for the new path
			if (!$this->allowed($newPath)) {
				throw new Exception('Not allowed to move file/directory '.$this->id($dir).' to '.$this->id($destDir));
			}
			// finally move the file/directory
			if (!rename($dir, $newPath)) {
				throw new Exception('Cannot move file/directory '.$this->id($dir));
			}
			// return this for jstree
			return array('id' => $this->id($newPath));
		}
		
		/**
		 * Copies a file or folder in the user's project to a new location.
		 * @param $id ID of the item which to copy.
		 * @param $par Directory to which the item is to be copied to.
		 * @return array An array containing information about the copied item.
		 */
		public function copy($id, $par) {
			$dir = $this->path($id);
			$par = $this->path($par);
			
			// check all the conditions
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to copy file/directory '.$id);
			}
			if (!$this->allowed($par)) {
				throw new Exception('Not allowed to access file/directory '.$this->id($par));
			}
			if (!file_exists($dir)) {
				throw new Exception('File/directory '.$id.' does not exist.');
			}
			
			// prepare the new path
			$new = explode(DIRECTORY_SEPARATOR, $dir);
			$new = array_pop($new);
			$new = $par . DIRECTORY_SEPARATOR . $new;
			
			// check all the conditions for the new path
			if (!$this->allowed($new)) {
				throw new Exception('Not allowed to access file/directory '.$this->id($new));
			}
			if (is_file($new) || is_dir($new)) {
				 throw new Exception('Path already exists: ' . $this->id($new));
			}
			
			// if we're copying a directory, create the destination and copy the contained files recursively
			if (is_dir($dir)) {
				if (!mkdir($new)) {
					throw new Exception('Cannot create destination directory '.$this->id($new));
				}
				foreach(array_diff(scandir($dir), array(".", "..")) as $f) {
					$this->copy($this->id($dir . DIRECTORY_SEPARATOR . $f), $this->id($new));
				}
			}
			// if we're copying a file, just copy it
			if(is_file($dir)) {
				if (!copy($dir, $new)) {
					throw new Exception('Cannot copy file/directory '.$this->id($dir));
				}
			}
			
			// return this for jstree
			return array('id' => $this->id($new));
		}
		
		/**
		 * Handles the moving of a uploaded file to the user's project.
		 * @param $tmpName Name of the temporary uploaded file.
		 * @param $folder Directory to which the item is to be uploaded to.
		 * @param $name New name of the uploaded item.
		 * @return array An array containing information about the uploaded item.
		 */
		public function uploadFile($tmpName, $folder, $name) {
			$dir = $this->path($folder);
			$filePath = $dir . DIRECTORY_SEPARATOR . $name;
			// check all the conditions
			if (!$this->allowed($filePath)) {
				throw new Exception('Not allowed to upload to directory '.$this->id($dir));
			}
			if(!$this->validName($name)) {
				throw new Exception('Invalid name: '.$name);
			}

			// move the uploaded file to the destination
			if (!move_uploaded_file($tmpName, $filePath)) {
				throw new Exception('Cannot upload file '.$name);
			}
			
			// return this just to be consistent with the other functions
			return array('id' => $this->id($filePath));
		}
		
		/**
		 * Puts new content to a file.
		 * @param $id ID of the file to which to put the new content.
		 * @param $content New content of the file.
		 * @return array An array containing information about the uploaded item.
		 */
		public function save($id, $content) {
			$dir = $this->path($id);
			// check all the conditions
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to save to file '.$id);
			}
			
			// check if the file existed previously
			$new = !file_exists($dir);
			
			// put the content to the specified file
			$r = file_put_contents($dir, $content, LOCK_EX);
			if ($r === FALSE) {
				throw new Exception('Cannot save to file '.$id);
			}
			
			// return 'id' just to be consistent with the other functions
			// return 'new' to indicate if the saved file is new and we should refresh jstree
			return array('id' => $id, 'new' => $new);
		}
		
		/**
		 * Starts the download of a file.
		 * @param $id ID of the file which to download.
		 */
		public function download($id) {
			$dir = $this->path($id);
			// check all the conditions
			if (!$this->allowed($dir)) {
				throw new Exception('Not allowed to read file/directory '.$id);
			}
			if(is_dir($dir)) {
				throw new Exception('Not allowed to download directories');
			}
			// finally download the file
			if(is_file($dir)) {
				download_file($dir);
			}
			else {
				throw new Exception('Not a valid selection: ' . $this->id($dir));
			}
		}
	}
