<?php
	/**
	 * This file defines the diff class used to compare two files and create edit scripts between them.
	 */

	/**
 	 * A class used to compare two files and create edit scripts between them.
 	 */
	class diff {
		const LINE_PRESERVED = 0;
		const LINE_NEW       = 1;
		const LINE_DELETED   = 2;

		/**
		 * Compares the contents of two files and assesses which lines are missing from each.
		 * @param $olderFile Contents of the older file.
		 * @param $newerFile Contents of the newer file.
		 * @return array Array with information about the new, deleted and preserved lines between the two files.
		 */
		public static function compare($olderFile, $newerFile) {
			if ($olderFile == '') {
				$olderLines = [];
			}
			else {
				$olderLines = explode("\n", $olderFile);
			}
			if ($newerFile == '') {
				$newerLines = [];
			}
			else {
				$newerLines = explode("\n", $newerFile);
			}
			
			$olderLength = count($olderLines);
			$newerLength = count($newerLines);

			$start = 0;
			$olderEnd = $olderLength;
			$newerEnd = $newerLength;
			
			// skip any common lines at the beginning
			while ($start < $olderLength && $start < $newerLength && $olderLines[$start] == $newerLines[$start]) {
				$start++;
			}

			// skip any common lines at the end
			while ($start < $olderEnd && $start < $newerEnd && $olderLines[$olderEnd - 1] == $newerLines[$newerEnd - 1]) {
				$olderEnd--;
				$newerEnd--;
			}
			
			$changedOlderLines = array_slice($olderLines, $start, $olderEnd - $start);
			$changedNewerLines = array_slice($newerLines, $start, $newerEnd - $start);

			// generate the diff
			$changedLinesDiff = self::generateDiff($changedOlderLines, $changedNewerLines);

			// generate the complete diff
			$diff = [];
			
			// append the lines from the beginning
			for ($i = 0; $i < $start; $i++) {
				$diff[] = array(self::LINE_PRESERVED, $olderLines[$i]);
			}
			$diff = array_merge($diff, $changedLinesDiff);
			for ($i = $olderEnd; $i < $olderLength; $i++) {
				$diff[] = array(self::LINE_PRESERVED, $olderLines[$i]);
			}

			// return the diff
			return $diff;
		}

		/**
		 * Generates the table of the lengths of the longest common subsequences of all prefixes of each of the files.
		 * @param $olderLines Lines of the older file.
		 * @param $newerLines Lines of the newer file.
		 * @return array The LCS table.
		 */
		protected static function generateTable($olderLines, $newerLines) {
			$olderLength = count($olderLines);
			$newerLength = count($newerLines);

			// replace the lines with their hashes, as it's faster to compare them than the actual lines
			for ($i = 0; $i < $olderLength; $i++) {
				$olderLines[$i] = md5($olderLines[$i]);
			}
			for ($i = 0; $i < $newerLength; $i++) {
				$newerLines[$i] = md5($newerLines[$i]);
			}
			
			// prepare the table, fill it with zeroes
			$table = array_fill(0, $olderLength + 1, array_fill(0, $newerLength + 1, 0));

			// iterate over the rows
			for ($row = 1; $row <= $olderLength; $row++) {

				// iterate over the columns
				for ($col = 1; $col <= $newerLength; $col++) {

					// if the lines are the same, they increase the possible LCS length
					if ($olderLines[$row - 1] == $newerLines[$col - 1]) {
						$table[$row][$col] = $table[$row - 1][$col - 1] + 1;
					}
					else {
						$table[$row][$col] = max($table[$row - 1][$col], $table[$row][$col - 1]);
					}
				}
			}

			return $table;
		}

		/**
		 * Generates the list of differences between the lines two of files.
		 * @param $olderLines Lines of the older file.
		 * @param $newerLines Lines of the newer file.
		 * @return array Array with information about the new, deleted and preserved lines between the two files.
		 */
		protected static function generateDiff($olderLines, $newerLines) {
			// generate the LCS table
			$table = self::generateTable($olderLines, $newerLines);

			$diff = [];
			
			$row = count($olderLines);
			$col = count($newerLines);
			
			while ($row > 0 || $col > 0){
				if ($col > 0 && $table[$row][$col] == $table[$row][$col - 1]) {
					$diff[] = array(self::LINE_NEW, $newerLines[$col - 1]);
					$col--;
				}
				else if ($row > 0 && $table[$row][$col] == $table[$row - 1][$col]) {
					$diff[] = array(self::LINE_DELETED, $olderLines[$row - 1]);
					$row--;
				}
				else {
					$diff[] = array(self::LINE_PRESERVED, $olderLines[$row - 1]);
					$row--;
					$col--;
				}
			}
			
			return array_reverse($diff);
		}
		
		/**
		 * Generates an edit script used to transform one file to another.
		 * @param $olderFile Contents of the older file.
		 * @param $newerFile Contents of the newer file.
		 * @return string Contents of the edit script.
		 */
		public static function createDiffScript($olderFile, $newerFile) {
			$diff = self::compare($olderFile, $newerFile);
			
			$diffScript = [];

			$pos = 0;
			$filePos = 0;
			$diffLength = count($diff);
			$toDelete = 0;
			$newLines = [];
			
			while ($pos < $diffLength) {
				if ($diff[$pos][0] == self::LINE_PRESERVED) {
					if ($toDelete > 0) {
						$diffScript[] = $deleteStart.'d'.$toDelete;
						$toDelete = 0;
					}
					else if (count($newLines) > 0) {
						$diffScript[] = $filePos.'a'.count($newLines);
						$diffScript = array_merge($diffScript, $newLines);
						$newLines = [];
					}
					$filePos++;
				}
				else if ($diff[$pos][0] == self::LINE_DELETED) {
					if (count($newLines) > 0) {
						$diffScript[] = $filePos.'a'.count($newLines);
						$diffScript = array_merge($diffScript, $newLines);
						$newLines = [];
					}
					if ($toDelete == 0) {
						$deleteStart = $filePos;
					}
					$toDelete++;
					$filePos++;
				}
				else if ($diff[$pos][0] == self::LINE_NEW) {
					if ($toDelete > 0) {
						$diffScript[] = $deleteStart.'d'.$toDelete;
						$toDelete = 0;
					}
					$newLines[] = $diff[$pos][1];
				}
				$pos++;
			}
			if ($toDelete > 0) {
				$diffScript[] = $deleteStart.'d'.$toDelete;
				$toDelete = 0;
			}
			else if (count($newLines) > 0) {
				$diffScript[] = $filePos.'a'.count($newLines);
				$diffScript = array_merge($diffScript, $newLines);
				$newLines = [];
			}

			return implode("\n", $diffScript);
		}
		
		/**
		 * Applies an edit script to a file and transforms it to another.
		 * @param $oldFile Contents of the original file.
		 * @param $diffScript Contents of the edit script
		 * @return string Contents of new transformed file.
		 */
		public static function applyDiffScript($oldFile, $diffScript) {
			if ($diffScript == '') {
				return $oldFile;
			}
			$diffScript = explode("\n", $diffScript);
			if ($oldFile == '') {
				$oldLines = [];
			}
			else {
				$oldLines = explode("\n", $oldFile);
			}
			
			$newLines = [];

			$oldLength = count($oldLines);
			$diffLength = count($diffScript);

			$oldPos = 0;
			$diffPos = 0;
			$toAppend = 0;
			
			while ($diffPos < $diffLength) {
				$diffLine = $diffScript[$diffPos++];
				if ($toAppend > 0) {
					$newLines[] = $diffLine;
					$toAppend--;
				}
				else {
					$aPos = strpos($diffLine, 'a');
					if ($aPos !== FALSE) {
						$appendAt = intval(substr($diffLine, 0, $aPos));
						$toAppend = intval(substr($diffLine, $aPos + 1));
						while ($oldPos < $appendAt) {
							$newLines[] = $oldLines[$oldPos++];
						}
					}
					else {
						$dPos = strpos($diffLine, 'd');
						if ($dPos !== FALSE) {
							$deleteFrom = intval(substr($diffLine, 0, $dPos));
							$deleteCount = intval(substr($diffLine, $dPos + 1));
							while ($oldPos < $deleteFrom) {
								$newLines[] = $oldLines[$oldPos++];
							}
							$oldPos += $deleteCount;
						}
					}
				}
			}
			
			while ($oldPos < $oldLength) {
				$newLines[] = $oldLines[$oldPos++];
			}
			
			return implode("\n", $newLines);
		}
	}
