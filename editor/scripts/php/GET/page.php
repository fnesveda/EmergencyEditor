<?php
	/**
	 * This file is used to parse the GET requests to display a page of the application.
	 */
	/** */
	try {
		// get the page name, default is 'home'
		$page = 'home';
		if (isset($_GET['page']) && $_GET['page'] != '')
		{
			$page = $_GET['page'];
		}

		// check if the page name is safe to call include with
		if (!strpos($page, '/') === false || !strpos($page, '.') === false)
		{
			throw new Exception("Page name '".$page."' is invalid.");
		}

		// check if the supplied page exists
		if (!file_exists('pages/content/'.$page.'.php'))
		{
			throw new Exception("Page '".$page."' doesn't exist.");
		}
		include('pages/content/'.$page.'.php');
	}
	catch (Exception $e) {
		require_once("scripts/php/errorHandler.php");
		ErrorHandler::handleError(404, $e->getMessage(), false);
	}