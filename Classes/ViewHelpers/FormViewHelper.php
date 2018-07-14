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
 * Class FormViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class FormViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper {

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
	 * Initialize arguments.
	 * @return void
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		if (version_compare(TYPO3_version, '8.3', '<')) {
			$this->registerArgument('action', 'string', 'Target action');
			$this->registerArgument('arguments', 'array', 'Arguments', FALSE, []);
			$this->registerArgument('controller', 'string', 'Target controller');
			$this->registerArgument('extensionName', 'string', 'Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used');
			$this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
			$this->registerArgument('pageUid', 'int', 'Target page uid');
			$this->registerArgument('object', 'mixed', 'Object to use for the form. Use in conjunction with the "property" attribute on the sub tags');
			$this->registerArgument('pageType', 'int', 'Target page type', FALSE, 0);
			$this->registerArgument('noCache', 'bool', 'set this to disable caching for the target page. You should not need this.', FALSE, FALSE);
			$this->registerArgument('noCacheHash', 'bool', 'set this to suppress the cHash query parameter created by TypoLink. You should not need this.', FALSE, FALSE);
			$this->registerArgument('section', 'string', 'The anchor to be added to the action URI (only active if $actionUri is not set)', FALSE, '');
			$this->registerArgument('format', 'string', 'The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)', FALSE, '');
			$this->registerArgument('additionalParams', 'array',
				'additional action URI query parameters that won\'t be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)', FALSE, []);
			$this->registerArgument('absolute', 'bool', 'If set, an absolute action URI is rendered (only active if $actionUri is not set)', FALSE, FALSE);
			$this->registerArgument('addQueryString', 'bool', 'If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)', FALSE, FALSE);
			$this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set', FALSE, []);
			$this->registerArgument('addQueryStringMethod', 'string', 'Method to use when keeping query parameters (GET or POST, only active if $actionUri is not set', FALSE, 'GET');
			$this->registerArgument('fieldNamePrefix', 'string', 'Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin');
			$this->registerArgument('actionUri', 'string', 'can be used to overwrite the "action" attribute of the form tag');
			$this->registerArgument('objectName', 'string',
				'name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName');
			$this->registerArgument('hiddenFieldClassName', 'string', 'hiddenFieldClassName');
			$this->registerTagAttribute('target', 'string', 'Target attribute of the form');
			$this->registerTagAttribute('novalidate', 'bool', 'Indicate that the form is not to be validated on submit.');
		}
		$this->registerArgument('resultTarget', 'string', 'Target where the results will be loaded');
		$this->registerArgument('loaders', 'array', 'Target where the loader will be shown');
	}

	/**
	 * Render the form.
	 *
	 * @return string
	 */
	public function render() {
		$this->renderHijaxDataAttributes($this->arguments['action'], $this->arguments['arguments'], $this->arguments['controller'], $this->arguments['extensionName'], $this->arguments['pluginName']);
		$this->hijaxEventDispatcher->setIsHijaxElement(TRUE);

		$resultTarget = $this->arguments['resultTarget'];
		$loaders = $this->arguments['loaders'];

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

		parent::render();

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

		$this->tag->addAttribute('data-hijax-element-type', 'form');
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
