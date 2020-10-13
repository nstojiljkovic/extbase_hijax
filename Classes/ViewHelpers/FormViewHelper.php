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
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Event\Dispatcher
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $hijaxEventDispatcher;

	/**
	 * Initialize arguments.
	 * @return void
	 * @throws \Exception
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('resultTarget', 'string', 'Target where the results will be loaded');
		$this->registerArgument('loaders', 'string', 'Target where the loader will be shown');
	}

	/**
	 * Render the form.
	 *
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Exception
	 * @throws \TYPO3\CMS\Extbase\Exception
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
	 * @throws \TYPO3\CMS\Extbase\Exception
	 */
	protected function renderHijaxDataAttributes($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL) {
		$request = $this->renderingContext->getControllerContext()->getRequest();

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
