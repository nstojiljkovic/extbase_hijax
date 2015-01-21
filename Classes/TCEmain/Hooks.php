<?php
namespace EssentialDots\ExtbaseHijax\TCEmain;

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
 * Class Hooks
 *
 * @package EssentialDots\ExtbaseHijax\TCEmain
 */
class Hooks implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Tracking\Manager
	 */
	protected $trackingManager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->trackingManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Tracking\\Manager');
	}

	/**
	 * @var array
	 */
	protected $pendingIdentifiers = array();

	/**
	 * Clear cache post processor.
	 *
	 * @param object $params parameter array
	 * @param object $pObj parent object
	 * @return void
	 */
	public function clearCachePostProc(&$params, &$pObj) {
		switch ($params['cacheCmd']) {
			case 'all':
				$this->trackingManager->flushTrackingInfo();
				break;
			default:
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param    array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	// @codingStandardsIgnoreStart
	public function processDatamap_preProcessFieldArray($fieldArray, $table, $id, $pObj) {
		// not used atm
	}
	// @codingStandardsIgnoreEnd

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param    string $status Operation status
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	// @codingStandardsIgnoreStart
	public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, $pObj) {
		// not used atm
	}
	// @codingStandardsIgnoreEnd

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param    string $command Action to be performed
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    string $value
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	// @codingStandardsIgnoreStart
	public function processCmdmap_preProcess($command, $table, $id, $value, $pObj) {
		$this->processCmdmapPreProcessImpl($command, $table, $id, $value, $pObj);
	}
	// @codingStandardsIgnoreEnd

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param    string $command Action to be performed
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    string $value
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	public function processCmdmapPreProcessImpl($command, $table, $id, $value, $pObj) {
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
			$objectIdentifier = $this->trackingManager->getObjectIdentifierForRecord($table, $id);
			if (!in_array($objectIdentifier, $this->pendingIdentifiers)) {
				$this->pendingIdentifiers[] = $objectIdentifier;
			}

			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);
			$pid = $row['pid'];

			if ($pid > 0) {
				$objectIdentifier = $this->trackingManager->getObjectIdentifierForRepository($table, $pid);
				if (!in_array($objectIdentifier, $this->pendingIdentifiers)) {
					$this->pendingIdentifiers[] = $objectIdentifier;
				}
				$objectIdentifier = $this->trackingManager->getObjectIdentifierForRepository($table, -1);
				if (!in_array($objectIdentifier, $this->pendingIdentifiers)) {
					$this->pendingIdentifiers[] = $objectIdentifier;
				}
			}
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param    string $command Action to be performed
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    string $value
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	// @codingStandardsIgnoreStart
	public function processCmdmap_postProcess($command, $table, $id, $value, $pObj) {
		$this->processCmdmapPostProcessImpl($command, $table, $id, $value, $pObj);
	}
	// @codingStandardsIgnoreEnd

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a delete action is performed on a record.
	 *
	 * @param    string $command Action to be performed
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $id The records id (if any)
	 * @param    string $value
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	protected function processCmdmapPostProcessImpl($command, $table, $id, $value, $pObj) {
		while (($objectIdentifier = array_pop($this->pendingIdentifiers))) {
			$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param    string $status Operation status
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $rawId The records id (if any)
	 * @param    array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	// @codingStandardsIgnoreStart
	public function processDatamap_afterDatabaseOperations($status, $table, $rawId, $fieldArray, $pObj) {
		$this->processDatamapAfterDatabaseOperationsImpl($status, $table, $rawId, $fieldArray, $pObj);
	}
	// @codingStandardsIgnoreEnd

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param    string $status Operation status
	 * @param    string $table The table TCEmain is currently processing
	 * @param    string $rawId The records id (if any)
	 * @param    array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $pObj Reference to the parent object (TCEmain)
	 * @return    void
	 */
	protected function processDatamapAfterDatabaseOperationsImpl($status, $table, $rawId, $fieldArray, $pObj) {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rawId)) {
			$rawId = $pObj->substNEWwithIDs[$rawId];
		}
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rawId)) {
			$objectIdentifier = $this->trackingManager->getObjectIdentifierForRecord($table, $rawId);
			$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);

			if ($fieldArray['pid'] && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($fieldArray['pid'])) {
				$pid = $fieldArray['pid'];
			} else {
				$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $rawId);
				$pid = $row['pid'];
			}

			if ($pid > 0) {
				$objectIdentifier = $this->trackingManager->getObjectIdentifierForRepository($table, $pid);
				$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
				$objectIdentifier = $this->trackingManager->getObjectIdentifierForRepository($table, -1);
				$this->trackingManager->clearPageCacheForObjectIdentifier($objectIdentifier);
			}
		}
	}
}