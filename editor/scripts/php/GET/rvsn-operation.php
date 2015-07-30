<?php
	/**
	 * This file is used to parse the GET requests for an operation on the version control system.
	 */
	/** */
	// include the filesystem handler class
	require_once("scripts/php/rvsn.php");

	try {
		// handle the supplied operation
		// the code was adapted from a demo code from jstree filebrowser (and heavily modified)
		if(isset($_GET['operation'])) {
			$rvsn = new rvsn();
			$rslt = null;
			// perform the appropriate filesystem action for the supplied operation
			// fs actions throw exceptions when they encounter an error, which we catch here
			switch($_GET['operation']) {
				case 'get-changes-between-commits':
					$olderCommit = isset($_GET['olderCommit']) ? $_GET['olderCommit'] : '';
					$newerCommit = isset($_GET['newerCommit']) ? $_GET['newerCommit'] : '';
					$rslt = $rvsn->getChangesBetweenCommits($olderCommit, $newerCommit);
				break;
				case 'get-node':
					$id     = isset($_GET['id'])     ? $_GET['id']     : '/';
					$commit = isset($_GET['commit']) ? $_GET['commit'] : '';
					$rslt = $rvsn->getNodeAtCommit($id, $commit, ($id === '#'));
				break;
				case 'get-info':
					$id     = isset($_GET['id'])     ? $_GET['id']     : '';
					$commit = isset($_GET['commit']) ? $_GET['commit'] : '';
					$rslt = $rvsn->getFileInfoAtCommit($id, $commit);
				break;
				case 'get-content':
					$id     = isset($_GET['id'])     ? $_GET['id']     : '';
					$commit = isset($_GET['commit']) ? $_GET['commit'] : '';
					$base64 = isset($_GET['base64']) ? $_GET['base64'] : false;
					$rslt = $rvsn->getFileContentsAtCommit($id, $commit, $base64);
				break;
				case 'get-commits':
					$rslt = $rvsn->getCommits();
				break;
				case 'download-file':
					$id     = isset($_GET['id'])     ? $_GET['id']     : '';
					$commit = isset($_GET['commit']) ? $_GET['commit'] : '';
					$rslt = $rvsn->downloadFileAtCommit($id, $commit);
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
