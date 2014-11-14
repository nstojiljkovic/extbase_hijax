<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers;

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
 * Class AjaxRefreshViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class AjaxRefreshViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'div';

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 * @inject
	 */
	protected $hijaxEventDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @inject
	 */
	protected $extensionService;

	/**
	 * Render the ajax refreshable element.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param string $controller Target controller
	 * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
	 * @param string $pluginName Target plugin. If empty, the current plugin name is used
	 * @param integer $pageUid Target page uid
	 * @param mixed $object Object to use for the element. Use in conjunction with the "property" attribute on the sub tags
	 * @param integer $pageType Target page type
	 * @param boolean $noCache set this to disable caching for the target page. You should not need this.
	 * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
	 * @param string $section The anchor to be added to the action URI (only active if $actionUri is not set)
	 * @param string $format The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
	 * @param array $additionalParams additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
	 * @param boolean $absolute If set, an absolute action URI is rendered (only active if $actionUri is not set)
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
	 * @param string $fieldNamePrefix Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
	 * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
	 * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
	 * @param string $resultTarget target where the results will be loaded
	 * @param string $loaders target where the loader will be shown
	 * @return string rendered element
	 */
	public function render(
			$action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL,
			$pageUid = NULL, $object = NULL, $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $format = '',
			array $additionalParams = array(), $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(),
			$fieldNamePrefix = NULL, $actionUri = NULL, $objectName = NULL, $resultTarget = NULL, $loaders = NULL) {

		$this->renderHijaxDataAttributes($action, $arguments, $controller, $extensionName, $pluginName);
		$this->hijaxEventDispatcher->setIsHijaxElement(TRUE);

		if ($resultTarget) {
			$this->tag->addAttribute('data-hijax-result-target', $resultTarget);
		} else {
			/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
			$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
			$this->tag->addAttribute('data-hijax-result-target', "jQuery(this).parents('.hijax-element[data-hijax-listener-id=\"" . $listener->getId() . "\"]')");
			$this->tag->addAttribute('data-hijax-result-wrap', 'false');
		}
		if ($loaders) {
			$this->tag->addAttribute('data-hijax-loaders', $loaders);
		}

		$this->tag->setContent($this->renderChildren());

		$this->tag->setContent('<div class="hijax-content">' . $this->tag->getContent() . '</div><div class="hijax-loading"></div>');

		return $this->tag->render();
	}

	/**
	 * Renders hijax-related data attributes
	 *
	 * @param NULL $action
	 * @param array $arguments
	 * @param NULL $controller
	 * @param NULL $extensionName
	 * @param NULL $pluginName
	 */
	protected function renderHijaxDataAttributes($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL) {
		$request = $this->controllerContext->getRequest();

		$this->tag->addAttribute('data-hijax-element-type', 'ajax');
		$this->tag->addAttribute('class', trim($this->arguments['class'] . ' hijax-element'));

		if ($action === NULL) {
			$action = $request->getControllerActionName();
		}
		$this->tag->addAttribute('data-hijax-action', $action);

		if ($controller === NULL) {
			$controller = $request->getControllerName();
		}
		$this->tag->addAttribute('data-hijax-controller', $controller);

		if ($extensionName === NULL) {
			$extensionName = $request->getControllerExtensionName();
		}
		$this->tag->addAttribute('data-hijax-extension', $extensionName);

		if ($pluginName === NULL && TYPO3_MODE === 'FE') {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controller, $action);
		}
		if ($pluginName === NULL) {
			$pluginName = $request->getPluginName();
		}
		$this->tag->addAttribute('data-hijax-plugin', $pluginName);

		if ($arguments) {
			$this->tag->addAttribute('data-hijax-arguments', serialize($arguments));
		}

		/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
		$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
		$this->tag->addAttribute('data-hijax-settings', $listener->getId());

		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		$this->tag->addAttribute('data-hijax-namespace', $pluginNamespace);
	}
}
