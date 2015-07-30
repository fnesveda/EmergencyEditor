<?php
	/**
	 * This file is used to display the results of a parse the POST requests on the server.
	 */
	/** */
	try {
		if(isset($_GET['og-action'])) {
			switch($_GET['og-action']) {
				// for now we only have two POST actions
				case 'file-operation':
				case 'rvsn-operation':
					// file and rvsn operations return their results as URL-encoded JSON
					if (isset($_GET['result'])) {
						header('Content-Type: application/json; charset=utf8');
						echo urldecode($_GET['result']);
					}
					else {
						throw new Exception('There was an with parsing the request result.');
					}
				break;
				default:
					throw new Exception("The original request action '".$_GET['og-action']." 'is invalid.");
				break;
			}
		}
		else {
			throw new Exception('The original request action is missing.');
		}
	}
	catch (Exception $e) {
		require_once("scripts/php/errorHandler.php");
		ErrorHandler::handleError(404, $e->getMessage(), true);
	}