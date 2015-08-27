<?php
namespace EssentialDots\ExtbaseHijax\Session;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Essential Dots d.o.o. Belgrade
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractSession
 *
 * @package EssentialDots\ExtbaseHijax\Session
 */
abstract class AbstractSession implements SingletonInterface {

	/**
	 * @var AbstractSession
	 */
	protected static $singletonInstance;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');

		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');
		$this->logger = $logManager->getLogger(get_class($this));
	}

	/**
	 * @return AbstractSession|object
	 */
	public static function getInstance() {
		if (!self::$singletonInstance) {
			self::$singletonInstance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\Session\\AbstractSession');
		}

		return self::$singletonInstance;
	}

	/**
	 * @return void
	 */
	public function destroy() {
		self::$singletonInstance = NULL;
	}

	/**
	 * @return string
	 */
	abstract public function getId();

	/**
	 * @param string $key
	 * @return mixed
	 */
	abstract public function get($key);

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	abstract public function set($key, $value);

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	abstract public function setIfNotExist($key, $value);

	/**
	 * @return bool
	 */
	abstract public function start();

	/**
	 * @return bool
	 */
	abstract public function commit();
}