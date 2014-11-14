<?php
namespace EssentialDots\ExtbaseHijax\Utility;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Extension
 *
 * @package EssentialDots\ExtbaseHijax\Utility
 */
class Extension extends \TYPO3\CMS\Extbase\Utility\ExtensionUtility {

	/**
	 * Add auto-generated TypoScript to configure the Extbase Dispatcher.
	 *
	 * When adding a frontend plugin you will have to add both an entry to the TCA definition
	 * of tt_content table AND to the TypoScript template which must initiate the rendering.
	 * Since the static template with uid 43 is the "content.default" and practically always
	 * used for rendering the content elements it's very useful to have this function automatically
	 * adding the necessary TypoScript for calling the appropriate controller and action of your plugin.
	 * It will also work for the extension "css_styled_content"
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 2
	 *
	 * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
	 * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value)
	 * @param array $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
	 * @param string $pluginType either \TYPO3\CMS\Extbase\Utility\ExtensionUtility::TYPE_PLUGIN (default) or \TYPO3\CMS\Extbase\Utility\ExtensionUtility::TYPE_CONTENT_ELEMENT
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	static public function configurePlugin($extensionName, $pluginName, array $controllerActions, array $nonCacheableControllerActions = array(), $pluginType = self::PLUGIN_TYPE_PLUGIN) {
		if (empty($pluginName)) {
			throw new \InvalidArgumentException('The plugin name must not be empty', 1239891987);
		}
		if (empty($extensionName)) {
			throw new \InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] = array();
		}

		foreach ($controllerActions as $controllerName => $actionsList) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName] =
				array('actions' => GeneralUtility::trimExplode(',', $actionsList));
			if (!empty($nonCacheableControllerActions[$controllerName])) {
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName]['nonCacheableActions'] =
					GeneralUtility::trimExplode(',', $nonCacheableControllerActions[$controllerName]);
			}
		}

		$pluginTemplate = 'plugin.tx_' . strtolower($extensionName) . ' {
	settings {
	}
	persistence {
		storagePid =
		classes {
		}
	}
	view {
		templateRootPath =
		layoutRootPath =
		partialRootPath =
		 # with defaultPid you can specify the default page uid of this plugin.
		 # If you set this to the string "auto" the target page will be determined automatically.
		 # Defaults to an empty string that expects the target page to be the current page.
		defaultPid =
	}
}';
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginTemplate);

		switch ($pluginType) {
			case self::PLUGIN_TYPE_PLUGIN:
				$pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = USER
tt_content.list.20.' . $pluginSignature . ' {
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = ' . $extensionName . '
	pluginName = ' . $pluginName . '
}');
				break;
			case self::PLUGIN_TYPE_CONTENT_ELEMENT:
				$pluginContent = trim('
tt_content.' . $pluginSignature . ' = COA
tt_content.' . $pluginSignature . ' {
	10 = < lib.stdheader
	20 = USER
	20 {
		userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
		extensionName = ' . $extensionName . '
		pluginName = ' . $pluginName . '
	}
}');
				break;
			default:
				throw new \InvalidArgumentException('The pluginType "' . $pluginType . '" is not suported', 1289858856);
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] = $pluginType;

		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginContent, 43);
	}

	/**
	 * Register an Extbase Hijax actions
	 * FOR USE IN ext_localconf.php FILES
	 *
	 * @param string $extensionKey The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value)
	 * @param array $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
	 * @param string $vendorName
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	static public function registerHijaxPlugin($extensionKey, array $controllerActions, array $nonCacheableControllerActions = array(), $vendorName = '') {
		if (empty($extensionKey)) {
			throw new \InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionName = GeneralUtility::underscoredToUpperCamelCase($extensionKey);
		$extensionName = $vendorName ? $vendorName . $extensionName : $extensionName;

		foreach ($controllerActions as $controllerName => $actionsList) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName] =
				array('actions' => GeneralUtility::trimExplode(',', $actionsList));
			if (!empty($nonCacheableControllerActions[$controllerName])) {
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName]['nonCacheableActions'] =
					GeneralUtility::trimExplode(',', $nonCacheableControllerActions[$controllerName]);
			}
		}
	}

	/**
	 * @param string $extensionName
	 * @param string $controllerName
	 * @param string $actionName
	 * @param string $vendorName
	 * @return bool
	 */
	static public function isAllowedHijaxAction($extensionName, $controllerName, $actionName, $vendorName) {
		// @todo: allow only requests with the given referrer
		$extensionName = $vendorName ? $vendorName . $extensionName : $extensionName;
		$result = FALSE;

		if (
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax'] &&
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName] &&
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName] &&
			in_array($actionName, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase_hijax']['extensions'][$extensionName]['controllers'][$controllerName]['actions'])
		) {
			$result = TRUE;
		}

		return $result;
	}
}
