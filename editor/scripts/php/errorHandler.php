<?php
	/**
	 * This file contains the definition of the ErrorHandler class used for managing errors which arise during the run of the application.
	 */

	/**
	 * A class used for managing errors which arise during the run of the application.
	 */
	class ErrorHandler {
		/**
		 * Handles an error.
		 * This method handles an error which occurred during the run of the application by forwarding it to the right error page based on its error code.
		 * @param $errorCode Code of the error.
		 * @param $errorMessage Message associated with the error.
		 * @param $json Specifies, whether to print the error in JSON format, or in plain text.
		 */
		public static function handleError($errorCode, $errorMessage = '', $json = false) {
			// convert the error code to string so code analysis doesn't give warnings
			$errorCode = strval($errorCode);
			// check if the error code is valid
			if (!strpos($errorCode, '/') === false || !strpos($errorCode, '.') === false || !file_exists('pages/errors/'.$errorCode.'.php')) {
				$errorCode = 404;
			}
			// the class ErrorPage is defined in the file $errorCode.php
			require_once('pages/errors/'.$errorCode.'.php');
			ErrorPage::printError($errorMessage, $json);
		}
	}
