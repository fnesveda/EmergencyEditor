<?php
	/**
	 * This file is used to create the webpage managing the version control system.
	 */
	/** */
	require_once("scripts/php/diff.php");
	require_once("scripts/php/rvsn.php");
	$rvsn = new rvsn();

	$view = "commits";
	if (isset($_GET['view']) && $_GET['view'] != "") {
		$view = $_GET['view'];
	}
	
	/**
	 * Formats the time to a specific format.
	 * This method formats the specified time to a specific format, for example "July 23, 2015, 17:24:35;.
	 * @param $time The time to format.
	 * @return string The formated time.
	 */
	function formatTime($time) {
		return date("F j, Y, G:i:s", $time);
	}
	
	/**
	 * Prints the list of changed items.
	 * This method prints the specially formatted list of changes between two commits.
	 * @param $olderCommitID The ID of the older commit.
	 * @param $newerCommitID The ID of the newer commit.
	 * @param $commitChanges The list of items changed between the two commits.
	 */
	function printChangedItems($olderCommitID, $newerCommitID, $commitChanges) {
		global $rvsn;
		$newItems = $commitChanges[0];
		$deletedItems = $commitChanges[1];
		$modifiedItems = $commitChanges[2]; ?>
		<div class="changed-items-list"><?php
		if (count($newItems) > 0) { ?> 
			<div class="changed-items-sublist changed-items-new-items">
				<div class="changed-items-sublist-title">New files and folders:</div><?php
			foreach($newItems as $item) { ?> 
				<div class="changed-items-sublist-item changed-items-new-item"><?php echo htmlspecialchars($item[rvsn::VCITEM_FILEID_POS]); ?></div><?php
			} ?> 
			</div><?php
		}
		if (count($deletedItems) > 0) { ?> 
			<div class="changed-items-sublist changed-items-deleted-items">
				<div class="changed-items-sublist-title">Deleted files and folders:</div><?php
			foreach($deletedItems as $item) { ?> 
				<div class="changed-items-sublist-item changed-items-deleted-item"><?php echo htmlspecialchars($item[rvsn::VCITEM_FILEID_POS]); ?></div><?php
			} ?> 
			</div><?php
		}
		if (count($modifiedItems) > 0) { ?> 
			<div class="changed-items-sublist changed-items-modified-items">
				<div class="changed-items-sublist-title">Modified files:</div><?php
			foreach($modifiedItems as $item) { ?> 
				<div class="changed-items-sublist-item changed-items-modified-item">
					<a href="rvsn/compare/<?php echo htmlspecialchars($olderCommitID);?>/<?php echo htmlspecialchars($newerCommitID);?>/<?php echo htmlspecialchars($item[$rvsn::VCITEM_FILEID_POS]);?>" title="Compare before and after"><?php echo htmlspecialchars($item[rvsn::VCITEM_FILEID_POS]); ?></a>
				</div><?php
			} ?> 
			</div><?php
		} ?> 
		</div><?php
	}
	
	/**
	 * Creates a select box for commits.
	 * This method creates a specially formatted select box in which to choose commits.
	 * @param $commits The commits to display in the select box.
	 * @param $boxID The ID attribute of the select box.
	 * @param $defaultCommitID The ID of the default selected commit.
	 */
	function createCommitSelectBox($commits, $boxID, $defaultCommitID = "-1") {
		global $rvsn;
		
		$commits = array_reverse($commits);
		
		$defaultCommit = [];
		$defaultCommit[rvsn::VCCOMMIT_ID_POS] = $defaultCommitID;
		$defaultCommit[rvsn::VCCOMMIT_TITLE_POS] = "Select commit";
		$defaultCommit[rvsn::VCCOMMIT_COMMENT_POS] = "Select the commit for comparison";
		
		if ($defaultCommitID == "latest") {
			$defaultCommit = $commits[1];
		}
		else if ($defaultCommitID != "none") {
			foreach($commits as $commit) { 
				if ($commit[rvsn::VCCOMMIT_ID_POS] == $defaultCommitID) {
					$defaultCommit = $commit;
				}
			}
		}
		?> 
		<div id="commit-select-box-<?php echo htmlspecialchars($boxID); ?>" class="commit-select-box">
			<input type="hidden" id="commit-select-box-<?php echo htmlspecialchars($boxID); ?>-selected-ID" value="<?php echo htmlspecialchars($defaultCommit[rvsn::VCCOMMIT_ID_POS]); ?>" />
			<div class="commit-select-box-current commit-select-box-item" onclick="toggleCommitSelectBox('commit-select-box-<?php echo htmlspecialchars($boxID); ?>');"><?php 
				if ($defaultCommit[rvsn::VCCOMMIT_TITLE_POS] == "") {
					$defaultCommit[rvsn::VCCOMMIT_TITLE_POS] = "Untitled commit";
				} ?> 
						<div class="commit-select-box-item-title"   ><?php echo htmlspecialchars($defaultCommit[rvsn::VCCOMMIT_TITLE_POS]); ?></div><?php
				if ($defaultCommit[rvsn::VCCOMMIT_COMMENT_POS] != "") { ?> 
						<div class="commit-select-box-item-comment" ><?php echo htmlspecialchars($defaultCommit[rvsn::VCCOMMIT_COMMENT_POS]); ?></div><?php
				} ?>
			</div>
			<div class="commit-select-box-list"><?php
		foreach($commits as $commit) { ?> 
				<div id="commit-select-box-<?php echo htmlspecialchars($boxID); ?>-item-<?php echo htmlspecialchars($commit[rvsn::VCCOMMIT_ID_POS]); ?>" class="commit-select-box-item" onclick="commitSelectBoxSelectCommit('commit-select-box-<?php echo htmlspecialchars($boxID); ?>', '<?php echo htmlspecialchars($commit[rvsn::VCCOMMIT_ID_POS]); ?>');"><?php
			if ($commit[rvsn::VCCOMMIT_TITLE_POS] == "") {
				$commit[rvsn::VCCOMMIT_TITLE_POS] = "Untitled commit";
			} ?> 
					<div class="commit-select-box-item-title"   ><?php echo htmlspecialchars($commit[rvsn::VCCOMMIT_TITLE_POS]); ?></div><?php
			if ($commit[rvsn::VCCOMMIT_COMMENT_POS] != "") { ?> 
					<div class="commit-select-box-item-comment" ><?php echo htmlspecialchars($commit[rvsn::VCCOMMIT_COMMENT_POS]); ?></div><?php
			}
			if ($commit[rvsn::VCCOMMIT_TIME_POS] > 0) { ?> 
					<div class="commit-select-box-item-datetime"><?php echo htmlspecialchars(formatTime($commit[rvsn::VCCOMMIT_TIME_POS])); ?></div><?php
			} ?> 
				</div><?php
		} ?> 
			</div>
		</div><?php
	} ?>
<!DOCTYPE html>
<html>
	<head>
		<base href="<?php echo $baseHref ?>" />
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width" />
		<title>Emergency Editor Version Control</title>

		<script src="./lib/jquery/jquery.min.js"></script>

		<script src="./lib/ace/src-min-noconflict/ace.js"></script>
		<script src="./lib/ace/src-min-noconflict/ext-modelist.js"></script>
		<script src="./lib/ace/src-min-noconflict/ext-language_tools.js"></script>

		<script src="./lib/cookie/jquery.cookie.js"></script>

		<script src="./lib/fileDownload/jquery.fileDownload.js"></script>

		<link  href="./lib/jstree/themes/default/style.min.css" rel="stylesheet" type="text/css" />
		<link  href="./lib/jstree/jstree.css" rel="stylesheet" type="text/css" />
		<script src="./lib/jstree/jstree.min.js"></script>

		<link href="./styles/common.css" rel="stylesheet" type="text/css" />
		<link href="./styles/jstree.css" rel="stylesheet" type="text/css" />
		<link href="./styles/rvsn.css"   rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div class="main" id="main">
			<div class="container">
				<div id="top-bar" class="top-bar">
					<div class="top-bar-links">
						<a href="rvsn/commits/"<?php if ($view=="commits") echo ' class="selected"'; ?>>Commits</a>
						<a href="rvsn/compare/"<?php if ($view=="compare") echo ' class="selected"'; ?>>Compare</a>
						<a href="rvsn/browse/" <?php if ($view=="browse")  echo ' class="selected"'; ?>>Browse </a>
					</div>
				</div>
				<div class="content"><?php
	switch($view) {
		case "commits":
			if (isset($_GET['commit']) && $_GET['commit'] != "") {
				$commitToView = $_GET['commit'];
				
				if ($commitToView == "current") {
					$commitID = "current";
					$commitTitle = "Current state of the project";
					$commitComment = "The state of the project as it is right now";
					$commitDatetime = time();
					$prevCommitID = "latest";
					
					$commitChanges = $rvsn->getChangesBetweenCommits("latest", "current", "/", "", true);
				}
				else if ($commitToView == "none") {
					$commitID = "none";
					$commitTitle = "Initial state of the project";
					$commitComment = "The state of the project before any commit";
					$commitDatetime = 0;
					$prevCommitID = "none";
					
					$commitChanges = [[], [], []];
				}
				else {
					$commitInfo = $rvsn->getCommitInfo($commitToView);
					if ($commitInfo == null) {
						$commitID = "invalid";
						$commitTitle = "Invalid commit";
						$commitComment = "The commit you want to view is invalid";
						$commitDatetime = 0;
						$prevCommitID = "invalid";

						$commitChanges = [[], [], []];
					}
					else {
						$commitID = $commitInfo[$rvsn::VCCOMMIT_ID_POS];
						$commitTitle = $commitInfo[$rvsn::VCCOMMIT_TITLE_POS];
						$commitComment = $commitInfo[$rvsn::VCCOMMIT_COMMENT_POS];
						$commitDatetime = $commitInfo[$rvsn::VCCOMMIT_TIME_POS];
						$prevCommitID = $commitInfo[$rvsn::VCCOMMIT_PREV_POS];

						if ($commitTitle == "") {
							$commitTitle = "Untitled commit";
						}

						$commitChanges = $rvsn->getChangesBetweenCommits($prevCommitID, $commitID, "/", "", true);
					}
				} ?> 
					<div class="commit-info content-center">
						<div class="commit-info-header">
							<div class="commit-info-header-details">
								<div class="commit-info-header-title"><?php echo htmlspecialchars($commitTitle);?></div><?php
				if ($commitComment != "") { ?> 
								<div class="commit-info-header-comment"><?php echo htmlspecialchars($commitComment);?></div><?php
				}
				if ($commitDatetime > 0) { ?> 
								<div class="commit-info-header-datetime"><?php echo htmlspecialchars(formatTime($commitDatetime)); ?></div><?php
				} ?> 
							</div><?php
				if ($commitID == "current") { ?> 
							<div class="button commit-info-button commit-new-commit-button"><span onclick="toggleCommitPopup();" title="Commit the current changes">Commit changes</span></div><?php
				}
				else { ?> 
							<div id="commit-revert-button" class="button commit-info-button commit-revert-button" onclick="revertToCommit('<?php echo htmlspecialchars($commitID);?>');" ><span title="Revert the project to this commit">Revert to this commit</span></div><?php
				} ?> 
							<div class="button commit-info-button commit-browse-button"><a href="rvsn/browse/<?php echo htmlspecialchars($commitID);?>/" title="Browse the project at this commit">Browse</a></div>
						</div><?php
				if (count($commitChanges[0]) == 0 && count($commitChanges[1]) == 0 && count($commitChanges[2]) == 0) { ?> 
						<div class="changed-items-empty">
							<span>There was no change in this commit.</span>
						</div><?php 
				}
				else { 
					printChangedItems($prevCommitID, $commitID, $commitChanges);
				} ?> 
					</div><?php
			}
			else {
				$commits = $rvsn->getCommits();
				
				$currentFakeCommit = [];
				$currentFakeCommit[$rvsn::VCCOMMIT_ID_POS] = "current";
				$currentFakeCommit[$rvsn::VCCOMMIT_TIME_POS] = 0;
				$currentFakeCommit[$rvsn::VCCOMMIT_TITLE_POS] = "Current state of the project";
				$currentFakeCommit[$rvsn::VCCOMMIT_COMMENT_POS] = "The state of the project as it is right now";
				
				$commits[] = $currentFakeCommit; ?> 
					<div class="commit-list content-center"><?php
				for ($i = count($commits) - 1; $i >= 0; $i--) {
					$commitID = $commits[$i][$rvsn::VCCOMMIT_ID_POS];
					$commitDatetime = $commits[$i][$rvsn::VCCOMMIT_TIME_POS];
					$commitTitle = $commits[$i][$rvsn::VCCOMMIT_TITLE_POS];
					$commitComment = $commits[$i][$rvsn::VCCOMMIT_COMMENT_POS]; ?> 
						<div class="commit-list-item">
							<div class="commit-list-item-details">
								<div class="commit-list-item-title"><a href="rvsn/commits/<?php echo htmlspecialchars($commitID);?>/"><?php echo htmlspecialchars($commitTitle); ?></a></div><?php
					if ($commitComment != "") { ?> 
								<div class="commit-list-item-comment"><?php echo htmlspecialchars($commitComment); ?></div><?php
					}
					if ($commitDatetime > 0) { ?>
								<div class="commit-list-item-datetime"><?php echo htmlspecialchars(formatTime($commitDatetime)); ?></div><?php
					} ?> 
							</div>
							<div class="button commit-info-button commit-browse-button"><a href="rvsn/browse/<?php echo htmlspecialchars($commitID);?>/" title="Browse the project at this commit">Browse</a></div>
						</div><?php
				} ?> 
					</div><?php
			}
		break;
		case "compare":
			$emptyFakeCommit = [];
			$emptyFakeCommit[$rvsn::VCCOMMIT_ID_POS] = "none";
			$emptyFakeCommit[$rvsn::VCCOMMIT_TIME_POS] = 0;
			$emptyFakeCommit[$rvsn::VCCOMMIT_TITLE_POS] = "Initial state of the project";
			$emptyFakeCommit[$rvsn::VCCOMMIT_COMMENT_POS] = "The state of the project before any commit";
			
			$commits = [$emptyFakeCommit];
		
			$commits = array_merge($commits, $rvsn->getCommits());
			
			$currentFakeCommit = [];
			$currentFakeCommit[$rvsn::VCCOMMIT_ID_POS] = "current";
			$currentFakeCommit[$rvsn::VCCOMMIT_TIME_POS] = 0;
			$currentFakeCommit[$rvsn::VCCOMMIT_TITLE_POS] = "Current state of the project";
			$currentFakeCommit[$rvsn::VCCOMMIT_COMMENT_POS] = "The state of the project as it is right now";
			
			$commits[] = $currentFakeCommit;

			$olderCommit = "-1";
			$newerCommit = "-1";
			$itemID = "/";
			if (isset($_GET['olderCommit']) && $_GET['olderCommit'] != "") {
				$olderCommit = $_GET['olderCommit'];
			}
			if (isset($_GET['newerCommit']) && $_GET['newerCommit'] != "") {
				$newerCommit = $_GET['newerCommit'];
			}
			if (isset($_GET['itemID']) && $_GET['itemID'] != "") {
				$itemID = $_GET['itemID'];
			} ?> 
			<div class="compare-commits-header"><?php
			createCommitSelectBox($commits, "older", $olderCommit); ?>
				<div class="button compare-commits-button"><span onclick="compareSelectedCommits('older', 'newer')">Compare commits</span></div><?php 
			createCommitSelectBox($commits, "newer", $newerCommit); ?>
			</div><?php
			if ($olderCommit != "-1" && $newerCommit != "-1") {
				$olderFileContents = $rvsn->getFileContentsAtCommit($itemID, $olderCommit);
				$newerFileContents = $rvsn->getFileContentsAtCommit($itemID, $newerCommit);
				
				if ($olderFileContents == null) { ?> 
					<div class="changed-items-empty">
						<span>The item <span class="compare-message-item-id"><?php echo htmlspecialchars($itemID); ?></span> didn't exist at commit <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $olderCommit; ?>"><?php echo htmlspecialchars($olderCommit); ?></a>.</span>
					</div><?php 
				}
				else if ($newerFileContents == null) { ?> 
					<div class="changed-items-empty">
						<span>The item <span class="compare-message-item-id"><?php echo htmlspecialchars($itemID); ?></span> didn't exist at commit <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $newerCommit; ?>"><?php echo htmlspecialchars($newerCommit); ?></a>.</span>
					</div><?php 
				}
				else if ($olderFileContents['type'] == "folder" && $newerFileContents['type'] == "file") { ?> 
					<div class="changed-items-empty">
						<span>The item <span class="compare-message-item-id"><?php echo htmlspecialchars($itemID); ?></span> is a folder at commit <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $olderCommit; ?>"><?php echo htmlspecialchars($olderCommit); ?></a>, but a file at commit  <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $newerCommit; ?>"><?php echo htmlspecialchars($newerCommit); ?></a>.</span>
					</div><?php 
				}
				else if ($olderFileContents['type'] == "file" && $newerFileContents['type'] == "folder") { ?> 
					<div class="changed-items-empty">
						<span>The item <span class="compare-message-item-id"><?php echo htmlspecialchars($itemID); ?></span> is a file at commit <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $olderCommit; ?>"><?php echo htmlspecialchars($olderCommit); ?></a>, but a folder at commit  <a class="compare-message-commit-link" href="rvsn/commits/<?php echo $newerCommit; ?>"><?php echo htmlspecialchars($newerCommit); ?></a>.</span>
					</div><?php 
				}
				else if ($olderFileContents['type'] == "folder" && $newerFileContents['type'] == "folder") {
					$commitChanges = $rvsn->getChangesBetweenCommits($olderCommit, $newerCommit, $itemID, "", true);
					if (count($commitChanges[0]) == 0 && count($commitChanges[1]) == 0 && count($commitChanges[2]) == 0) { ?> 
						<div class="changed-items-empty">
							<span>There are no changes between the selected commits.</span>
						</div><?php 
					}
					else {
						printChangedItems($olderCommit, $newerCommit, $commitChanges);
					}
				}
				else {
					if ($olderFileContents['binary'] == true || $newerFileContents['binary'] == true) { ?>
					<div class="compare-files-binary-message">
						<span>The file <?php echo htmlspecialchars($itemID); ?> is binary, which means we can't compare its versions.</span>
					</div><?php
					}
					else {
						$diff = diff::compare($olderFileContents["content"], $newerFileContents["content"]); ?>
						<table class="file-diff-table">
							<thead>
								<tr class="fake-row">
									<th class="file-diff-line-number"></th>
									<th class="file-diff-line"></th>
									<th class="file-diff-line-number"></th>
									<th class="file-diff-line"></th>
								</tr>
								<tr>
									<th colspan="2" class="file-diff-header"><span class="file-diff-header-fileID"><?php echo htmlspecialchars($itemID); ?></span> at commit <a class="file-diff-header-commit-link" href="rvsn/commits/<?php echo htmlspecialchars($olderCommit); ?>"><?php echo htmlspecialchars($olderCommit); ?></a></th>
									<th colspan="2" class="file-diff-header"><span class="file-diff-header-fileID"><?php echo htmlspecialchars($itemID); ?></span> at commit <a class="file-diff-header-commit-link" href="rvsn/commits/<?php echo htmlspecialchars($newerCommit); ?>"><?php echo htmlspecialchars($newerCommit); ?></a></th>
								</tr>
							</thead>
							<tbody><?php 
						$olderFileLineNumber = 1;
						$newerFileLineNumber = 1;
						foreach ($diff as $diffLine) { ?> 
								<tr><?php 
							$diffLine[1] = htmlspecialchars($diffLine[1]);
							if ($diffLine[0] == diff::LINE_PRESERVED) { ?> 
									<td class="file-diff-line-number file-diff-preserved"><?php echo htmlspecialchars($olderFileLineNumber++); ?></td>
									<td class="file-diff-line        file-diff-preserved"><code><?php echo htmlspecialchars($diffLine[1]); ?></code></td>
									<td class="file-diff-line-number file-diff-preserved"><?php echo htmlspecialchars($newerFileLineNumber++); ?></td>
									<td class="file-diff-line        file-diff-preserved"><code><?php echo htmlspecialchars($diffLine[1]); ?></code></td><?php 
							}
							if ($diffLine[0] == diff::LINE_DELETED) { ?> 
									<td class="file-diff-line-number file-diff-deleted"><?php echo htmlspecialchars($olderFileLineNumber++); ?></td>
									<td class="file-diff-line        file-diff-deleted"><code><?php echo htmlspecialchars($diffLine[1]); ?></code></td>
									<td class="file-diff-line-number file-diff-skip"></td>
									<td class="file-diff-line        file-diff-skip"></td><?php 
							}
							if ($diffLine[0] == diff::LINE_NEW) { ?> 
									<td class="file-diff-line-number file-diff-skip"></td>
									<td class="file-diff-line        file-diff-skip"></td>
									<td class="file-diff-line-number file-diff-new"><?php echo htmlspecialchars($newerFileLineNumber++); ?></td>
									<td class="file-diff-line        file-diff-new"><code><?php echo htmlspecialchars($diffLine[1]); ?></code></td><?php 
							} ?>
								</tr><?php
						} ?>
							</tbody>
						</table><?php 
					}
				}
			}
		break;
		case "browse":
			if (isset($_GET['commit']) && $_GET['commit'] != "") {
				$commitToView = $_GET['commit']; ?>
				<script type="text/javascript">commitID = "<?php echo htmlspecialchars($commitToView); ?>"</script>
					<div id="tree"></div>
					<div class="tree-search-box-container"><input type="search" id="tree-search-box" class="tree-search-box" placeholder="Search files and folders"></div>
					<div id="file-container" class="file-container">
						<div id="file-dummy-message" class="file-dummy-message">
							<span>Double-click a file to open it.</span>
						</div>
						<div id="file-content" class="file-content">
							<pre id="editor"      class="file-editor"></pre>
							<div id="file-image"  class="file-image">
								<img src="images/placeholder.png" alt=""/>
							</div>
						</div>
					</div><?php
			}
			else {
				$commits = $rvsn->getCommits();
				
				$currentFakeCommit = [];
				$currentFakeCommit[$rvsn::VCCOMMIT_ID_POS] = "current";
				$currentFakeCommit[$rvsn::VCCOMMIT_TIME_POS] = 0;
				$currentFakeCommit[$rvsn::VCCOMMIT_TITLE_POS] = "Current state of the project";
				$currentFakeCommit[$rvsn::VCCOMMIT_COMMENT_POS] = "The state of the project as it is right now";
				
				$commits[] = $currentFakeCommit; ?> 
				<div class="browse-commit-header"><?php
				createCommitSelectBox($commits, "browse", "-1"); ?>
					<div class="button browse-commit-button"><span onclick="browseAtSelectedCommit('browse')">Browse at commit</span></div>
				</div><?php
			}
		break;
	} ?> 
				</div>
			</div>
			<div id="error-popup" class="popup popup-hidden">
				<div id="error-popup-background" class="popup-background" onclick="toggleErrorPopup()"></div>
				<div id="error-popup-content" class="popup-content error-popup-content">
					<div id="error-popup-title" class="popup-title">Error <span class="popup-close" onclick="toggleErrorPopup()"></span></div>
					<div id="error-popup-text" class="error-popup-text"></div>
				</div>
			</div>
			<div id="commit-popup" class="popup popup-hidden">
				<div id="commit-popup-background" class="popup-background" onclick="toggleCommitPopup()"></div>
				<div id="commit-popup-content" class="popup-content commit-popup-content">
					<div id="commit-popup-title" class="popup-title">Create new commit <span class="popup-close" onclick="toggleCommitPopup()"></span></div>
					<div id="commit-popup-inputs-wrapper" class="commit-popup-inputs-wrapper">
						<input type="text" id="commit-form-title"          class="commit-form-title"   name="title"     placeholder="Commit title">
						<textarea          id="commit-form-comment"        class="commit-form-comment" name="comment"   placeholder="Commit comment"></textarea>
						<div               id="commit-form-submit-button"  class="button commit-form-submit-button" onclick="commitChanges();"><span>Commit changes</span></div>
					</div>
				</div>
			</div>
		</div>
<?php include("pages/snippets/browserSupport.php"); ?> 
		<script src="./scripts/js/rvsn.js"></script>
	</body>
</html>