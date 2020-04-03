<?php
namespace EssentialDots\ExtbaseHijax\Event;

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
 * Class Listener
 *
 * @package EssentialDots\ExtbaseHijax\Event
 */
class Listener {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\AutoIDService
	 * @inject
	 */
	protected $autoIdService;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\RequestInterface
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * Constructs a new \EssentialDots\ExtbaseHijax\Event\Listener.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request
	 * @param array $configuration Framework configuraiton
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject An array of parameters
	 */
	public function __construct(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, $configuration = NULL, $contentObject = NULL) {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$this->autoIdService = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\AutoIDService');

		$this->request = $request;

		if ($configuration) {
			$this->configuration = $this->ksortRecursive($configuration);
		} else {
			$this->configuration = $this->ksortRecursive($this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK));
		}

		if ($contentObject) {
			$this->contentObject = $contentObject;
		} else {
			$this->contentObject = $this->configurationManager->getContentObject();
		}

		/* @var $listenerFactory \EssentialDots\ExtbaseHijax\Service\Serialization\ListenerFactory */
		$listenerFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\ListenerFactory');
		// old logic - using autoincrement
		//$this->id = $this->autoIDService->getAutoId(get_class($this));
		// new logic - determine the id based on md5 hash
		// resetting the id so it doesn't affect the hash
		$this->id = '';
		$serialized = $listenerFactory->serialize($this);
		list($table, $uid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->contentObject->currentRecord);
		if ($table == 'tt_content' && $uid) {
			$this->id = str_replace(':', '-', $this->contentObject->currentRecord) . '-' . md5($serialized);
		} else {
			// test if this is ExtbaseHijax Pi1
			if (method_exists($this->request, 'getControllerExtensionName') &&
				method_exists($this->request, 'getPluginName') &&
				$this->request->getControllerExtensionName() == 'ExtbaseHijax' &&
				$this->request->getPluginName() == 'Pi1') {

				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['loadContentFromTypoScript']);
				$settingsHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings);
				if ($this->configuration['switchableControllerActions']['ContentElement'][0] == 'user') {
					$this->id = 'h-' . $settingsHash . '-' . $encodedSettings;
				} else {
					$this->id = 'hInt-' . $settingsHash . '-' . $encodedSettings;
				}
			} elseif ($this->configuration['settings']['fallbackTypoScriptConfiguration']) {
				$encodedSettings = str_replace('.', '---', $this->configuration['settings']['fallbackTypoScriptConfiguration']);
				$settingsHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings);
				$this->id = 'f-' . $settingsHash . '-' . $encodedSettings;
			} else {
				$this->id = md5($serialized);
			}
		}
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @return string
	 */
	public function getSerializedRequest() {
		return $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\RequestFactory')->serialize($this->request);
	}

	/**
	 * @return string
	 */
	public function getSerializedContentObject() {
		/** @var \EssentialDots\ExtbaseHijax\Service\Serialization\CObjFactory $contentObjectFactory */
		$contentObjectFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\CObjFactory');
		return $contentObjectFactory->serialize(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Event\\CObj', $this->contentObject));
	}

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
	 */
	public function getContentObject() {
		return $this->contentObject;
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}

	/**
	 * @param string $request
	 */
	public function setSerializedRequest($request) {
		$this->request = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\RequestFactory')->unserialize($request);
	}

	/**
	 * @param string $contentObject
	 */
	public function setSerializedContentObject($contentObject) {
		/* @var $eventContentObject \EssentialDots\ExtbaseHijax\Event\CObj */
		$eventContentObject = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\CObjFactory')->unserialize($contentObject);
		$eventContentObject->reconstitute();
		$this->contentObject = $eventContentObject->getContentObject();
	}

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 */
	public function setContentObject($contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @param multitype : $configuration
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $this->ksortRecursive($configuration);
	}

	/**
	 * @param number $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @param array $array
	 * @return array
	 */
	protected function ksortRecursive(array $array) {
		foreach ($array as $key => $nestedArray) {
			if (is_array($nestedArray) && !empty($nestedArray)) {
				$array[$key] = $this->ksortRecursive($nestedArray);
			}
		}

		ksort($array);

		return $array;
	}
}