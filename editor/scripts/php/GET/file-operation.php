<?php
	/**
	 * This file is used to parse the GET requests for an operation with files and folders of the user's project
	 */
	/** */
	// include the filesystem handler class
	require_once("scripts/php/fs.php");

	try {
		// handle the supplied operation
		// the code was adapted from a demo code from jstree filebrowser (and heavily modified)
		if(isset($_GET['operation'])) {
			$fs = new fs();
			$rslt = null;
			// perform the appropriate filesystem action for the supplied operation
			// fs actions throw exceptions when they encounter an error, which we catch here
			switch($_GET['operation']) {
				case 'get-node':
					$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
					$rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
				break;
				case "get-info":
					$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
					$rslt = $fs->info($node);
				break;
				case "get-content":
					$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
					$rslt = $fs->data($node, FALSE);
				break;
				case "get-content-base64":
					$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
					$rslt = $fs->data($node, TRUE);
				break;
				case "download-item":
					$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
					$rslt = $fs->download($node);
				break;
				default:
					throw new Exception("The operation '".$_GET['operation']."' is not supported.");
				break;
			}
			// return the result as JSON
			header('Content-Type: application/json; charset=utf8');
			echo json_encode($rslt);
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
