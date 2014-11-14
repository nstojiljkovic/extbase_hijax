<?php
namespace EssentialDots\ExtbaseHijax\MVC;

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
 * Class Dispatcher
 *
 * @package EssentialDots\ExtbaseHijax\MVC
 */
class Dispatcher extends \TYPO3\CMS\Extbase\Mvc\Dispatcher {
	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 */
	protected $hijaxEventDispatcher;

	/**
	 * Extension Configuration
	 *
	 * @var \EssentialDots\ExtbaseHijax\Configuration\ExtensionInterface
	 */
	protected $extensionConfiguration;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	protected $currentListener;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\RequestInterface
	 */
	protected $currentRequest;

	/**
	 * @var array
	 */
	protected $requestsStack;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Serialization\ListenerFactory
	 */
	protected $listenerFactory;

	/**
	 * @var int
	 */
	protected static $id = 0;

	/**
	 * @var array
	 */
	protected $listenersStack;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher
	 */
	protected $ajaxDispatcher;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Content
	 */
	protected $serviceContent;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		parent::__construct($objectManager);
		$this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$this->hijaxEventDispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Event\\Dispatcher');
		$this->ajaxDispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Utility\\Ajax\\Dispatcher');
		$this->extensionConfiguration = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Configuration\\ExtensionInterface');
		$this->listenerFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\ListenerFactory');
		$this->serviceContent = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Content');
		self::$id = $this->extensionConfiguration->getNextElementId();
		$this->listenersStack = array();
		$this->requestsStack = array();
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, to be modified by the controller
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $listener Listener
	 * @return void
	 */
	public function dispatch(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response, \EssentialDots\ExtbaseHijax\Event\Listener $listener = NULL) {
		/* @var $request \TYPO3\CMS\Extbase\Mvc\Request */
		$this->currentRequest = $request;
		array_push($this->requestsStack, $this->currentRequest);

		if (defined('TYPO3_cliMode') && TYPO3_cliMode === TRUE) {
			parent::dispatch($request, $response);
		} else {
			array_push($this->listenersStack, $this->currentListener);
			if ($listener) {
				$this->currentListener = $listener;
			} else {
				$this->currentListener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Event\\Listener', $request);
			}

			if (!$this->serviceContent->getExecuteExtbasePlugins()) {
				$this->listenerFactory->persist($this->currentListener);
				$this->serviceContent->setCurrentListener($this->currentListener);
			} else {
				$this->hijaxEventDispatcher->startContentElement();

				try {
					parent::dispatch($request, $response);
				} catch (\TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException $requiredArgumentMissingException) {
					try {
						// this happens with simple reload on pages where some argument is required
						$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

						$defaultControllerName = current(array_keys($configuration['controllerConfiguration']));

						$allowedControllerActions = array();
						foreach ($configuration['controllerConfiguration'] as $controllerName => $controllerActions) {
							$allowedControllerActions[$controllerName] = $controllerActions['actions'];
						}
						$defaultActionName = is_array($allowedControllerActions[$request->getControllerName()]) ? current($allowedControllerActions[$request->getControllerName()]) : '';

						// try to run the current controller with the default action
						$request->setDispatched(FALSE);
						$request->setControllerActionName($defaultActionName);

						parent::dispatch($request, $response);

					} catch (\TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException $requiredArgumentMissingException) {
						if ($defaultControllerName != $request->getControllerName()) {
							$request->setControllerName($defaultControllerName);
							$defaultActionName = is_array($allowedControllerActions[$defaultControllerName]) ? current($allowedControllerActions[$defaultControllerName]) : '';

							// try to run the default plugin controller with the default action
							$request->setDispatched(FALSE);
							$request->setControllerActionName($defaultActionName);
							parent::dispatch($request, $response);
						}
					}
				}

				if ($this->hijaxEventDispatcher->getIsHijaxElement()) {
					$this->listenerFactory->persist($this->currentListener);
				}

				if (($this->ajaxDispatcher->getIsActive() || $this->hijaxEventDispatcher->getIsHijaxElement()) && !$this->ajaxDispatcher->getPreventMarkupUpdateOnAjaxLoad()) {

					$currentListeners = $this->hijaxEventDispatcher->getListeners('', TRUE);

					$signature = $this->getCurrentListener()->getId() . '(' . $this->convertArrayToCsv(array_keys($currentListeners)) . '); ';

					$content = $response->getContent();

					$content = '<!-- ###EVENT_LISTENER_' . self::$id . '### START ' . $signature . ' -->' . $content . '<!-- ###EVENT_LISTENER_' . self::$id . '### END -->';

					if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('eID') && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('eID') != 'extbase_hijax_dispatcher') {
						$this->hijaxEventDispatcher->replaceXmlCommentsWithDivs($content, 'html');
					}
					$response->setContent($content);

					$this->extensionConfiguration->setNextElementId(++self::$id);
				}

				$this->hijaxEventDispatcher->endContentElement();
			}
			$this->currentListener = array_pop($this->listenersStack);
		}

		$this->currentRequest = array_pop($this->requestsStack);
	}

	/**
	 * @return \EssentialDots\ExtbaseHijax\Event\Listener
	 */
	public function getCurrentListener() {
		return $this->currentListener;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\Request
	 */
	public function getCurrentRequest() {
		return $this->currentRequest;
	}

	/**
	 * @param array $data
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return string
	 */
	protected function convertArrayToCsv($data, $delimiter = ',', $enclosure = '"') {
		$outstream = fopen('php://temp', 'r+');
		fputcsv($outstream, $data, $delimiter, $enclosure);
		rewind($outstream);
		$csv = fgets($outstream);
		fclose($outstream);
		return trim($csv);
	}

}