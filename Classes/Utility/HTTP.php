<?php
namespace EssentialDots\ExtbaseHijax\Utility;

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
 * Class HTTP
 *
 * @package EssentialDots\ExtbaseHijax\Utility
 */
class HTTP implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var int
	 */
	protected static $loopCount = 0;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * ajaxDispatcher
	 *
	 * @var \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher
	 */
	protected $ajaxDispatcher;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 */
	protected $hijaxEventDispatcher;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initializeObjectManager();
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 */
	protected function initializeObjectManager() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->ajaxDispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Utility\\Ajax\\Dispatcher');
		$this->hijaxEventDispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Event\\Dispatcher');
	}

	/**
	 * Sends a redirect header response and exits. Additionaly the URL is
	 * checked and if needed corrected to match the format required for a
	 * Location redirect header. By default the HTTP status code sent is
	 * a 'HTTP/1.1 303 See Other'.
	 *
	 * @param string $url The target URL to redirect to
	 * @param string $httpStatus An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
	 * @return void
	 */
	public static function redirect($url, $httpStatus = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303) {
		/* @var $httpServiceInstance \EssentialDots\ExtbaseHijax\Utility\HTTP */
		$httpServiceInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Utility\\HTTP');
		$httpServiceInstance->redirectInstance($url, $httpStatus);
	}

	/**
	 * Sends a redirect header response and exits. Additionaly the URL is
	 * checked and if needed corrected to match the format required for a
	 * Location redirect header. By default the HTTP status code sent is
	 * a 'HTTP/1.1 303 See Other'.
	 *
	 * @param    string $url The target URL to redirect to
	 * @param    string $httpStatus An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
	 * @throws \EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction
	 * @return void
	 */
	protected function redirectInstance($url, $httpStatus = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303) {
		if ($this->ajaxDispatcher->getIsActive()) {
			/* @var $redirectException \EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction */
			$redirectException = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\MVC\\Exception\\RedirectAction');
			$redirectException->setUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url));
			$redirectException->setHttpStatus($httpStatus);
			throw $redirectException;
		} else {
			header($httpStatus);
			header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url));

			exit;
		}
	}
}
