<?php
namespace EssentialDots\ExtbaseHijax\Persistence;

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
use TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface;

/**
 * Class Backend
 *
 * @package EssentialDots\ExtbaseHijax\Persistence
 */
class Backend extends \TYPO3\CMS\Extbase\Persistence\Generic\Backend {

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Inserts an object in the storage backend
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject
	 * @param string $parentPropertyName
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 * @return void
	 */
	protected function insertObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $parentObject = NULL, $parentPropertyName = '') {

		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject) {
			$result = $this->getUidOfAlreadyPersistedValueObject($object);
			if ($result !== FALSE) {
				$object->_setProperty('uid', (int)$result);
				return;
			}
		}
		$dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
		$row = array();
		$this->addCommonFieldsToRow($object, $row);
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = -1;
		}

		$dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
				continue;
			}
			$columnMap = $dataMap->getColumnMap($propertyName);
			if (is_null($propertyValue)) {
				// ignore null values at this stage
				continue;
			} elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
				// just skip, it's too much to deal with ObjectStorage atm
				continue;
			} elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
				&& $object instanceof ObjectMonitoringInterface) {
				if ($object->_isDirty($propertyName)) {
					if (!$propertyValue->_isNew()) {
						$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue);
						if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
							$object->_memorizeCleanState($propertyName);
						}
					}
				}
			} else {
				$row[$columnMap->getColumnName()] = $this->getPlainValue($propertyValue, $columnMap);
				if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
					$object->_memorizeCleanState($propertyName);
				}
			}
		}

		if ($parentObject !== NULL && $parentPropertyName) {
			$parentColumnDataMap = $this->dataMapFactory->buildDataMap(get_class($parentObject))->getColumnMap($parentPropertyName);
			$relationTableMatchFields = $parentColumnDataMap->getRelationTableMatchFields();
			if (is_array($relationTableMatchFields)) {
				$row = array_merge($relationTableMatchFields, $row);
			}
			if ($parentColumnDataMap->getParentKeyFieldName() !== NULL) {
				$row[$parentColumnDataMap->getParentKeyFieldName()] = (int)$parentObject->getUid();
			}
		}

		//////////////////// PATCH START //////////////////////
		$params = array(
			'pObj' => &$this,
			'object' => &$object,
			'tableName' => $dataMap->getTableName(),
			'row' => &$row,
			'isRelation' => FALSE
		);
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeAddRow', $params);
		//////////////////// PATCH END //////////////////////

		$uid = $this->storageBackend->addRow($dataMap->getTableName(), $row);
		$object->_setProperty('uid', (int)$uid);

		//////////////////// PATCH START //////////////////////
		$params = array(
			'pObj' => &$this,
			'object' => &$object,
			'tableName' => $dataMap->getTableName(),
			'row' => &$row,
			'isRelation' => FALSE,
			'result' => $uid
		);
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterAddRow', $params);
		//////////////////// PATCH END //////////////////////

		if ((int)$uid >= 1) {
			$this->emitAfterInsertObjectSignal($object);
		}
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $uid);
		}
		$this->session->registerObject($object, $uid);
	}

	/**
	 * Updates a given object in the storage
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be updated
	 * @param array $row Row to be stored
	 * @return bool
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	protected function updateObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array $row) {
		//////////////////// ORIGINAL START //////////////////////
		$dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
		$this->addCommonFieldsToRow($object, $row);
		$row['uid'] = $object->getUid();
		if ($dataMap->getLanguageIdColumnName() !== NULL) {
			$row[$dataMap->getLanguageIdColumnName()] = $object->_getProperty('_languageUid');
			if ($object->_getProperty('_localizedUid') !== NULL) {
				$row['uid'] = $object->_getProperty('_localizedUid');
			}
		}

		//////////////////// PATCH START //////////////////////
		$params = array(
			'pObj' => &$this,
			'object' => &$object,
			'tableName' => $dataMap->getTableName(),
			'row' => &$row,
			'isRelation' => FALSE
		);
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeUpdateRow', $params);
		//////////////////// PATCH END //////////////////////
		$result = $this->storageBackend->updateRow($dataMap->getTableName(), $row);
		//////////////////// PATCH START //////////////////////
		$params = array(
			'pObj' => &$this,
			'object' => &$object,
			'tableName' => $dataMap->getTableName(),
			'row' => &$row,
			'isRelation' => FALSE,
			'result' => $result
		);
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterUpdateRow', $params);

		if ($result === TRUE) {
			$this->emitAfterUpdateObjectSignal($object);
		}
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($dataMap->getTableName(), $row['uid']);
		}
		//////////////////// ORIGINAL END //////////////////////

		return $result;
	}

	/**
	 * Deletes an object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be removed from the storage
	 * @param boolean $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	protected function removeEntity(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$dataMap = $this->dataMapFactory->buildDataMap(get_class($object));
		$tableName = $dataMap->getTableName();
		if ($markAsDeleted === TRUE && $dataMap->getDeletedFlagColumnName() !== NULL) {
			$deletedColumnName = $dataMap->getDeletedFlagColumnName();
			$row = array(
				'uid' => $object->getUid(),
				$deletedColumnName => 1
			);
			$this->addCommonDateFieldsToRow($object, $row);
			//////////////////// PATCH START //////////////////////
			$params = array(
				'pObj' => &$this,
				'object' => &$object,
				'tableName' => $tableName,
			);
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeMarkAsDeletedRow', $params);
			//////////////////// PATCH END //////////////////////
			$res = $this->storageBackend->updateRow($tableName, $row);
			//////////////////// PATCH START //////////////////////
			$params = array(
				'pObj' => &$this,
				'object' => &$object,
				'tableName' => $tableName,
				'result' => $res
			);
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterMarkAsDeletedRow', $params);
			//////////////////// PATCH END //////////////////////
		} else {
			//////////////////// PATCH START //////////////////////
			$params = array(
				'pObj' => &$this,
				'object' => &$object,
				'tableName' => $tableName,
			);
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeRemoveRow', $params);
			//////////////////// PATCH END //////////////////////
			$res = $this->storageBackend->removeRow($tableName, array('uid' => $object->getUid()));
			//////////////////// PATCH START //////////////////////
			$params = array(
				'pObj' => &$this,
				'object' => &$object,
				'tableName' => $tableName,
				'result' => $res
			);
			$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterRemoveRow', $params);
			//////////////////// PATCH END //////////////////////
		}
		if ($res === TRUE) {
			$this->emitAfterRemoveObjectSignal($object);
		}
		$this->removeRelatedObjects($object);
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($frameworkConfiguration['persistence']['updateReferenceIndex'] === '1') {
			$this->referenceIndex->updateRefIndexTable($tableName, $object->getUid());
		}
	}
}
