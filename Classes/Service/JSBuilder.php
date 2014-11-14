<?php
namespace EssentialDots\ExtbaseHijax\Service;

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
 * Class JSBuilder
 *
 * @package EssentialDots\ExtbaseHijax\Service
 */
class JSBuilder implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var \EssentialDots\ExtbaseHijax\MVC\Dispatcher
	 * @inject
	 */
	protected $mvcDispatcher;

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
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 * @inject
	 */
	protected $pageRenderer;

	/**
	 * Returns TRUE if what we are outputting may be cached
	 *
	 * @return boolean
	 */
	protected function isCached() {
		$userObjType = $this->configurationManager->getContentObject()->getUserObjectType();
		return ($userObjType !== \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER_INT);
	}

	/**
	 * Returns JS callback for the given action
	 *
	 * @param NULL $action
	 * @param array $arguments
	 * @param NULL $controller
	 * @param NULL $extensionName
	 * @param NULL $pluginName
	 * @param string $format
	 * @param string $section
	 * @return string
	 */
	public function getAjaxFunction($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $format = '', $section = 'footer') {
		// current element needs to have additional logic...
		$this->hijaxEventDispatcher->setIsHijaxElement(TRUE);

		$request = $this->mvcDispatcher->getCurrentRequest();

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
		}

		$format = $format ? $format : 'html';
		$settingsHash = $this->mvcDispatcher->getCurrentListener() ? $this->mvcDispatcher->getCurrentListener()->getId() : '';

		$settings = array(
			'extension' => $extensionName,
			'plugin' => $pluginName,
			'controller' => $controller,
			'format' => $format,
			'action' => $action,
			'arguments' => $arguments,
			'settingsHash' => $settingsHash,
			'namespace' => ($extensionName && $pluginName) ? $this->extensionService->getPluginNamespace($extensionName, $pluginName) : '',
		);

		$functionName = 'extbaseHijax_' . md5(serialize($settings));

		$content = '; ' . $functionName . '=function(settings, pendingElement, loaders) {';
		foreach ($settings as $k => $v) {
			$content .= 'if (typeof settings.' . $k . ' == \'undefined\') settings.' . $k . '=' . json_encode($v) . ';';
		}
		$content .= 'return jQuery.hijax(settings, pendingElement, loaders);};';

		if ($this->isCached()) {
			if ($section == 'footer') {
				$this->pageRenderer->addJsFooterInlineCode(md5($content), $content, FALSE, TRUE);
			} else {
				$this->pageRenderer->addJsInlineCode(md5($content), $content, FALSE, TRUE);
			}
		} else {
			// additionalFooterData not possible in USER_INT
			$GLOBALS['TSFE']->additionalHeaderData[md5($content)] = \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
		}

		return $functionName;
	}
}