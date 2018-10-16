<?php
namespace Tx\Webkitpdf\Generator;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Interface for PDF generators.
 */
class BackgroundPdfGenerator extends AbstractPdfGenerator implements PdfGeneratorInterface {

	/**
	 * The default options for this PDF generator.
	 *
	 * @var array
	 */
	protected $generatorOptions = array(
		'logFile' => '',
		'waitTimeInSeconds' => 10
	);

	/**
	 * Runs the given PDF generation command in the background and writes the ouput to a log file.
	 * If the process does not stop after a configurable wait time the process is killed and an Exeption is thrown.
	 *
	 * @param string $scriptCall
	 * @return void
	 */
	protected function dispatchPdfGeneration($scriptCall) {

		$logFile = empty($this->generatorOptions['logFile']) ? '' : GeneralUtility::getFileAbsFileName($this->generatorOptions['logFile']);

		if ($logFile === '') {
			$logFile = '/dev/null';
		} else {
			$logDir = dirname($logFile);
			if (!@is_dir($logDir)) {
				GeneralUtility::mkdir_deep($logDir);
			}
		}

		$scriptCall .= ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
		$output = array();
		exec($scriptCall, $output);
		$processId = isset($output[0]) ? (int)$output[0] : 0;

		if ($processId === 0) {
			throw new \RuntimeException('Process ID of PDF generator could not be determined.');
		}

		$this->pdfUtility->debugLogging('Executed shell command in background with process ID ' . $processId, -1, array($scriptCall));

		$waitTimeInSeconds = isset($this->generatorOptions['waitTimeInSeconds']) ? (int)$this->generatorOptions['waitTimeInSeconds'] : 0;
		if ($waitTimeInSeconds === 0) {
			$waitTimeInSeconds = 10;
		}

		$secondsWaited = 0;
		while ($this->processIsRunning($processId)) {
			sleep(1);
			$secondsWaited++;
			if ($secondsWaited > $waitTimeInSeconds) {
				exec('kill ' . $processId);
				throw new \RuntimeException('PDF generation did not finish in a reasonable amount of time' . ($this->isDebugEnabled ? ': ' . $scriptCall : '.'));
			}
		}
	}

	/**
	 * Checks if the process with the given ID is still running.
	 *
	 * @param int $processId
	 * @return bool
	 */
	protected function processIsRunning($processId) {

		$psCmd = CommandUtility::getCommand('ps');

		if (!$psCmd) {
			throw new \RuntimeException('The path to ps could not be determined. Please check your path configuration.', 1438182630);
		}

		$result = shell_exec($psCmd . ' ' . $processId);

		if (count(preg_split("/\n/", $result)) > 2) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}