<?php
	/**
	 * This file contains general purpose functions used throughout the project.
	 * This file is to be included from the topmost script, in this case index.php, so we don't need to include it from children scripts.
	 */
	/** */

	if (!function_exists('merge_presorted_arrays')) {
		/**
		 * Merges two arrays, whose contents are sorted, to a new already sorted array.
		 * @param $left One of the arrays to merge.
		 * @param $right Other of the arrays to merge.
		 * @param $comparer Function used to compare the items of the arrays.
		 * @return array The sorted array made from the items of both the input arrays.
		 */
		function merge_presorted_arrays($left, $right, $comparer = null) {
			if ($comparer === null) {
				$comparer = function($a, $b) {
					if ($a < $b)  return -1;
					if ($a == $b) return 0;
					if ($a > $b)  return 1;
				};
			}
			$leftLength = count($left);
			$rightLength = count($right);
			$result = [];
			
			$i = 0;
			$j = 0;
			
			while ($i < $leftLength || $j < $rightLength) {
				if ($i < $leftLength && $j < $rightLength) {
					$cmp = $comparer($left[$i], $right[$j]);
				}
				else if ($i < $leftLength) {
					$cmp = -1;
				}
				else {
					$cmp = 1;
				}
				
				if ($cmp <= 0) {
					$result[] = $left[$i++];
				}
				if ($cmp >= 0) {
					$result[] = $right[$j++];
				}
			}
			
			return $result;
		}
	}
/* 
	EXAMPLE USAGE: 
	$arr1 = array(1, 2, 4, 6);
	$arr2 = array(0, 3, 4, 7);
	$arr3 = merge_presorted_arrays($arr1, $arr2);

	$arr1 = array('b', 'c', 'e', 'g');
	$arr2 = array('a', 'd', 'e', 'h');
	$arr3 = merge_presorted_arrays($arr1, $arr2, 'strcmp');

	$arr1 = array(5, 4, 2, 0);
	$arr2 = array(7, 5, 5, 3, 2, 1);
	$arr3 = merge_presorted_arrays($arr1, $arr2, function($a, $b) {
		if ($a < $b)  return 1;
		if ($a == $b) return 0;
		if ($a > $b)  return -1;
	});
*/

	if (!function_exists('is_file_binary')) {
		/**
		 * Detects if a file contains binary data.
		 * @param $path Path to the examined file.
		 * @return boolean Whether the file contains binary data, or not.
		 */
		function is_file_binary($path) {
			$binary = false;
			// check the first 4096 bytes for occurrences of bytes normally not appearing in text (ASCII / ISO 8859/I / UTF-8)
			$first4k = file_get_contents($path, false, NULL, 0, 4096);
			for ($i = 0; $i < strlen($first4k); $i++) {
				$code = ord($first4k[$i]);
				if ($code < 32 && $code != 9 && $code != 10 && $code != 13) // 9 .. tab, 10 .. line feed, 13 .. carriage return
				{
					$binary = true;
					break;
				}
			}
			return $binary;
		}
	}
	
	if (!function_exists('download_file')) {
		/**
		 * Initiates the download of a file in the user's browser.
		 * @param $path Path to the file to download.
		 * @param $name Name as which the file should be saved when downloaded.
		 * @param $allowResume Whether to allow resuming the download.
		 * @param $deleteAfter Whether to delete the file on the server after downloading.
		 */
		function download_file($path, $name = "", $allowResume = true, $deleteAfter = false) {
			if ($name == "") {
				$name = basename($path);
			}
			$mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
			// set all the right headers for a file download
			header('Set-Cookie: fileDownload=true; path=/'); // for the jquery.fileDownload library
			header('Cache-Control: max-age=60, must-revalidate');
			header('Content-Transfer-Encoding: binary'); // For Gecko browsers mainly
			header('Content-Encoding: none');
			header('Content-Type: '.$mimetype); // if there are some problems with this, switch to application/octet-stream for everything
			header('Content-Disposition: attachment; filename="'.$name.'"');
			header('Content-Length: ' . filesize($path));
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
			if ($allowResume) {
				header('Accept-Ranges: bytes');  // For download resume
			}
			header('Expires: 0');
			header('Pragma: public');
			readfile($path);
			if ($deleteAfter) {
				unlink($path);
			}
			exit();
		}
	}

	// define simpler versions of functions json_encode and json_decode, if they don't exist, like at www.ms.mff.cuni.cz
	// taken from http://www.abeautifulsite.net/using-json-encode-and-json-decode-in-php4/

	// so we can use the two parameter version of json_encode on PHP < 5.4 easily
	if (!defined('JSON_UNESCAPED_SLASHES')) {
		define('JSON_UNESCAPED_SLASHES', 64);
	}
	if (!defined('JSON_PRETTY_PRINT')) {
		define('JSON_PRETTY_PRINT', 128);
	}
	
	if (!function_exists('json_encode')) {
		require_once('lib/Services_JSON/JSON.php');
		/**
		 * Encodes a variable to the JSON format.
		 * @param $data The data to encode.
		 * @param $options Options of the encoding.
		 * @return string The encoded data.
		 */
		function json_encode($data, $options = 0) {
			// currently the $options parameter does nothing
			// it's there only for PHP not to shout when using the two parameter version
			$json = new Services_JSON();
			return($json->encode($data));
		}
	}

	if (!function_exists('json_decode')) {
		require_once('lib/Services_JSON/JSON.php');
		/**
		 * Decodes a JSON-encoded variable.
		 * @param $data The JSOn-encoded data.
		 * @param $output_as_array Whether to decode the data to an array or an object.
		 * @return array|object The decoded variable.
		 */
		function json_decode($data, $output_as_array) {
			$json_mode = $output_as_array ? SERVICES_JSON_LOOSE_TYPE : null;
			$json = new Services_JSON($json_mode);
			return($json->decode($data));
		}
	}

	