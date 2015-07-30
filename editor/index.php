<?php
	/**
	 * This file serves as the main script of the application which handles all requests and includes the right scripts to take appropriate actions.
	 */
	/** */

	// the base address to use as the base for links and AJAX requests in the application
	$baseHref = dirname($_SERVER['SCRIPT_NAME'])."/";

	// include general language additions
	require_once("scripts/php/functions.php");
	
	// the main entrance for the whole IDE
	// we handle actions differently depending on which METHOD are they requested with
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			// get the action, default is 'page'
			$action = 'page';
			if (isset($_GET['action']))	{
				$action = $_GET['action'];
			}
			// check if the action is valid, and if so, include the right file
			if (strpos($action, '/') === false && strpos($action, '.') === false && file_exists('scripts/php/GET/'.$action.'.php')) {
				include('scripts/php/GET/'.$action.'.php');
			}
			else {
				require_once("scripts/php/errorHandler.php");
				ErrorHandler::handleError(404, "Action '".$action."' could not be handled.", false);
			}
			break;
		case 'POST':
			// get the action, there is no default
			$action = '';
			if (isset($_POST['action'])) {
				$action = $_POST['action'];
			}
			// check if the action is valid, and if so, include the right file
			if (strpos($action, '/') === false && strpos($action, '.') === false && file_exists('scripts/php/POST/'.$action.'.php')) {
				include('scripts/php/POST/'.$action.'.php');
			}
			else {
				require_once("scripts/php/errorHandler.php");
				ErrorHandler::handleError(500, "Action '".$action."' could not be handled.", true);
			}
			break;
	}
