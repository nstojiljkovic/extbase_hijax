<?php
namespace EssentialDots\ExtbaseHijax\Tests\Unit;

use TYPO3\CMS\Core\Package\PackageManager;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Essential Dots d.o.o. Belgrade
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * Class BaseTestCase
 *
 * @package EssentialDots\ExtbaseHijax\Tests\Unit
 */
abstract class BaseTestCase extends \Tx_Phpunit_Database_TestCase {

	/**
	 * @var array
	 */
	protected $mockObjects = array();

	/**
	 * @var array
	 */
	protected $tcaBackup;

	/**
	 * @var array
	 */
	protected $typo3ConfVarsBackup;

	/**
	 * @var PackageManager
	 */
	protected $packageManagerBackup;

	/**
	 * @var array
	 */
	protected $loadedExtsBackup;

	/**
	 * @var boolean
	 */
	protected $useDataBaseMemoryEngineIfAvailable = FALSE;

	/**
	 * @var boolean
	 */
	protected $useDataBaseMemoryEngine = FALSE;

	/**
	 * @var array
	 */
	protected $skipMemoryEngineForTables = array();

	/**
	 * @var array
	 */
	protected $extensions = array('cms', 'frontend', 'backend', 'extbase', 'tstemplate', 'extbase_hijax');

	/**
	 * @var array
	 */
	protected $extTablesPaths = array();

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function setUp() {
		$_SERVER['HTTP_HOST'] = 'secure.essentialdots.com';
		define('TYPO3_PATH_WEB', 1);
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_SERVER['SCRIPT_NAME'] = '/index.php';

		// @codingStandardsIgnoreStart
		GLOBAL $TCA;
		// @codingStandardsIgnoreEnd

		$this->typo3ConfVarsBackup = $GLOBALS['TYPO3_CONF_VARS'];
		$GLOBALS['TYPO3_CONF_VARS'] = array(
			'DB' => $this->typo3ConfVarsBackup['DB'],
			'GFX' => $this->typo3ConfVarsBackup['GFX'],
			'SYS' => $this->typo3ConfVarsBackup['SYS'],
			'FE' => $this->typo3ConfVarsBackup['FE'],
			'BE' => $this->typo3ConfVarsBackup['BE'],
			'MAIL' => $this->typo3ConfVarsBackup['MAIL'],
			'HTTP' => $this->typo3ConfVarsBackup['HTTP'],
			'SVCONF' => $this->typo3ConfVarsBackup['SVCONF'],
			'INSTALL' => $this->typo3ConfVarsBackup['INSTALL'],
		);
		if (array_key_exists('DB_SCALE', $this->typo3ConfVarsBackup)) {
			$GLOBALS['TYPO3_CONF_VARS']['DB_SCALE'] = $this->typo3ConfVarsBackup['DB_SCALE'];
		}
		if (array_key_exists('DB_SCALE_PHPUNIT', $this->typo3ConfVarsBackup)) {
			$GLOBALS['TYPO3_CONF_VARS']['DB_SCALE_PHPUNIT'] = $this->typo3ConfVarsBackup['DB_SCALE_PHPUNIT'];
		}

		$db = $GLOBALS['TYPO3_DB'];
		/* @var $db \TYPO3\CMS\Dbal\Database\DatabaseConnection */
		$db->initialize();

		if ($this->useDataBaseMemoryEngineIfAvailable) {
			$res = $db->sql_query('SHOW ENGINES');
			while (($row = $db->sql_fetch_assoc($res))) {
				if (strtolower($row['Engine']) == 'memory' && (strtolower($row['Support']) == 'yes' || strtolower($row['Support']) == 'default')) {
					$this->useDataBaseMemoryEngine = TRUE;
					break;
				}
			}
			$db->sql_free_result($res);
		}

		$this->tcaBackup = &$TCA;
		$TCA = array();

		if (ExtensionManagementUtility::isLoaded('core')) {
			$stdDataBaseExtTablesFilename = ExtensionManagementUtility::extPath('core', 'ext_tables.php');
		} else {
			$stdDataBaseExtTablesFilename = GeneralUtility::getFileAbsFileName(PATH_t3lib . 'stddb/tables.php');
		}
		if (!file_exists($stdDataBaseExtTablesFilename) || is_dir($stdDataBaseExtTablesFilename)) {
			throw new \Exception('Cannot find STD DB PHP file.');
		}

		// @codingStandardsIgnoreStart
		include($stdDataBaseExtTablesFilename);
		// @codingStandardsIgnoreEnd

		$this->createDatabase();
		$this->useTestDatabase();
		$this->importStdDB();

		$this->defaultTypoScript_setupBackup = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'];

		$this->resetSingletonInstances();

		if (ExtensionManagementUtility::isLoaded('ed_scale')) {
			$this->extensions[] = 'ed_scale';
		}

		$this->loadedExtsBackup = $GLOBALS['TYPO3_LOADED_EXT'];
		/* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
		$this->packageManagerBackup = $objectManager->get('TYPO3\\CMS\\Core\\Package\\PackageManager');
		$allActivePackages = $this->packageManagerBackup->getActivePackages();
		$newActivePackages = array();
		$activePackagesMap = array();
		$getPackageMap = array();
		$extensionsWithDependencies[] = array();
		foreach ($this->extensions as $key) {
			$extensionsWithDependencies[] = $key;
			$dependencies = $this->findDependencies($key);
			if (is_array($dependencies)) {
				$extensionsWithDependencies = array_merge($extensionsWithDependencies, $dependencies);
			}
			$extensionsWithDependencies = array_unique($extensionsWithDependencies);
		}
		$GLOBALS['TYPO3_LOADED_EXT'] = array();
		foreach ($extensionsWithDependencies as $key) {
			if (isset($allActivePackages[$key])) {
				$newActivePackages[$key] = $allActivePackages[$key];
				$activePackagesMap[] = [$key, TRUE];
				$getPackageMap[] = [$key, $this->packageManagerBackup->getPackage($key)];
				$GLOBALS['TYPO3_LOADED_EXT'][$key] = $this->loadedExtsBackup[$key];
			}
		}
		/** @var \TYPO3\CMS\Core\Core\ClassLoader $classLoader */
		$classLoader = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		$classLoader->setPackages($newActivePackages);
		$packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array(), array(), '', FALSE);
		$packageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue($newActivePackages));
		$packageManager->expects($this->any())->method('isPackageAvailable')->will($this->returnValueMap($activePackagesMap));
		$packageManager->expects($this->any())->method('isPackageActive')->will($this->returnValueMap($activePackagesMap));
		$packageManager->expects($this->any())->method('getPackage')->will($this->returnValueMap($getPackageMap));
		ExtensionManagementUtility::setPackageManager($packageManager);

		$this->importExtensions($this->extensions, TRUE);
		foreach ($this->extTablesPaths as $extensionName => $extTablesPath) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$_EXTKEY = $extensionName;
			// @codingStandardsIgnoreStart
			include($extTablesPath);
			// @codingStandardsIgnoreEnd
		}
		ExtensionManagementUtility::loadBaseTca(FALSE);
		ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
		$this->extTablesPaths = array();
		ExtensionManagementUtility::setPackageManager($this->packageManagerBackup);
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->loadedExtsBackup;
		/** @var \TYPO3\CMS\Core\Core\ClassLoader $classLoader */
		$classLoader = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		$classLoader->setPackages($this->packageManagerBackup->getActivePackages());

		// needed in order to avoid SQL errors
		$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
	}

	/**
	 * @return void
	 */
	protected function tearDown() {
		// @codingStandardsIgnoreStart
		GLOBAL $TCA, $TYPO3_CONF_VARS;
		// @codingStandardsIgnoreEnd

		$this->cleanDatabase();
		$this->dropDatabase();
		$this->switchToTypo3Database();
		$this->resetSingletonInstances();

		/** @noinspection PhpUnusedLocalVariableInspection */
		$TCA = &$this->tcaBackup;
		$TYPO3_CONF_VARS = $this->typo3ConfVarsBackup;
		ExtensionManagementUtility::setPackageManager($this->packageManagerBackup);
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->loadedExtsBackup;
		/** @var \TYPO3\CMS\Core\Core\ClassLoader $classLoader */
		$classLoader = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		$classLoader->setPackages($this->packageManagerBackup->getActivePackages());

		unset($this->tcaBackup);
	}

	/**
	 * Drops all tables in the test database.
	 *
	 * @return void
	 */
	protected function cleanDatabase() {
		/** @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		if ($db->isConnected()) {
			parent::cleanDatabase();
		}
	}

	/**
	 * @return void
	 */
	protected function resetSingletonInstances() {
		$instances = GeneralUtility::getSingletonInstances();
		$newInstances = array();
		foreach ($instances as $key => $instance) {
			if ($instance instanceof \TYPO3\CMS\Core\Cache\CacheManager) {
				$instance->flushCaches();
				$newInstances[$key] = $instance;
			}
			if (
				$instance instanceof \TYPO3\CMS\Core\Cache\CacheFactory ||
				$instance instanceof \TYPO3\CMS\Core\Package\PackageManager
			) {
				$newInstances[$key] = $instance;
			}
			if (method_exists($instance, 'destroy')) {
				call_user_func(array($instance, 'destroy'));
			}
		}

		$reflectedGeneralUtilityClass = new \ReflectionClass('TYPO3\\CMS\\Core\\Utility\\GeneralUtility');
		$reflectedFinalClassNameCacheProperty = $reflectedGeneralUtilityClass->getProperty('finalClassNameCache');
		$reflectedFinalClassNameCacheProperty->setAccessible(true);
		$reflectedFinalClassNameCacheProperty->setValue(array());

		GeneralUtility::purgeInstances();
		GeneralUtility::resetSingletonInstances($newInstances);
	}

	/**
	 * Accesses the TYPO3 database instance and uses it to fetch the list of
	 * abailable databases. Then this function creates a test database (if none
	 * has been set up yet).
	 *
	 * @return boolean
	 *         TRUE if the database has been created successfully (or if there
	 *         already is a test database), FALSE otherwise
	 */
	protected function createDatabase() {
		$success = TRUE;

		$this->dropDatabase();
		/** @var $db \TYPO3\CMS\Dbal\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];

		if (!in_array($this->testDatabase, $this->adminGetDataBases())) {
			if ($db->admin_query('CREATE DATABASE ' . $this->testDatabase) === FALSE) {
				$success = FALSE;
			}
		}

		return $success;
	}

	/**
	 * Drops the test database.
	 *
	 * @return boolean
	 *         TRUE if the database has been dropped successfully, FALSE otherwise
	 */
	protected function dropDatabase() {
		/** @var $db \TYPO3\CMS\Dbal\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		if (!in_array($this->testDatabase, $this->adminGetDataBases())) {
			return TRUE;
		}

		$db->sql_select_db($this->testDatabase);

		return ($db->admin_query('DROP DATABASE ' . $this->testDatabase) !== FALSE);
	}

	/**
	 * @return array
	 */
	protected function adminGetDataBases() {
		/** @var $db \TYPO3\CMS\Dbal\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		$res = $db->sql_query('SHOW DATABASES');

		$databaseNames = array();
		while ($res && ($row = $db->sql_fetch_row($res))) {
			$databaseNames[] = $row[0];
		}

		return $databaseNames;
	}

	/**
	 * Imports the ext_tables.sql and ext_tables.php files from the given extensions.
	 *
	 * @param array $extensions
	 *        keys of the extensions to import, may be empty
	 * @param boolean $importDependencies
	 *        whether to import dependency extensions on which the given extensions
	 *        depend as well
	 * @param array &$skipDependencies
	 *        keys of the extensions to skip, may be empty, will be modified
	 *
	 * @return void
	 */
	protected function importExtensions(array $extensions, $importDependencies = FALSE, array &$skipDependencies = array()) {
		// @codingStandardsIgnoreStart
		GLOBAL $TCA;
		// @codingStandardsIgnoreEnd

		$this->useTestDatabase();

		foreach ($extensions as $extensionName) {
			if (!ExtensionManagementUtility::isLoaded($extensionName)) {
				$this->markTestSkipped(
					'This test is skipped because the extension ' . $extensionName .
					' which was marked for import is not loaded on your system!'
				);
			} elseif (in_array($extensionName, $skipDependencies)) {
				continue;
			}

			$skipDependencies = array_merge($skipDependencies, array($extensionName));

			if ($importDependencies) {
				$dependencies = $this->findDependencies($extensionName);
				if (is_array($dependencies)) {
					$this->importExtensions($dependencies, TRUE, $skipDependencies);
				}
			}

			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionName] = $this->typo3ConfVarsBackup['EXT']['extConf'][$extensionName];

			$extTypoScriptSetupPath = ExtensionManagementUtility::extPath($extensionName, 'ext_typoscript_setup.txt');
			if (file_exists($extTypoScriptSetupPath) && is_file($extTypoScriptSetupPath)) {
				ExtensionManagementUtility::addTypoScriptSetup(
					file_get_contents($extTypoScriptSetupPath)
				);
			}

			$extLocalconfPath = ExtensionManagementUtility::extPath($extensionName, 'ext_localconf.php');
			if (file_exists($extLocalconfPath) && is_file($extLocalconfPath)) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				$_EXTKEY = $extensionName;
				// @codingStandardsIgnoreStart
				include($extLocalconfPath);
				// @codingStandardsIgnoreEnd
			}

			$extTablesPath = ExtensionManagementUtility::extPath($extensionName, 'ext_tables.php');
			if (file_exists($extTablesPath) && is_file($extTablesPath)) {
				$this->extTablesPaths[$extensionName] = $extTablesPath;
			}

			$this->importExtension($extensionName);
		}

		// TODO: The hook should be replaced by real clean up and rebuild the whole
		// 'TYPO3_CONF_VARS' in order to have a clean testing environment.
		// hook to load additional files
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'] as $file) {
				$sqlFilename = GeneralUtility::getFileAbsFileName($file);
				$fileContent = GeneralUtility::getUrl($sqlFilename);

				$this->importDatabaseDefinitions($fileContent);
			}
		}
		//parent::importExtensions($extensions, $importDependencies, $skipDependencies);
	}


	/**
	 * Imports the ext_tables.sql file of the extension with the given name
	 * into the test database.
	 *
	 * @param string $extensionName
	 *        the name of the installed extension to import, must not be empty
	 *
	 * @return void
	 */
	protected function importExtension($extensionName) {
		$sqlFilename = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extensionName) . 'ext_tables.sql');
		if (($fileContent = GeneralUtility::getUrl($sqlFilename))) {
			$this->importDatabaseDefinitions($fileContent);
		}
	}


	/**
	 * Imports the data from the stddb tables.sql file.
	 *
	 * Example/intended usage:
	 *
	 * <pre>
	 * public function setUp() {
	 *   $this->createDatabase();
	 *   $db = $this->useTestDatabase();
	 *   $this->importStdDB();
	 *   $this->importExtensions(array('cms', 'static_info_tables', 'templavoila'));
	 * }
	 * </pre>
	 *
	 * @throws \Exception
	 */
	protected function importStdDb() {
		if (ExtensionManagementUtility::isLoaded('core')) {
			$sqlFilename = ExtensionManagementUtility::extPath('core', 'ext_tables.sql');
		} else {
			$sqlFilename = GeneralUtility::getFileAbsFileName(PATH_t3lib . 'stddb/tables.sql');
		}
		if (!file_exists($sqlFilename) || is_dir($sqlFilename)) {
			throw new \Exception('Cannot find STD DB SQL file.');
		}
		$fileContent = GeneralUtility::getUrl($sqlFilename);

		if (class_exists('\TYPO3\CMS\Core\Cache\Cache')) {
			// Add SQL content coming from the caching framework
			$fileContent .= chr(10) . \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();
		} else {
			$fileContent .= chr(10) . \t3lib_cache::getDatabaseTableDefinitions();
		}

		if (class_exists('\TYPO3\CMS\Core\Category\CategoryRegistry')) {
			// Add SQL content coming from the category registry
			$fileContent .= chr(10) . \TYPO3\CMS\Core\Category\CategoryRegistry::getInstance()->getDatabaseTableDefinitions();
		}

		$this->importDatabaseDefinitions($fileContent);
	}

	/**
	 * @param $functionName
	 * @param $folderName
	 * @param string $fileName
	 * @return array
	 */
	protected function getDefaultDataProvider($functionName, $folderName, $fileName = 'data_provider.yml') {
		/** @noinspection PhpIncludeInspection */
		require_once 'PHPUnit/Extensions/Database/DataSet/YamlDataSet.php';

		$result = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($folderName . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . $fileName);

		$tbl = $result->getTable($functionName);

		$dataProvider = array();

		for ($i = 0; $i < $tbl->getRowCount(); $i++) {
			$dataProvider[] = $tbl->getRow($i);
		}

		return $dataProvider;
	}


	/**
	 * Returns a mock object for the specified class.
	 *
	 * @param  string $classNameForRegistration
	 * @param  string $originalClassName
	 * @param  array $methods
	 * @param  array $arguments
	 * @param  string $mockClassName
	 * @param  boolean $callOriginalConstructor
	 * @param  boolean $callOriginalClone
	 * @param  boolean $callAutoload
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function registerExtbaseSingletonMockImplementation(
			$classNameForRegistration,
			$originalClassName = '',
			$methods = array(),
			array $arguments = array(),
			$mockClassName = '',
			$callOriginalConstructor = TRUE,
			$callOriginalClone = TRUE,
			$callAutoload = TRUE) {

		if (!$originalClassName) {
			$originalClassName = $classNameForRegistration;
		}

		$dummyStub = $this->getMock($originalClassName, $methods, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload);
		$mockImplementationClassName = get_class($dummyStub);

		/* @var $extbaseObjectContainer Tx_Extbase_Object_Container_Container */
		$extbaseObjectContainer = GeneralUtility::makeInstance('Tx_Extbase_Object_Container_Container');
		$extbaseObjectContainer->registerImplementation($classNameForRegistration, $mockImplementationClassName);

		$dummyStub->__phpunit_cleanup();
		unset($dummyStub);

		/* @var $objectManager Tx_Extbase_Object_ObjectManager */
		$objectManager = GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->mockObjects[] = $objectManager->get($mockImplementationClassName);

		return $mockImplementationClassName;
	}


	/**
	 * Finds all direct dependencies of the extension with the key $extKey.
	 *
	 * @param string $extKey the key of an installed extension, must not be empty
	 *
	 * @return array<string>|NULL
	 *         the keys of all extensions on which the given extension depends,
	 *         will be NULL if the dependencies could not be determined
	 */
	protected function findDependencies($extKey) {
		$path = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extKey) . 'ext_emconf.php');
		$_EXTKEY = $extKey;
		// @codingStandardsIgnoreStart
		include($path);
		// @codingStandardsIgnoreEnd

		/** @noinspection PhpUndefinedVariableInspection */
		$dependencies = $EM_CONF[$_EXTKEY]['constraints']['depends'];
		if (!is_array($dependencies)) {
			return NULL;
		}

		// remove php and typo3 extension (not real extensions)
		if (isset($dependencies['php'])) {
			unset($dependencies['php']);
		}
		if (isset($dependencies['typo3'])) {
			unset($dependencies['typo3']);
		}

		return array_keys($dependencies);
	}

	/**
	 * Imports the SQL definitions from a (ext_)tables.sql file.
	 *
	 * @param string $definitionContent
	 *        the SQL to import, must not be empty
	 *
	 * @return void
	 */
	protected function importDatabaseDefinitions($definitionContent) {

		if ($this->useDataBaseMemoryEngine) {
			$preserveSql = array();
			$definitionContent = preg_replace('/\)\s*ENGINE\s*=\s*(.*)\s*;/msU', ') ENGINE=MEMORY;', $definitionContent);
			$definitionContent = preg_replace('/\)\s*;/msU', ') ENGINE=MEMORY;', $definitionContent);
			foreach ($this->skipMemoryEngineForTables as $skipMemoryEngineForTable) {
				$matches = NULL;
				if (preg_match_all('/CREATE\s+TABLE\s+' . $skipMemoryEngineForTable . '\s+(.*)ENGINE=MEMORY;/msU', $definitionContent, $matches) > 0) {
					$preserveSql[$skipMemoryEngineForTable] = $matches[1];
				}
			}
			$definitionContent = preg_replace('/\s+text([\s+,])/', ' varchar(1024)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+longtext([\s+,])/', ' varchar(1024)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+mediumtext([\s+,])/', ' varchar(1024)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+tinytext([\s+,])/', ' varchar(255)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+blob([\s+,])/', ' varchar(4096)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+longblob([\s+,])/', ' varchar(4096)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+mediumblob([\s+,])/', ' varchar(1024)$1', $definitionContent);
			$definitionContent = preg_replace('/\s+tinyblob([\s+,])/', ' varchar(255)$1', $definitionContent);

			foreach ($preserveSql as $tableName => $sqlArr) {
				foreach ($sqlArr as $sql) {
					$definitionContent = preg_replace('/CREATE\s+TABLE\s+' . $tableName . '\s+([^;]*)ENGINE=MEMORY;/msU', 'CREATE TABLE ' . $tableName . ' ' . $sql . ';', $definitionContent, 1);
				}
			}
		}

		$sqlHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Sql\\SchemaMigrator');
		/* @var $sqlHandler \TYPO3\CMS\Install\Sql\SchemaMigrator */
		if (method_exists($sqlHandler, 'getFieldDefinitions_fileContent')) {
			$fieldDefinitionsFile = $sqlHandler->getFieldDefinitions_fileContent($definitionContent);
		} else {
			$fieldDefinitionsFile = $sqlHandler->getFieldDefinitions_sqlContent($definitionContent);
		}

		if (empty($fieldDefinitionsFile)) {
			return;
		}

		// find statements to query
		if (method_exists($sqlHandler, 'getFieldDefinitions_fileContent')) {
			$fieldDefinitionsDatabase = $sqlHandler->getFieldDefinitions_fileContent($this->getTestDatabaseSchema());
		} else {
			$fieldDefinitionsDatabase = $sqlHandler->getFieldDefinitions_sqlContent($this->getTestDatabaseSchema());
		}

		$diff = $sqlHandler->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $sqlHandler->getUpdateSuggestions($diff);

		$updateTypes = array('add', 'change', 'create_table');

		foreach ($updateTypes as $updateType) {
			if (array_key_exists($updateType, $updateStatements)) {
				foreach ((array)$updateStatements[$updateType] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
			}
		}
	}


	/**
	 * Returns an SQL dump of the test database.
	 *
	 * @return string SQL dump of the test databse, might be empty
	 */
	protected function getTestDatabaseSchema() {
		$db = $this->useTestDatabase();
		$tables = $this->getDatabaseTables();

		// finds create statement for every table
		$linefeed = chr(10);

		$schema = '';
		$db->sql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
		foreach ($tables as $tableName) {
			$res = $db->sql_query('show create table ' . $tableName);
			$row = $db->sql_fetch_row($res);

			// modifies statement to be accepted by TYPO3
			$createStatement = preg_replace('/ENGINE.*$/', '', $row[1]);
			$createStatement = preg_replace(
				'/(CREATE TABLE.*\()/', $linefeed . '\\1' . $linefeed, $createStatement
			);
			$createStatement = preg_replace('/\) $/', $linefeed . ')', $createStatement);

			$schema .= $createStatement . ';';
		}

		return $schema;
	}


	/**
	 * Verifies the mock object expectations.
	 *
	 * @since Method available since Release 3.5.0
	 */
	protected function verifyMockObjects() {
		foreach ($this->mockObjects as $mockObject) {
			$this->addToAssertionCount(1);
			$mockObject->__phpunit_verify();
			$mockObject->__phpunit_cleanup();
		}

		$this->mockObjects = array();

		parent::verifyMockObjects();
	}

	/**
	 * @param string $src
	 * @param string $dst
	 */
	protected function copyRecursive($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		GeneralUtility::fixPermissions($dst);
		while (FALSE !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
					$this->copyRecursive($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				} else {
					copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
					GeneralUtility::fixPermissions($dst . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * @param string $folder
	 */
	protected function deleteRecursive($folder) {
		GeneralUtility::rmdir($folder, TRUE);
	}

	/**
	 * Injects $dependency into property $name of $target
	 *
	 * This is a convenience method for setting a protected or private property in
	 * a test subject for the purpose of injecting a dependency.
	 *
	 * @param object $target The instance which needs the dependency
	 * @param string $name Name of the property to be injected
	 * @param object $dependency The dependency to inject – usually an object but can also be any other type
	 * @return void
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	protected function inject($target, $name, $dependency) {
		if (!is_object($target)) {
			throw new \InvalidArgumentException('Wrong type for argument $target, must be object.');
		}

		$objectReflection = new \ReflectionObject($target);
		$methodNamePart = strtoupper($name[0]) . substr($name, 1);
		if ($objectReflection->hasMethod('set' . $methodNamePart)) {
			$methodName = 'set' . $methodNamePart;
			$target->$methodName($dependency);
		} elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
			$methodName = 'inject' . $methodNamePart;
			$target->$methodName($dependency);
		} elseif ($objectReflection->hasProperty($name)) {
			$property = $objectReflection->getProperty($name);
			$property->setAccessible(TRUE);
			$property->setValue($target, $dependency);
		} else {
			throw new \RuntimeException('Could not inject ' . $name . ' into object of type ' . get_class($target));
		}
	}
}