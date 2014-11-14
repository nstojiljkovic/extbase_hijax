<?php
namespace EssentialDots\ExtbaseHijax\Service\Serialization;

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
 * Class ListenerFactory
 *
 * @package EssentialDots\ExtbaseHijax\Service\Serialization
 */
class ListenerFactory extends \EssentialDots\ExtbaseHijax\Service\Serialization\AbstractFactory {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Content
	 */
	protected $serviceContent;

	/**
	 * @var array
	 */
	protected $properties = array('configuration', 'id', 'serializedRequest', 'serializedCObj');

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->serviceContent = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Content');
	}

	/**
	 * @param string $listenerId
	 * @return object
	 */
	public function findById($listenerId) {
		$result = NULL;

		if ($listenerId) {
			$object = parent::findById($listenerId);

			if (!$object) {
				list($table, $uid, $rawListenerId) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $listenerId, FALSE, 3);

				// try to generate the listener cache
				if ($table == 'tt_content' && $uid) {
					$object = $this->serviceContent->generateListenerCacheForContentElement($table, $uid);
				} elseif ($table == 'h' || $table == 'hInt') {
					$settingsHash = $uid;
					$encodedSettings = $rawListenerId;
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings) == $settingsHash) {
						$loadContentFromTypoScript = str_replace('---', '.', $encodedSettings);
						$eventsToListen = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('e');
						$object = $this->serviceContent->generateListenerCacheForHijaxPi1($loadContentFromTypoScript, $eventsToListen[$listenerId], $table == 'h');
					}
				}
				if ($table == 'f') {
					$settingsHash = $uid;
					$encodedSettings = $rawListenerId;
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings) == $settingsHash) {
						$fallbackTypoScriptConfiguration = str_replace('---', '.', $encodedSettings);
						$object = $this->serviceContent->generateListenerCacheForTypoScriptFallback($fallbackTypoScriptConfiguration);
					}
				}
			}

			$result = $object;
		}

		return $result;
	}

}