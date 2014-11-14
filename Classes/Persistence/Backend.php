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

/**
 * Class Backend
 *
 * @package EssentialDots\ExtbaseHijax\Persistence
 */
class Backend extends \TYPO3\CMS\Extbase\Persistence\Generic\Backend {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $sessionAddedObjects = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $sessionRemovedObjects = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $sessionChangedObjects = NULL;

	/**
	 * @var array
	 */
	protected $pendingInsertObjects = array();

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Inserts an object in the storage backend
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be insterted in the storage
	 * @return void
	 */
	protected function insertObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'beforeInsertObjectHijax', array('object' => $object));

		parent::insertObject($object);

		if ($object->getUid() >= 1) {
			/*
			 * Check if update operation will be called for this object
			 * (depending on the properties)
			 * @see \TYPO3\CMS\Extbase\Persistence\Generic\Backend::persistObject
			 */
			$dataMap = $this->dataMapper->getDataMap(get_class($object));
			$properties = $object->_getProperties();
			$row = array();
			foreach ($properties as $propertyName => $propertyValue) {
				if (!$propertyValue || !is_object($propertyValue) || !$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
					continue;
				}
				$columnMap = $dataMap->getColumnMap($propertyName);
				if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
					if ($object->_isNew() || $propertyValue->_isDirty()) {
						$row[$columnMap->getColumnName()] = TRUE;
					}
				} elseif ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
					if ($object->_isDirty($propertyName)) {
						$row[$columnMap->getColumnName()] = TRUE;
					}
					$queue[] = $propertyValue;
				} elseif ($object->_isNew() || $object->_isDirty($propertyName)) {
					$row[$columnMap->getColumnName()] = TRUE;
				}
			}

			if (count($row) > 0) {
				$objectHash = spl_object_hash($object);
				$this->pendingInsertObjects[$objectHash] = $object;
			} else {
				$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterInsertObjectHijax', array('object' => $object));
				$this->sessionAddedObjects->attach($object);
			}
		}
	}

	/**
	 * Updates a given object in the storage
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be updated
	 * @param array $row Row to be stored
	 * @return bool
	 */
	protected function updateObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array $row) {
		$objectHash = spl_object_hash($object);

		if (!$this->pendingInsertObjects[$objectHash]) {
			$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'beforeUpdateObjectHijax', array('object' => $object, 'row' => &$row));
		}

		$result = parent::updateObject($object, $row);

		if ($result === TRUE) {
			if (!$this->pendingInsertObjects[$objectHash]) {
				$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterUpdateObjectHijax', array('object' => $object, 'row' => &$row));
				$this->sessionChangedObjects->attach($object);
			} else {
				unset($this->pendingInsertObjects[$objectHash]);
				$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterInsertObjectHijax', array('object' => $object, 'row' => &$row));
				$this->sessionAddedObjects->attach($object);
			}
		}

		return $result;
	}

	/**
	 * Deletes an object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be removed from the storage
	 * @param bool $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 */
	protected function removeObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'beforeRemoveObjectHijax', array('object' => $object));

		// TODO: check if object is not already deleted
		parent::removeObject($object, $markAsDeleted);

		// TODO: check if object is removed indeed
		$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterRemoveObjectHijax', array('object' => $object));
		$this->sessionRemovedObjects->attach($object);
	}

	/**
	 * Deletes an object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object The object to be removed from the storage
	 * @param boolean $markAsDeleted Wether to just flag the row deleted (default) or really delete it
	 * @return void
	 */
	protected function removeEntity(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, $markAsDeleted = TRUE) {
		$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'beforeRemoveObjectHijax', array('object' => $object));

		// TODO: check if object is not already deleted
		parent::removeEntity($object, $markAsDeleted);

		// TODO: check if object is removed indeed
		$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterRemoveObjectHijax', array('object' => $object));
		$this->sessionRemovedObjects->attach($object);
	}

	/**
	 * Commits the current persistence session.
	 *
	 * @return void
	 */
	public function commit() {
		$this->sessionAddedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$this->sessionRemovedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$this->sessionChangedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');

		parent::commit();

		foreach ($this->sessionAddedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterInsertCommitObjectHijax', array('object' => $object));
		}
		foreach ($this->sessionRemovedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterRemoveCommitObjectHijax', array('object' => $object));
		}
		foreach ($this->sessionChangedObjects as $object) {
			$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', 'afterUpdateCommitObjectHijax', array('object' => $object));
		}
		$this->sessionAddedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$this->sessionRemovedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$this->sessionChangedObjects = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
	}
}
