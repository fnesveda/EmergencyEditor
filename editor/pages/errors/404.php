<?php
	/**
	 * This file contains the definition of the ErrorPage class used to print error pages and messages to the browser.
	 */
	 /** 
 	 * Class for printing an error page / returning error information
 	 */
 	class ErrorPage {
 		/**
 		 * Prints an error page or message.
 		 * This method prints information about an error either in JSON or as a regular webpage.
 		 * @param $errorMessage Message associated with the error.
 		 * @param $json Specifies, whether to print the error in JSON format, or as a webpage.
 		 */
		public static function printError($errorMessage, $json = false) {
			// sanitize the error message before sending it out to the browser
			$errorMessage = preg_replace('|[^ a-zA-Z0-9.:;!?-_\'"/()#]|', '', $errorMessage);
			
			// send the appropriate error header
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			
			// send the appropriate result depending on the format we chose
			if ($json) {
				$result = array(
					'error' => $errorMessage, // this is used by Dropzone to display the error message after a failed upload
					'errorCode' => 404,
					'errorStatus' => '404 Not Found',
					'errorMessage' => $errorMessage // this is used by AJAX fail() callbacks
				);
				header('Content-Type: application/json; charset=utf8');
				echo json_encode($result);
			}
			else {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Error 404</title>
		<meta name="viewport" content="width=device-width" />
	</head>
	<body>
		<p>The requested page cannot be found.</p>
		<p><?php echo htmlspecialchars($errorMessage); ?></p>
	</body>
</html>
<?php
			}
			exit();
		}
	}
