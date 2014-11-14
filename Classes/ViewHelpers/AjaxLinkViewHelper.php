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
 * Class AjaxLinkViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class AjaxLinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

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
	 * @var \EssentialDots\ExtbaseHijax\Service\JSBuilder
	 * @inject
	 */
	protected $jsBuilder;

	/**
	 * @param string $action
	 * @param array $arguments
	 * @param string $controller
	 * @param string $extensionName
	 * @param string $pluginName
	 * @param string $format
	 * @param int $pageUid
	 *
	 * @return string
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $format = '', $pageUid = 0) {
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

		$additionalArguments = array();
		$this->hA('r[0][arguments]', $arguments, $additionalArguments);

		$language = intval($GLOBALS['TSFE'] ? $GLOBALS['TSFE']->sys_language_content : 0);
		$additionalParams = '&r[0][extension]=' . $extensionName .
							'&r[0][plugin]=' . $pluginName .
							'&r[0][controller]=' . $controller .
							'&r[0][action]=' . $action .
							'&r[0][format]=' . $format .
							'&eID=extbase_hijax_dispatcher&L=' . $language;

		if ($additionalArguments) {
			$additionalParams .= '&' . implode('&', $additionalArguments);
		}

		/* @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
		$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		if (!$pageUid) {
			$pageUid = $GLOBALS['TSFE'] ? $GLOBALS['TSFE']->id : 0;
		}

		return $cObj->typoLink('', array(
			'returnLast' => 'url',
			'additionalParams' => $additionalParams,
			'parameter' => $pageUid
		));
	}

	/**
	 * @param string $namespace
	 * @param array $arguments
	 * @param array $additionalArguments
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
}
