<?php
namespace EssentialDots\ExtbaseHijax\Utility\Ajax;

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
use EssentialDots\ExtbaseHijax\Event\Listener;
use EssentialDots\ExtbaseHijax\MVC\Controller\ArgumentsManager;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An AJAX dispatcher.
 */
class Dispatcher implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var int
	 */
	protected static $loopCount = 0;

	/**
	 * @var bool
	 */
	protected $isActive = FALSE;

	/**
	 * Array of all request Arguments
	 *
	 * @var array
	 */
	protected $requestArguments = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $cacheInstance;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 */
	protected $hijaxEventDispatcher;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Serialization\ListenerFactory
	 */
	protected $listenerFactory;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Content
	 */
	protected $serviceContent;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 */
	protected $extensionService;

	/**
	 * @var boolean
	 */
	protected $preventMarkupUpdateOnAjaxLoad = FALSE;

	/**
	 * @var boolean
	 */
	protected $preventHistoryPush = FALSE;

	/**
	 * @var \EssentialDots\EdCache\Domain\Repository\CacheRepository
	 */
	protected $cacheRepository;

	/**
	 * @var boolean
	 */
	protected $initializedTypoScriptFrontEnd = FALSE;

	/**
	 * @var boolean
	 */
	protected $errorWhileConverting = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->hijaxEventDispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Event\\Dispatcher');
		$this->serviceContent = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Content');
		$this->listenerFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\ListenerFactory');
		$this->extensionService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\ExtensionService');
		/** @var CacheManager $cacheManager */
		$cacheManager = $GLOBALS['typo3CacheManager'] ?: GeneralUtility::makeInstance(CacheManager::class);
		$this->cacheInstance = $cacheManager->getCache('extbase_hijax_storage');
	}

	/**
	 * @return \EssentialDots\EdCache\Domain\Repository\CacheRepository
	 */
	protected function getCacheRepository() {
		if ($this->cacheRepository != NULL) {
			return $this->cacheRepository;
		}

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ed_cache')) {
			$this->cacheRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(CacheRepository::class);
		}
		return $this->cacheRepository;
	}

	/**
	 * Called by ajax.php / eID.php
	 * Builds an extbase context and returns the response.
	 *
	 * @return void
	 */
	public function dispatch() {
		$this->setIsActive(TRUE);
		$callback = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('callback');
		$requests = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('r');
		$eventsToListen = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('evts') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('evts') : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('e');
		$preventDirectOutput = FALSE;
		$preventJsonEncode = FALSE;
		try {
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();

			$responses = array(
				'original' => array(),
				'affected' => array(),
			);

			foreach ($requests as $r) {

				if ($r['secureLocalStorage']) {
					echo file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase_hijax', 'Resources/Private/Templates/SecureLocalStorage/IFrame.html'));
					exit;
				}

				$skipProcessing = FALSE;
				$configuration = array();

				$allowCaching = FALSE;
				if ($r['chash']) {
					/* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
					$cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
					$allowCaching = $r['chash'] == $cacheHash->calculateCacheHash(array(
							'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
							'action' => $r['action'],
							'controller' => $r['controller'],
							'extension' => $r['extension'],
							'plugin' => $r['plugin'],
							'arguments' => $r['arguments'],
							'settingsHash' => $r['settingsHash']
						));
				}

				if ($r['tsSource']) {
					$this->initialize();
					if ($this->serviceContent->isAllowedTypoScriptPath($r['tsSource'])) {
						/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
						$encodedSettings = str_replace('.', '---', $r['tsSource']);
						$settingsHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($encodedSettings);
						$listener = $this->listenerFactory->findById('h-' . $settingsHash . '-' . $encodedSettings);
						$configuration = $listener->getConfiguration();
						$r['extension'] = $configuration['extensionName'];
						$r['plugin'] = $configuration['pluginName'];
						$r['controller'] = $configuration['controller'];
						$r['action'] = $configuration['action'];
					} else {
						throw new \Exception('Path not allowed.', 503);
					}
				} elseif ($r['settingsHash']) {
					/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
					$listener = $this->listenerFactory->findById($r['settingsHash']);
				}

				$bootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');

				// load settings saved under settingsHash
				if ($listener) {
					/** @var Listener $listener */
					$configuration = $listener->getConfiguration();
					$request = $listener->getRequest();
					// @extensionScannerIgnoreLine
					$bootstrap->cObj = $listener->getContentObject();
					$this->checkAllowedControllerActions($configuration, $r);
				} else {
					$skipProcessing = TRUE;
				}

				if (!$skipProcessing) {
					if ($allowCaching && $this->getCacheRepository()) {
						$cacheConf = array(
							'contentFunc' => array($this, 'handleFrontendRequest'),
							'contentFuncParams' => array(
								$bootstrap,
								$configuration,
								$r,
								$request,
								$listener,
								TRUE
							)
						);
						if ($configuration['settings']['extbaseHijaxDefaultCacheExpiryPeriod']) {
							$cacheConf['expire_on_datetime'] = $GLOBALS['EXEC_TIME'] + $configuration['settings']['extbaseHijaxDefaultCacheExpiryPeriod'];
						}
						$cachedResponse = $this->getCacheRepository()->getByKey('hijax_' . $r['chash'], $cacheConf, $bootstrap->cObj);
						$cachedResponse['id'] = $r['id'];
						$responses['original'][] = $cachedResponse;
					} else {
						$responses['original'][] = $this->handleFrontendRequest($bootstrap, $configuration, $r, $request, $listener, FALSE);
					}
				}
			}

			// see if there are affected elements on the page as well
			// and run their code generation again
			$this->parseAndRunEventListeners($responses, $eventsToListen, FALSE);

			while ($this->hijaxEventDispatcher->hasPendingNextPhaseEvents()) {
				$this->hijaxEventDispatcher->promoteNextPhaseEvents();
				$this->parseAndRunEventListeners($responses, $eventsToListen);

				if (self::$loopCount++ > 99) {
					// preventing dead loops
					break;
				}
			}

			foreach ($responses['original'] as $i => $_) {
				$this->hijaxEventDispatcher->replaceXmlCommentsWithDivs($responses['original'][$i]['response'], $responses['original'][$i]['format']);
				if ($responses['original'][$i]['format'] == 'json') {
					// yes, non-optimal, but no time for now to change the extbase core...
					$responses['original'][$i]['response'] = json_decode($responses['original'][$i]['response']);
				}
			}
			foreach ($responses['affected'] as $i => $_) {
				$this->hijaxEventDispatcher->replaceXmlCommentsWithDivs($responses['affected'][$i]['response'], $responses['affected'][$i]['format']);
				if ($responses['affected'][$i]['format'] == 'json') {
					// yes, non-optimal, but no time for now to change the extbase core...
					$responses['affected'][$i]['response'] = json_decode($responses['affected'][$i]['response']);
				}
			}

			$this->cleanShutDown();
		} catch (\EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction $redirectException) {
			$responses = array(
				'redirect' => array(
					'url' => $redirectException->getUrl(),
					'code' => $redirectException->getHttpStatus()
				)
			);
			$preventDirectOutput = TRUE;
		} catch (\EssentialDots\ExtbaseHijax\MVC\Exception\OutputRawDataAction $outputRawDataException) {
			header('Content-type: ' . $outputRawDataException->getContentType());
			$responses['original'][0]['response'] = $outputRawDataException->getMessage();

			$preventJsonEncode = TRUE;
			$preventDirectOutput = TRUE;
		} catch (\Exception $e) {
			header('HTTP/1.1 503 Service Unavailable');
			header('Status: 503 Service Unavailable');

			error_log($e->getMessage());
			$responses = array('success' => FALSE, 'code' => $e->getCode());
		}

		/** @var ArgumentsManager $argumentsManager */
		$argumentsManager = GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\MVC\\Controller\\ArgumentsManager');
		$responses['validation-errors'] = $argumentsManager->hasErrors();
		if ($this->getPreventHistoryPush()) {
			$responses['prevent-state-push'] = TRUE;
		}

		if (!$preventDirectOutput && $responses['original'][0]['format'] != 'html' && is_string($responses['original'][0]['response'])) {
			foreach ($responses['original'][0]['headers'] as $header) {
				header(trim($header));
			}
			header('Cache-control: private');
			header('Connection: Keep-Alive');
			header('Content-Length: ' . strlen($responses['original'][0]['response']));
			echo $responses['original'][0]['response'];
		} elseif ($callback) {
			header('Content-type: text/javascript');
			echo $callback . '(' . json_encode($responses) . ')';
		} elseif (!$preventJsonEncode) {
			header('Content-type: application/x-json');
			echo json_encode($responses);
		} else {
			echo $responses['original'][0]['response'];
		}

		$this->setIsActive(FALSE);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Core\Bootstrap $bootstrap
	 * @param array $configuration
	 * @param array $r
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Request $request
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $listener
	 * @param bool $isCacheCallback
	 * @return array
	 * @throws \EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
	 */
	public function handleFrontendRequest($bootstrap, $configuration, $r, $request, $listener, $isCacheCallback = FALSE) {
		$this->initialize();

		$bootstrap->initialize($configuration);
		$this->setPreventMarkupUpdateOnAjaxLoad(FALSE);
		/* @var $request \TYPO3\CMS\Extbase\Mvc\Web\Request */
		$request = $this->buildRequest($r, $request);
		$request->setDispatched(FALSE);

		$namespace = $this->extensionService->getPluginNamespace($request->getControllerExtensionName(), $request->getPluginName());
		// @codingStandardsIgnoreStart
		$_POST[$namespace] = $request->getArguments();
		// @codingStandardsIgnoreEnd

		/* @var $response \TYPO3\CMS\Extbase\Mvc\Web\Response */
		$response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');

		/* @var $dispatcher \EssentialDots\ExtbaseHijax\MVC\Dispatcher */
		$dispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher');
		$dispatcher->dispatch($request, $response, $listener);
		$this->parseHeaders($response);

		// @codingStandardsIgnoreStart
		$_POST[$namespace] = array();
		// @codingStandardsIgnoreEnd

		$content = $response->getContent();
		$this->serviceContent->processIntScripts($content);
		$this->serviceContent->processAbsRefPrefix($content, $configuration['settings']['absRefPrefix']);
		$response->setContent($content);

		// convert HTML to specified format
		$htmlConverter = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\HTMLConverter\\ConverterFactory');
		/* @var $htmlConverter \EssentialDots\ExtbaseHijax\HTMLConverter\ConverterFactory */
		$converter = $htmlConverter->getConverter($request->getFormat());

		try {
			$response = $converter->convert($response);

			/* @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
			$tsfe = &$GLOBALS['TSFE'];
			// @extensionScannerIgnoreLine
			$tsfe->content = $response->getContent();

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'])) {
				$params = array('pObj' => $tsfe);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] as $funcRef) {
					GeneralUtility::callUserFunction($funcRef, $params, $tsfe);
				}
			}

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'])) {
				$params = array('pObj' => $tsfe);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'] as $funcRef) {
					GeneralUtility::callUserFunction($funcRef, $params, $tsfe);
				}
			}

			// @extensionScannerIgnoreLine
			$response->setContent($tsfe->content);
		} catch (\EssentialDots\ExtbaseHijax\HTMLConverter\FailedConversionException $e) {
			$this->errorWhileConverting = TRUE;
		}

		$result = array(
			'id' => $r['id'],
			'format' => $request->getFormat(),
			'response' => $response->getContent(),
			'preventMarkupUpdate' => $this->getPreventMarkupUpdateOnAjaxLoad(),
			'headers' => $response->getHeaders());

		if (!$this->errorWhileConverting && $isCacheCallback && !$request->isCached() && $this->getCacheRepository()) {
			error_log('Throwing EssentialDots\\EdCache\\Exception\\PreventActionCaching, did you missconfigure cacheable actions in Extbase?');
			/* @var $preventActionCaching \EssentialDots\EdCache\Exception\PreventActionCaching */
			$preventActionCaching = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\EssentialDots\EdCache\Exception\PreventActionCaching::class);
			$preventActionCaching->setResult($result);
			throw $preventActionCaching;
		}

		return $result;
	}

	/**
	 * @param $responses
	 * @param $eventsToListen
	 * @param bool $processOriginal
	 * @throws \EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 */
	protected function parseAndRunEventListeners(&$responses, $eventsToListen, $processOriginal = TRUE) {
		if ($processOriginal) {
			foreach ($responses['original'] as $response) {
				$this->hijaxEventDispatcher->parseAndRunEventListeners($response['response']);
			}
		}
		if ($eventsToListen && is_array($eventsToListen)) {
			foreach ($eventsToListen as $listenerId => $eventNames) {
				$shouldProcess = FALSE;
				foreach ($eventNames as $eventName) {
					if ($this->hijaxEventDispatcher->hasPendingEventWithName($eventName, $listenerId)) {
						$shouldProcess = TRUE;
						break;
					}
				}

				if ($shouldProcess) {
					/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
					$listener = $this->listenerFactory->findById($listenerId);

					if ($listener) {
						$configuration = $listener->getConfiguration();
						$bootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
						// @extensionScannerIgnoreLine
						$bootstrap->cObj = $listener->getContentObject();
						$bootstrap->initialize($configuration);

						/* @var $request \TYPO3\CMS\Extbase\Mvc\Web\Request */
						$request = $listener->getRequest();
						$request->setDispatched(FALSE);
						$this->setPreventMarkupUpdateOnAjaxLoad(FALSE);

						/* @var $response \TYPO3\CMS\Extbase\Mvc\Web\Response */
						$response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');

						/* @var $dispatcher \EssentialDots\ExtbaseHijax\MVC\Dispatcher */
						$dispatcher = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher');
						try {
							$dispatcher->dispatch($request, $response, $listener);
							$this->parseHeaders($response);

							$content = $response->getContent();
							$this->serviceContent->processIntScripts($content);
							$this->serviceContent->processAbsRefPrefix($content, $configuration['settings']['absRefPrefix']);
							$responses['affected'][] = array('id' => $listenerId, 'format' => $request->getFormat(), 'response' => $content, 'preventMarkupUpdate' => $this->getPreventMarkupUpdateOnAjaxLoad());
						} catch (\EssentialDots\ExtbaseHijax\MVC\Exception\StopProcessingAction $exception) {
							$this->parseHeaders($response);

						}
					//} else {
						// TODO: log error message
					}
				}
			}
		}
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Response $response
	 * @throws \EssentialDots\ExtbaseHijax\MVC\Exception\RedirectAction
	 */
	protected function parseHeaders($response) {
		// detect redirects
		if (method_exists($response, 'getHeaders')) {
			foreach ($response->getHeaders() as $header) {
				$matches = array();
				preg_match('/Location: (.*)/ms', $header, $matches);
				if (count($matches) > 0) {
					$url = trim($matches[1]);
					// no need to set any other status than 303?
					\EssentialDots\ExtbaseHijax\Utility\HTTP::redirect($url, \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);
				}

			}
		}
	}

	/**
	 * initialize TSFE and TCA
	 */
	public function initialize() {
		if (!$this->initializedTypoScriptFrontEnd) {
			$this->initializedTypoScriptFrontEnd = TRUE;
			$this->initializeTsfe();
		}
	}

	/**
	 * Initializes TSFE.
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
	 * @throws \TYPO3\CMS\Core\Http\ImmediateResponseException
	 */
	protected function initializeTsfe() {
		/* @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
			$GLOBALS['TYPO3_CONF_VARS'],
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type'));
		$GLOBALS['TSFE'] = &$tsfe;

		// @extensionScannerIgnoreLine
		$tsfe->initFEuser();
		$tsfe->initUserGroups();
		// @extensionScannerIgnoreLine
		$tsfe->checkAlternativeIdMethods();
		$tsfe->determineId();
		$tsfe->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$tsfe->getConfigArray();
		$tsfe->settingLanguage();
		$tsfe->settingLocale();
		$tsfe->calculateLinkVars();
		$tsfe->newCObj();
		$tsfe->preparePageContentGeneration();
	}

	/**
	 * Shuts down services and persists objects.
	 *
	 * @return void
	 */
	protected function cleanShutDown() {
		$this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
		$this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService')->shutdown();
	}

	/**
	 * Build a request object
	 *
	 * @param array $r
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Request $request
	 * @return \TYPO3\CMS\Extbase\Mvc\Request
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
	 */
	protected function buildRequest($r, &$request = NULL) {

		if (!$request) {
			$request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		}

		$request->setControllerExtensionName($r['extension']);
		$request->setPluginName($r['plugin']);
		$request->setFormat($r['format'] ? $r['format'] : 'html');
		$request->setControllerName($r['controller']);
		$request->setControllerActionName($r['action']);
		if ($r['vendor']) {
			$request->setControllerVendorName($r['vendor']);
		}
		if ($r['arguments'] && !is_array($r['arguments'])) {
			$r['arguments'] = unserialize($r['arguments']);
			$this->stringify($r['arguments']);
		}

		$args = $request->getArguments();
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($args, !is_array($r['arguments']) ? array() : $r['arguments']);
		$request->setArguments($args);

		return $request;
	}

	/**
	 * @param $arr
	 */
	protected function stringify(&$arr) {
		if (is_array($arr)) {
			foreach ($arr as $k => $v) {
				if (!is_array($v) && !is_object($v) && !is_null($v)) {
					$arr[$k] = (string)$v;
				} else {
					$this->stringify($arr[$k]);
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function getIsActive() {
		return $this->isActive;
	}

	/**
	 * @param boolean $isActive
	 */
	protected function setIsActive($isActive) {
		$this->isActive = $isActive;
	}

	/**
	 * @return bool
	 */
	public function getPreventMarkupUpdateOnAjaxLoad() {
		return $this->preventMarkupUpdateOnAjaxLoad;
	}

	/**
	 * @param boolean $preventMarkupUpdateOnAjaxLoad
	 */
	public function setPreventMarkupUpdateOnAjaxLoad($preventMarkupUpdateOnAjaxLoad) {
		$this->preventMarkupUpdateOnAjaxLoad = $preventMarkupUpdateOnAjaxLoad;
	}

	/**
	 * @return boolean
	 */
	public function getPreventHistoryPush() {
		return $this->preventHistoryPush;
	}

	/**
	 * @param boolean $preventHistoryPush
	 */
	public function setPreventHistoryPush($preventHistoryPush) {
		$this->preventHistoryPush = $preventHistoryPush;
	}

	/**
	 * @param $configuration
	 * @param $r
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 */
	protected function checkAllowedControllerActions($configuration, &$r) {
		$allowedControllerActions = array();
		foreach ($configuration['controllerConfiguration'] as $controllerName => $controllerActions) {
			$allowedControllerActions[$controllerName] = $controllerActions['actions'];
		}

		$allowedControllerNames = array_keys($allowedControllerActions);
		if (!in_array($r['controller'], $allowedControllerNames)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException(
				'The controller "' . $r['controller'] . '" is not allowed by this plugin. Please check for ExtensionUtility::configurePlugin() in your ext_localconf.php.',
				1313855173);
		}

		$defaultActionName = is_array($allowedControllerActions[$r['controller']]) ? current($allowedControllerActions[$r['controller']]) : '';
		if (!isset($r['action']) || strlen($r['action']) === 0) {
			if (strlen($defaultActionName) === 0) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception(
					'The default action can not be determined for controller "' . $r['controller'] . '". Please check ExtensionUtility::configurePlugin() in your ext_localconf.php.',
					1295479651);
			} else {
				$r['action'] = $defaultActionName;
			}
		}
		$allowedActionNames = $allowedControllerActions[$r['controller']];
		if (!in_array($r['action'], $allowedActionNames)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException(
				'The action "' . $r['action'] . '" (controller "' . $r['controller'] . '") is not allowed by this plugin. Please check ExtensionUtility::configurePlugin() in your ext_localconf.php.',
				1313855175);
		}

		// filter extension/vendor/plugin, we allow only initial context plugin
		unset($r['extension']);
		unset($r['vendor']);
		unset($r['plugin']);
	}
}
