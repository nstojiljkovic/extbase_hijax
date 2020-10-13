<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$boot = function () {
	$_EXTKEY = 'extbase_hijax';

	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EssentialDots\\ExtbaseHijax\\Tslib\\FE\\Hook->contentPostProcAll';
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EssentialDots\\ExtbaseHijax\\Tslib\\FE\\Hook->contentPostProcOutput';
	// @extensionScannerIgnoreLine
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'EssentialDots\\ExtbaseHijax\\Tslib\\FE\\Hook->initFEuser';

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
		$_EXTKEY,
		'Pi1',
		array(
			'ContentElement' => 'user,userInt',
		),
		// non-cacheable actions
		array(
			'ContentElement' => 'userInt',
		)
	);

	$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_dispatcher'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/Private/Eid/dispatcher.php';

	if (!$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode']) {
		$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode'] = 'flock';
	}

	// Register cache for extbase_hijax
	// Tracking
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking'] = array();
	}

	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking']['backend'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend';
	}

	// Settings/serialized storage
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage'] = array();
	}

	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage']['backend'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend';
	}

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
		'EssentialDots\\ExtbaseHijax\\TCEmain\\Hooks->clearCachePostProc';
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
		'EssentialDots\\ExtbaseHijax\\TCEmain\\Hooks';
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
		'EssentialDots\\ExtbaseHijax\\TCEmain\\Hooks';

	/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
	$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher', 'EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\BackendInterface', 'EssentialDots\\ExtbaseHijax\\Persistence\\Storage\\Typo3DbBackend');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface', 'EssentialDots\\ExtbaseHijax\\Persistence\\Backend');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface', 'EssentialDots\\ExtbaseHijax\\Persistence\\Query');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ObjectStorageConverter', 'EssentialDots\\ExtbaseHijax\\Property\\TypeConverter\\ObjectStorageConverter');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments', 'EssentialDots\\ExtbaseHijax\\MVC\\Controller\\Arguments');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ObjectStorageConverter']['className'] = 'EssentialDots\\ExtbaseHijax\\Property\\TypeConverter\\ObjectStorageConverter';
	unset($extbaseObjectContainer);

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['EssentialDots\\ExtbaseHijax\\Session\\AbstractSession']['className'] = 'EssentialDots\\ExtbaseHijax\\Session\\Session';
};

$boot();
unset($boot);