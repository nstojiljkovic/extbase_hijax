<?php
namespace EssentialDots\ExtbaseHijax\HTMLConverter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Essential Dots d.o.o. Belgrade
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

/**
 * Class PDFConverter
 *
 * @package EssentialDots\ExtbaseHijax\HTMLConverter
 */
class PDFConverter extends \EssentialDots\ExtbaseHijax\HTMLConverter\AbstractConverter {

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Response $response
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Response
	 * @throws FailedConversionException
	 */
	public function convert($response) {
		$pathToPdfGenFile = $this->extensionConfiguration->get('pathToPDFGenFile');
		if ($pathToPdfGenFile) {
			list($retVal, $output, $error) = $this->runCommands($pathToPdfGenFile, $response->getContent());
			if ($retVal != 0) {
				error_log('EssentialDots\\ExtbaseHijax\\HTMLConverter\\PDFConverter error: ' . $error);
				/* @var $failedConversionException \EssentialDots\ExtbaseHijax\HTMLConverter\FailedConversionException */
				$failedConversionException = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\HTMLConverter\\FailedConversionException');
				$failedConversionException->setError($error);
				$failedConversionException->setInput($response);
				$failedConversionException->setOutput($output);
				$failedConversionException->setReturnValue($retVal);
				throw $failedConversionException;
			} else {
				$filename = $this->extractTitle($response->getContent());

				$response->setContent($output);
				$response->setHeader('Content-Type', 'application/pdf');

				if ($filename) {
					$fileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
					/* @var $fileFunc \TYPO3\CMS\Core\Utility\File\BasicFileUtility */
					$filename = '; filename = "' . $fileFunc->cleanFileName($filename) . '.pdf"';

					$response->setHeader('Content-Disposition', 'attachment' . $filename);
				} else {
					$response->setHeader('Content-Disposition', 'inline');
				}
			}
		}

		return $response;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function extractTitle($content) {
		preg_match('/<h1([^>]*)>(.*)<\/h1>/msU', $content, $matches);

		return count($matches) ? trim($matches[2]) : '';
	}

	/**
	 * @param string $cmds
	 * @param string $output
	 * @param bool $pipe
	 * @return array
	 */
	protected function runCommands($cmds, $output = '', $pipe = TRUE) {
		if (!is_array($cmds)) {
			$cmds = array($cmds);
		}

		foreach ($cmds as $cmd) {
			$proc = proc_open($cmd,
				array(
					//stdin
					array('pipe', 'r'),
					//stdout
					array('pipe', 'w'),
					//stderr
					array('pipe', 'w')
				),
				$pipes);

			if ($output && $pipe) {
				fwrite($pipes[0], $output);
			}
			fclose($pipes[0]);

			$output = stream_get_contents($pipes[1]);
			$error = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			$retVal = @proc_close($proc);
			if ($error) {
				break;
			}
		}

		return array($retVal, $output, $error);
	}
}