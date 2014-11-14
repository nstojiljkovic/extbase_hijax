<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Widget;

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
 * Class LinkViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Widget
 */
class LinkViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Widget\LinkViewHelper {

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
	 * Render the link.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param array $contextArguments Context arguments
	 * @param string $section The anchor to be added to the URI
	 * @param string $format The requested format, e.g. ".html"
	 * @param boolean $ajax TRUE if the URI should be to an AJAX widget, FALSE otherwise.
	 * @param boolean $cachedAjaxIfPossible TRUE if the URI should be cached (with respect to non-cacheable actions)
	 * @param boolean $forceContext TRUE if the controller/action/... should be passed
	 * @return string The rendered link
	 * @api
	 */
	public function render($action = NULL, $arguments = array(), $contextArguments = array(), $section = '', $format = '', $ajax = TRUE, $cachedAjaxIfPossible = TRUE, $forceContext = FALSE) {
		$uri = $this->getWidgetUri($action, $arguments, $contextArguments, $ajax, $cachedAjaxIfPossible, $forceContext);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());

		return $this->tag->render();
	}

	/**
	 * Renders hijax-related data attributes
	 *
	 * @param NULL $action
	 * @param array $arguments
	 * @param array $contextArguments
	 * @param bool $ajax
	 * @param bool $cachedAjaxIfPossible
	 * @param bool $forceContext
	 * @return string
	 */
	protected function getWidgetUri($action = NULL, array $arguments = array(), array $contextArguments = array(), $ajax = TRUE, $cachedAjaxIfPossible = TRUE, $forceContext = FALSE) {
		$this->hijaxEventDispatcher->setIsHijaxElement(TRUE);

		$request = $this->controllerContext->getRequest();
		/* @var $widgetContext \EssentialDots\ExtbaseHijax\Core\Widget\WidgetContext */
		$widgetContext = $request->getWidgetContext();
		$tagAttributes = array();

		if ($ajax) {
			$tagAttributes['data-hijax-element-type'] = 'link';
			$this->tag->addAttribute('class', trim($this->arguments['class'] . ' hijax-element'));
		}

		if ($action === NULL) {
			$action = $widgetContext->getParentControllerContext()->getRequest()->getControllerActionName();
		}
		if ($ajax) {
			$tagAttributes['data-hijax-action'] = $action;
		}

		$controller = $widgetContext->getParentControllerContext()->getRequest()->getControllerName();
		if ($ajax) {
			$tagAttributes['data-hijax-controller'] = $controller;
		}

		$extensionName = $widgetContext->getParentControllerContext()->getRequest()->getControllerExtensionName();
		if ($ajax) {
			$tagAttributes['data-hijax-extension'] = $extensionName;
		}

		if (TYPO3_MODE === 'FE') {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
		}
		if (!$pluginName) {
			$pluginName = $request->getPluginName();
		}
		if (!$pluginName) {
			$pluginName = $widgetContext->getParentPluginName();
		}
		if ($ajax) {
			$tagAttributes['data-hijax-plugin'] = $pluginName;
		}
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		if ($ajax) {
			$tagAttributes['data-hijax-namespace'] = $pluginNamespace;
		}

		$requestArguments = $widgetContext->getParentControllerContext()->getRequest()->getArguments();
		$requestArguments = array_merge($requestArguments, $this->hijaxEventDispatcher->getContextArguments());
		$requestArguments = array_merge($requestArguments, $contextArguments);
		$requestArguments[$widgetContext->getWidgetIdentifier()] = ($arguments && is_array($arguments)) ? $arguments : array();

		if (version_compare(TYPO3_version, '6.1.0', '>=')) {
			$variableContainer = $widgetContext->getViewHelperChildNodeRenderingContext()->getViewHelperVariableContainer();
			if ($variableContainer->exists('TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper', 'formFieldNames')) {
				$formFieldNames = $variableContainer->get('TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper', 'formFieldNames');
				$mvcPropertyMappingConfigurationService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\MvcPropertyMappingConfigurationService');
				/* @var $mvcPropertyMappingConfigurationService \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService */
				$requestHash = $mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $pluginNamespace);
				$requestArguments['__trustedProperties'] = $requestHash;
			}
		}

		if ($ajax) {
			$tagAttributes['data-hijax-arguments'] = serialize($requestArguments);
		}

		/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
		$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
		if ($ajax) {
			$tagAttributes['data-hijax-settings'] = $listener->getId();
		}

		$cachedAjaxIfPossible = $cachedAjaxIfPossible ? $this->configurationManager->getContentObject()->getUserObjectType() != ContentObjectRenderer::OBJECTTYPE_USER_INT : FALSE;

		if ($cachedAjaxIfPossible) {
			/* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
			$cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
			$tagAttributes['data-hijax-chash'] = $cacheHash->calculateCacheHash(array(
				'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
				'action' => $tagAttributes['data-hijax-action'],
				'controller' => $tagAttributes['data-hijax-controller'],
				'extension' => $tagAttributes['data-hijax-extension'],
				'plugin' => $tagAttributes['data-hijax-plugin'],
				'arguments' => $tagAttributes['data-hijax-arguments'],
				'settingsHash' => $tagAttributes['data-hijax-settings']
			));
		}

		foreach ($tagAttributes as $tagAttribute => $attributeValue) {
			$this->tag->addAttribute($tagAttribute, $attributeValue);
		}

		$uriBuilder = $this->controllerContext->getUriBuilder();

		$argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();

		if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
			$requestArguments['format'] = $this->arguments['format'];
		}

		$uriBuilder
			->reset()
			//->setUseCacheHash($this->contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER)
			->setArguments(array($pluginNamespace => $requestArguments))
			->setSection($this->arguments['section'])
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array($argumentPrefix, 'cHash'))
			->setFormat($this->arguments['format']);

		if ($forceContext) {
			$result = $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
		} else {
			$result = $uriBuilder->build();
		}

		return $result;
	}
}
