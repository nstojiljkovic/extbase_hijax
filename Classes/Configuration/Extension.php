<?php
namespace EssentialDots\ExtbaseHijax\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
 * Class Extension
 *
 * @package EssentialDots\ExtbaseHijax\Configuration
 */
class Extension implements ExtensionInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extbase_hijax') ?: [];
		$this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
		$this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
	}

	/**
	 * returns configurationvalue for the given key
	 *
	 * @param string $key
	 * @return string
	 */
	public function get($key) {
		return $this->configuration[$key];
	}

	/**
	 * @return boolean
	 */
	public function hasIncludedCssAndJs() {
		return (boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['includedCSSJS'];
	}

	/**
	 * @param boolean $includedCssAndJs
	 * @return void
	 */
	public function setIncludedCssAndJs($includedCssAndJs) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedCSSJS'] = $includedCssAndJs;
	}

	/**
	 * @return boolean
	 */
	public function shouldIncludeEofe() {
		return ((boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['includeEofe'] || (boolean)$GLOBALS['TSFE']->tmpl->setup['config.']['extbase_hijax.']['includeEofe']);
	}

	/**
	 * @param boolean $includeEofe
	 * @return void
	 */
	public function setIncludeEofe($includeEofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includeEofe'] = $includeEofe;
	}

	/**
	 * @return boolean
	 */
	public function hasIncludedEofe() {
		return (boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['includedEofe'];
	}

	/**
	 * @param boolean $includedEofe
	 * @return void
	 */
	public function setIncludedEofe($includedEofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedEofe'] = $includedEofe;
	}

	/**
	 * @return boolean
	 */
	public function shouldIncludeSofe() {
		return ((boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['includeSofe'] || (boolean)$GLOBALS['TSFE']->tmpl->setup['config.']['extbase_hijax.']['includeSofe']);
	}

	/**
	 * @param boolean $includeSofe
	 * @return void
	 */
	public function setIncludeSofe($includeSofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includeSofe'] = $includeSofe;
	}

	/**
	 * @return boolean
	 */
	public function hasIncludedSofe() {
		return (boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['includedSofe'];
	}

	/**
	 * @param boolean $includedSofe
	 * @return void
	 */
	public function setIncludedSofe($includedSofe) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['includedSofe'] = $includedSofe;
	}

	/**
	 * @return boolean
	 */
	public function hasAddedBodyClass() {
		return (boolean)$GLOBALS['TSFE']->config['extbase_hijax.']['addedBodyClass'];
	}

	/**
	 * @param boolean $addedBodyClass
	 * @return void
	 */
	public function setAddedBodyClass($addedBodyClass) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['addedBodyClass'] = $addedBodyClass;
	}

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return $GLOBALS['TSFE']->baseUrl ? $GLOBALS['TSFE']->baseUrl : (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $GLOBALS['TSFE']->absRefPrefix);
	}

	/**
	 * @return string
	 */
	public function getCacheInvalidationLevel() {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		return (string)($frameworkConfiguration['settings']['cacheInvalidationLevel'] ? $frameworkConfiguration['settings']['cacheInvalidationLevel'] : 'noinvalidation');
	}

	/**
	 * @return integer
	 */
	public function getNextElementId() {
		return intval($GLOBALS['TSFE']->config['extbase_hijax.']['nextElementId']);
	}

	/**
	 * @param integer $nextElementId
	 * @return void
	 */
	public function setNextElementId($nextElementId) {
		$GLOBALS['TSFE']->config['extbase_hijax.']['nextElementId'] = $nextElementId;
	}
}