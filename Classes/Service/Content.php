<?php
namespace EssentialDots\ExtbaseHijax\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class Content
 *
 * @package EssentialDots\ExtbaseHijax\Service
 */
class Content implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	protected $absRefPrefix;

	/**
	 * @var
	 */
	protected $absRefPrefixCallbackAttribute;

	/**
	 * @var boolean
	 */
	protected $executeExtbasePlugins = TRUE;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	protected $currentListener;

	/**
	 * @return bool
	 */
	public function getExecuteExtbasePlugins() {
		return $this->executeExtbasePlugins;
	}

	/**
	 * @param boolean $executeExtbasePlugins
	 */
	public function setExecuteExtbasePlugins($executeExtbasePlugins) {
		$this->executeExtbasePlugins = $executeExtbasePlugins;
	}

	/**
	 * @return \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	public function getCurrentListener() {
		return $this->currentListener;
	}

	/**
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $currentListener
	 */
	public function setCurrentListener($currentListener) {
		$this->currentListener = $currentListener;
	}

	/**
	 * @param string $table
	 * @param int $uid
	 *
	 * @return \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	public function generateListenerCacheForContentElement($table, $uid) {
		/* @var $contentObject \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		// TODO: implement language overlay functions
		if (!$GLOBALS['TSFE']) {
			$ajaxDispatcher = GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Utility\\Ajax\\Dispatcher');
			/* @var $ajaxDispatcher \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher */
			$ajaxDispatcher->initialize();
		}
		$data = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
		if ($data) {
			// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$contentObject->start($data, $table);
			$dummyContent = $contentObject->cObjGetSingle('RECORDS', array(
				'source' => $uid,
				'tables' => $table
			));
			$this->processIntScripts($dummyContent);
			// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = NULL;

		return $listener;
	}

	/**
	 *
	 * @param string $loadContentFromTypoScript
	 * @param string $eventsToListen
	 * @param boolean $cached
	 *
	 * @return \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	public function generateListenerCacheForHijaxPi1($loadContentFromTypoScript, $eventsToListen, $cached) {
		/* @var $contentObject \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		if ($loadContentFromTypoScript) {
			// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$dummyContent = $contentObject->cObjGetSingle('USER', array(
				'extensionName' => 'ExtbaseHijax',
				'pluginName' => 'Pi1',
				'userFunc' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run',
				'switchableControllerActions.' => array(
					'ContentElement.' => array('0' => $cached ? 'user' : 'userInt')
				),
				'settings.' => array(
					'listenOnEvents' => implode(',', $eventsToListen),
					'loadContentFromTypoScript' => $loadContentFromTypoScript
				)
			));
			$this->processIntScripts($dummyContent);
			// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = NULL;

		return $listener;
	}

	/**
	 * @param $fallbackTypoScriptConfiguration
	 * @return \EssentialDots\ExtbaseHijax\Event\Listener
	 * @throws \Exception
	 */
	public function generateListenerCacheForTypoScriptFallback($fallbackTypoScriptConfiguration) {

		if ($fallbackTypoScriptConfiguration) {

			// make sure that the actual controller action IS NOT executed
			$this->setExecuteExtbasePlugins(FALSE);
			$dummyContent = $this->renderTypoScriptPath($fallbackTypoScriptConfiguration);
			$this->processIntScripts($dummyContent);
			// make sure that any following controller action IS executed
			$this->setExecuteExtbasePlugins(TRUE);
			$listener = $this->currentListener;
		}
		$this->currentListener = NULL;

		return $listener;
	}

	/**
	 * Processes INT scripts
	 *
	 * @param string $content
	 */
	public function processIntScripts(&$content) {
		$tsfe = &$GLOBALS['TSFE'];
		/* @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		// @extensionScannerIgnoreLine
		$tsfe->content = $content;
		if (!$tsfe->config['INTincScript']) {
			$tsfe->config['INTincScript'] = array();
		}
		$tsfe->INTincScript();
		// @extensionScannerIgnoreLine
		$content = $tsfe->content;
	}

	/**
	 * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
	 *
	 * @param string $content
	 * @param string $absRefPrefix
	 * @return    void
	 */
	public function processAbsRefPrefix(&$content, $absRefPrefix) {
		if ($absRefPrefix) {
			$this->absRefPrefix = $absRefPrefix;
			$this->absRefPrefixCallbackAttribute = 'href';
			$content = preg_replace_callback('/\shref="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);
			$this->absRefPrefixCallbackAttribute = 'src';
			$content = preg_replace_callback('/\ssrc="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);
			$this->absRefPrefixCallbackAttribute = 'action';
			$content = preg_replace_callback('/\saction="(?P<url>[^"].*)"/msU', array($this, 'processAbsRefPrefixCallback'), $content);

			// TYPO3 6.0 code compatibility
			$content = str_replace('"typo3temp/', '"' . $this->absRefPrefix . 'typo3temp/', $content);
			$content = str_replace('"typo3conf/ext/', '"' . $this->absRefPrefix . 'typo3conf/ext/', $content);
			$content = str_replace('"' . TYPO3_mainDir . 'contrib/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'contrib/', $content);
			$content = str_replace('"' . TYPO3_mainDir . 'ext/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'ext/', $content);
			$content = str_replace('"' . TYPO3_mainDir . 'sysext/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'sysext/', $content);
			$content = str_replace('"' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '"' . $this->absRefPrefix . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], $content);
			$content = str_replace('"' . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], '"' . $this->absRefPrefix . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], $content);
			// Process additional directories
			$directories = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'], TRUE);
			foreach ($directories as $directory) {
				$content = str_replace('"' . $directory, '"' . $this->absRefPrefix . $directory, $content);
			}
		}
	}

	/**
	 * @param array $match
	 * @return string
	 */
	protected function processAbsRefPrefixCallback($match) {

		$url = $match['url'];
		$urlInfo = parse_url($url);
		if (!$urlInfo['scheme']) {
			if (substr($url, 0, strlen($this->absRefPrefix)) == $this->absRefPrefix) {
				// don't change the URL
				// it already starts with absRefPrefix
				$result = $match[0];
			} else {
				$result = ' ' . $this->absRefPrefixCallbackAttribute . ' = "' . $this->absRefPrefix . $url . '"';
			}
		} else {
			// don't change the URL
			// it has scheme so we assume it's full URL
			$result = $match[0];
		}

		return $result;
	}

	/**
	 * @param $typoscriptObjectPath
	 * @return string
	 * @throws \Exception
	 */
	protected function renderTypoScriptPath($typoscriptObjectPath) {
		/* @var $contentObject \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		if (is_null($GLOBALS['TSFE'])) {
			$hijaxDispatcher = GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Utility\\Ajax\\Dispatcher');
			/** @var $hijaxDispatcher \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher */
			$hijaxDispatcher->initialize();
		}
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				throw new \Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist', 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		return $contentObject->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.']);
	}

	/**
	 * @param string $typoscriptObjectPath
	 * @return mixed
	 * @throws \Exception
	 */
	public function isAllowedTypoScriptPath($typoscriptObjectPath) {
		/* @var $contentObject \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				throw new \Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist', 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		return $setup[$lastSegment . '.']['enableHijax'];
	}
}