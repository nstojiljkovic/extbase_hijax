<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Link;

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
	 * @inject
	 */
	protected $mvcDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @inject
	 */
	protected $extensionService;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 * @inject
	 */
	protected $hijaxEventDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
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
	 * @param boolean $forceContext TRUE if the controller/action/... should be passed
	 * @param boolean $noAjax
	 *
	 * @return string
	 */
	public function render(
			$action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $format = '',
			$pageUid = NULL, $cachedAjaxIfPossible = TRUE, $forceContext = TRUE, $noAjax = FALSE) {
		$request = $this->mvcDispatcher->getCurrentRequest();

		if ($forceContext) {
			$requestArguments = $this->controllerContext->getRequest()->getArguments();
			$requestArguments = array_merge($requestArguments, $this->hijaxEventDispatcher->getContextArguments());
			$requestArguments = array_merge($requestArguments, $arguments);
			$arguments = $requestArguments;
		}

		if ($noAjax) {
			$result = parent::render($action, $arguments, $controller, $extensionName, $pluginName, $pageUid);
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

			$language = intval($GLOBALS['TSFE'] ? $GLOBALS['TSFE']->sys_language_content : 0);
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

			$this->tag->addAttribute('href', $uri);
			$this->tag->setContent($this->renderChildren());
			$this->tag->forceClosingTag(TRUE);

			$result = $this->tag->render();
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
