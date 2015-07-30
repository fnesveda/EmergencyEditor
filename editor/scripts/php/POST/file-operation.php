<?php
	/**
	 * This file is used to parse the POST requests for an operation with files and folders of the user's project
	 */
	/** */
	// include the filesystem handler class
	require_once("scripts/php/fs.php");
		
	try {
		// handle the supplied operation
		// the code was adapted from a demo code from jstree filebrowser (and heavily modified)
		if(isset($_POST['operation'])) {
			$fs = new fs();
			$rslt = null;
			// perform the appropriate filesystem action for the supplied operation
			// fs actions throw exceptions when they encounter an error, which we catch here
			switch($_POST['operation']) {
				case 'create-item':
					$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
					$rslt = $fs->create($node, isset($_POST['text']) ? $_POST['text'] : '', (!isset($_POST['type']) || $_POST['type'] !== 'file'));
				break;
				case 'rename-item':
					$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
					$rslt = $fs->rename($node, isset($_POST['text']) ? $_POST['text'] : '');
				break;
				case 'delete-item':
					$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
					$rslt = $fs->remove($node);
				break;
				case 'move-item':
					$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
					$parn = isset($_POST['parent']) && $_POST['parent'] !== '#' ? $_POST['parent'] : '/';
					$rslt = $fs->move($node, $parn);
				break;
				case 'copy-item':
					$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
					$parn = isset($_POST['parent']) && $_POST['parent'] !== '#' ? $_POST['parent'] : '/';
					$rslt = $fs->copy($node, $parn);
				break;
				case 'upload-files':
					// try to upload files to the supplied folder
					if (!isset($_POST['folder']) || ($_POST['folder'] == '')) {
						throw new Exception('Missing upload folder');
					}
					if (empty($_FILES)) {
						throw new Exception('No files to upload');
					}
					// process each file from $_FILES and try to apply the fs->uploadFile action
					// we don't copy it here directly, because we don't know the fs implementation in this script
					$failed_files = array();
					$rslt = array();
					foreach ($_FILES["file"]["error"] as $key => $error) {
						if ($error == UPLOAD_ERR_OK) {
							$tmp_name = $_FILES["file"]["tmp_name"][$key];
							$name = $_FILES["file"]["name"][$key];
							$rslt[] = $fs->uploadFile($tmp_name, $_POST['folder'], $name);
						}
						else {
							$failed_files[] = $_FILES["file"]["name"][$key];
						}
					}
					if (!empty($failed_files)) {
						throw new Exception('Files '.implode("', '", $failed_files).' failed to upload');
					}
				break;
				case 'save-file':
					if (!isset($_POST['filePath']) || ($_POST['filePath'] == '')) throw new Exception('Missing file path');
					if (!isset($_POST['content'])) throw new Exception('Missing file content');
					$rslt = $fs->save($_POST['filePath'], $_POST['content']);
				break;
				default:
					throw new Exception("The operation '".$_POST['operation']."' is not supported.");
				break;
			}
			// redirect to a GET script which returns the result from this one, to be consistent with the practice of POST scripts not returning anything
			$url_suffix = '?action=post-success&og-action=file-operation&result='.urlencode(json_encode($rslt));
			header("HTTP/1.1 303 See Other");
			header('Location: '.$_SERVER['SCRIPT_NAME'].$url_suffix);
			exit();
		}
		else {
			throw new Exception("The 'operation' parameter is missing");
		}
	}
	catch (Exception $e) {
		require_once("scripts/php/errorHandler.php");
		ErrorHandler::handleError(500, $e->getMessage(), true);
	}