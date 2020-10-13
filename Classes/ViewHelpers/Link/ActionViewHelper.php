<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Link;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
 * Class ActionViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Link
 */
class ActionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\MVC\Dispatcher
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $mvcDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $extensionService;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $hijaxEventDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $configurationManager;

	/**
	 * @param string $action
	 * @param array $arguments
	 * @param string $controller
	 * @param string $extensionName
	 * @param string $pluginName
	 * @param string $format
	 * @param int $pageUid
	 * @param boolean $cachedAjaxIfPossible TRUE if the URI should be cached (with respect to non-cacheable actions)
	 * @param mixed $forceContext TRUE if the controller/action/... should be passed. Or array of keys that should be merged
	 * @param array $skipContextArguments
	 * @param boolean $noAjax
	 * @param boolean $returnUriOnly
	 *
	 * @return string
	 * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
	 * @throws \TYPO3\CMS\Extbase\Exception
	 */
	public function render(
			$action = NULL,
			array $arguments = array(),
			$controller = NULL,
			$extensionName = NULL,
			$pluginName = NULL,
			$format = '',
			$pageUid = NULL,
			$cachedAjaxIfPossible = TRUE,
			$forceContext = TRUE,
			$skipContextArguments = array(),
			$noAjax = FALSE,
			$returnUriOnly = FALSE) {
		$request = $this->mvcDispatcher->getCurrentRequest();

		if ($forceContext) {
			$requestArguments = $this->renderingContext->getControllerContext()->getRequest()->getArguments();
			$requestArguments = array_merge($requestArguments, $this->hijaxEventDispatcher->getContextArguments());
			if (is_array($forceContext)) {
				$filteredRequestArguments = array();
				foreach ($forceContext as $key) {
					if (array_key_exists($key, $requestArguments)) {
						$filteredRequestArguments[$key] = $requestArguments[$key];
					}
				}
				$requestArguments = $filteredRequestArguments;
			}
			if (count($skipContextArguments)) {
				foreach ($skipContextArguments as $key) {
					if (array_key_exists($key, $requestArguments)) {
						unset($requestArguments[$key]);
					}
				}
			}
			$requestArguments = array_merge($requestArguments, $arguments);
			$arguments = $requestArguments;
		}

		if ($noAjax) {
			if ($returnUriOnly) {
				$uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
				$pageType = 0;
				$noCache = FALSE;
				$result = $uriBuilder
					->reset()
					->setTargetPageUid($pageUid)
					->setTargetPageType($pageType)
					->setNoCache($noCache)
					->setFormat($format)
					->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
			} else {
				$result = parent::render($action, $arguments, $controller, $extensionName, $pluginName, $pageUid);
			}
		} else {

			/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
			$listener = $this->mvcDispatcher->getCurrentListener();

			if ($request) {
				if ($action === NULL) {
					$action = $request->getControllerActionName();
				}

				if ($controller === NULL) {
					$controller = $request->getControllerName();
				}

				if ($extensionName === NULL) {
					$extensionName = $request->getControllerExtensionName();
				}

				if ($pluginName === NULL && TYPO3_MODE === 'FE') {
					$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
				}
				if ($pluginName === NULL) {
					$pluginName = $request->getPluginName();
				}

				// @extensionScannerIgnoreLine
				$cachedAjaxIfPossible = $cachedAjaxIfPossible ? $this->configurationManager->getContentObject()->getUserObjectType() != ContentObjectRenderer::OBJECTTYPE_USER_INT : FALSE;

				if ($cachedAjaxIfPossible) {
					/* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
					$cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
					$chash = $cacheHash->calculateCacheHash(array(
						'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
						'action' => $action,
						'controller' => $controller,
						'extension' => $extensionName,
						'plugin' => $pluginName,
						'arguments' => $this->arrayMapRecursive('strval', $arguments),
						'settingsHash' => $listener->getId()
					));
				}
			}

			$additionalArguments = array();
			$this->hA('r[0][arguments]', $arguments, $additionalArguments);

			$context = GeneralUtility::makeInstance(Context::class);
			$language = intval($context->getPropertyFromAspect('language', 'contentId') ?: 0);
			$additionalParams =
				'&r[0][extension]=' . $extensionName .
				'&r[0][plugin]=' . $pluginName .
				'&r[0][controller]=' . $controller .
				'&r[0][action]=' . $action .
				'&r[0][format]=' . $format .
				'&r[0][settingsHash]=' . $listener->getId() .
				'&eID=extbase_hijax_dispatcher&L=' . $language;

			if ($additionalArguments) {
				$additionalParams .= '&' . implode('&', $additionalArguments);
			}

			if ($chash) {
				$additionalParams .= '&r[0][chash]=' . $chash;
			}

			/* @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			if (!$pageUid) {
				$pageUid = $GLOBALS['TSFE'] ? $GLOBALS['TSFE']->id : 0;
			}

			$uri = $cObj->typoLink('', array(
				'returnLast' => 'url',
				'additionalParams' => $additionalParams,
				'parameter' => $pageUid
			));

			if ($returnUriOnly) {
				$result = $uri;
			} else {
				$this->tag->addAttribute('href', $uri);
				$this->tag->setContent($this->renderChildren());
				$this->tag->forceClosingTag(TRUE);

				$result = $this->tag->render();
			}
		}

		return $result;
	}

	/**
	 * @param string $namespace
	 * @param array $arguments
	 * @param array $additionalArguments
	 * @return void
	 */
	protected function hA($namespace, $arguments, &$additionalArguments) {
		if ($arguments) {
			foreach ($arguments as $i => $v) {
				if (is_array($v)) {
					$this->hA($namespace . '[' . $i . ']', $v, $additionalArguments);
				} else {
					$additionalArguments[] = $namespace . '[' . $i . ']=' . rawurlencode($v);
				}
			}
		}
	}

	/**
	 * @param $fn
	 * @param $arr
	 * @return array
	 */
	protected function arrayMapRecursive($fn, $arr) {
		$rarr = array();
		foreach ($arr as $k => $v) {
			$rarr[$k] = is_array($v)
				? $this->arrayMapRecursive($fn, $v)
				: $fn($v);
		}
		return $rarr;
	}
}
