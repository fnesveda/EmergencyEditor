<?php
	/**
	 * This file is used to parse the POST requests for an operation on the version control system.
	 */
	/** */
	// include the revision handler class
	require_once("scripts/php/rvsn.php");
		
	try {
		// handle the supplied operation
		// the code was adapted from a demo code from jstree filebrowser (and heavily modified)
		if(isset($_POST['operation'])) {
			$rvsn = new rvsn();
			$rslt = null;
			// perform the appropriate filesystem action for the supplied operation
			// fs actions throw exceptions when they encounter an error, which we catch here
			switch($_POST['operation']) {
				case 'commit-changes':
					$title   = isset($_POST['title'])   ? $_POST['title']   : '';
					$comment = isset($_POST['comment']) ? $_POST['comment'] : '';
					$rslt = $rvsn->commitChanges($title, $comment);
				break;
				case 'revert-item':
					$id     = isset($_POST['id'])     ? $_POST['id']     : '';
					$commit = isset($_POST['commit']) ? $_POST['commit'] : '';
					$rslt = $rvsn->revertItemToCommit($id, $commit);
				break;
				case 'revert-all':
					$commit = isset($_POST['commit']) ? $_POST['commit'] : '';
					$rslt = $rvsn->revertAllToCommit($commit);
				break;

				default:
					throw new Exception("The operation '".$_POST['operation']."' is not supported.");
				break;
			}
			// redirect to a GET script which returns the result from this one, to be consistent with the practice of POST scripts not returning anything
			$url_suffix = '?action=post-success&og-action=rvsn-operation&result='.urlencode(json_encode($rslt));
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