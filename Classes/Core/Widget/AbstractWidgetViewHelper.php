<?php
namespace EssentialDots\ExtbaseHijax\Core\Widget;

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
 * Class AbstractWidgetViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\Core\Widget
 */
abstract class AbstractWidgetViewHelper extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper {

	/**
	 * The Controller associated to this widget.
	 * This needs to be filled by the individual subclass by an @TYPO3\CMS\Extbase\Annotation\Inject
	 * annotation.
	 *
	 * @var \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController
	 * @api
	 */
	protected $controller;

	/**
	 * If set to TRUE, it is an AJAX widget.
	 *
	 * @var boolean
	 * @api
	 */
	protected $ajaxWidget = FALSE;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $ajaxWidgetContextHolder;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $extensionService;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Core\Widget\WidgetContext
	 */
	protected $widgetContext;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->widgetContext = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Core\\Widget\\WidgetContext');
	}

	/**
	 * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
	 *
	 * @return string the rendered ViewHelper.
	 */
	public function initializeArgumentsAndRender() {
		$this->validateArguments();
		$this->initialize();
		$this->initializeWidgetContext();

		return $this->callRenderMethod();
	}

	/**
	 * Initialize the Widget Context, before the Render method is called.
	 *
	 * @return void
	 */
	protected function initializeWidgetContext() {
		$this->widgetContext->setWidgetConfiguration($this->getWidgetConfiguration());
		$this->initializeWidgetIdentifier();
		$this->widgetContext->setControllerObjectName(get_class($this->controller));
		$extensionName = $this->renderingContext->getControllerContext()->getRequest()->getControllerExtensionName();
		$pluginName = $this->renderingContext->getControllerContext()->getRequest()->getPluginName();
		$this->widgetContext->setParentExtensionName($extensionName);
		$this->widgetContext->setParentPluginName($pluginName);
		$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
		$this->widgetContext->setParentPluginNamespace($pluginNamespace);
		// set parent context
		$this->widgetContext->setParentControllerContext($this->renderingContext->getControllerContext());
		$this->widgetContext->setWidgetViewHelperClassName(get_class($this));
		if ($this->ajaxWidget === TRUE) {
			$this->ajaxWidgetContextHolder->store($this->widgetContext);
		}
	}

	/**
	 * Stores the syntax tree child nodes in the Widget Context, so they can be
	 * rendered with <f:widget.renderChildren> lateron.
	 *
	 * @param array $childNodes The SyntaxTree Child nodes of this ViewHelper.
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$rootNode = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode');
		foreach ($childNodes as $childNode) {
			$rootNode->addChildNode($childNode);
		}
		$this->widgetContext->setViewHelperChildNodes($rootNode, $this->renderingContext);
	}

	/**
	 * Generate the configuration for this widget. Override to adjust.
	 *
	 * @return array
	 * @api
	 */
	protected function getWidgetConfiguration() {
		return $this->arguments;
	}

	/**
	 * Initiate a sub request to $this->controller. Make sure to fill $this->controller
	 * via Dependency Injection.
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface the response of this request.
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 */
	protected function initiateSubRequest() {
		if (!($this->controller instanceof \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController)) {
			if (isset($this->controller)) {
				throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException(
					'initiateSubRequest() can not be called if there is no valid controller extending AbstractWidgetController.' .
					'Got "' . get_class($this->controller) . '" in class "' . get_class($this) . '".',
					1289422564);
			}
			throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\MissingControllerException(
				'initiateSubRequest() can not be called if there is no controller inside $this->controller.' .
				'Make sure to add a corresponding injectController method to your WidgetViewHelper class "' . get_class($this) . '".',
				1284401632);
		}

		$subRequest = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest');
		$subRequest->setWidgetContext($this->widgetContext);
		$this->passArgumentsToSubRequest($subRequest);

		$subResponse = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');
		$this->controller->processRequest($subRequest, $subResponse);
		return $subResponse;
	}

	/**
	 * Pass the arguments of the widget to the subrequest.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 */
	protected function passArgumentsToSubRequest(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest $subRequest) {
		$arguments = $this->renderingContext->getControllerContext()->getRequest()->getArguments();
		$widgetIdentifier = $this->widgetContext->getWidgetIdentifier();
		if (isset($arguments[$widgetIdentifier])) {
			if (isset($arguments[$widgetIdentifier]['action'])) {
				$subRequest->setControllerActionName($arguments[$widgetIdentifier]['action']);
				unset($arguments[$widgetIdentifier]['action']);
			}
			$subRequest->setArguments($arguments[$widgetIdentifier]);
		}
	}

	/**
	 * The widget identifier is unique on the current page, and is used
	 * in the URI as a namespace for the widget's arguments.
	 *
	 * @return string the widget identifier for this widget
	 * @return void
	 * @todo clean up, and make it somehow more routing compatible.
	 */
	protected function initializeWidgetIdentifier() {
		if (!$this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetViewHelper', 'nextWidgetNumber')) {
			$widgetCounter = 0;
		} else {
			$widgetCounter = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetViewHelper', 'nextWidgetNumber');
		}
		$widgetIdentifier = '__widget_' . $widgetCounter;
		$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetViewHelper', 'nextWidgetNumber', $widgetCounter + 1);

		$this->widgetContext->setWidgetIdentifier($widgetIdentifier);
	}
}
