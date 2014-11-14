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
 * Class AutoIDService
 *
 * @package EssentialDots\ExtbaseHijax\Service
 */
class AutoIDService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $trackingCache;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->trackingCache = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_storage');
	}

	/**
	 * Clears cache of pages where an object with the given identifier is shown
	 *
	 * @param string $classIdentifier
	 * @return int|mixed
	 */
	public function getAutoId($classIdentifier) {
		$exclusiveLock = NULL;
		$objectIdentifier = 'autoid-' . $classIdentifier;
		$exclusiveLockAcquired = $this->acquireLock($exclusiveLock, $objectIdentifier, TRUE);

		$autoId = 0;

		if ($exclusiveLockAcquired) {
			if ($this->trackingCache->has($objectIdentifier)) {
				$autoId = $this->trackingCache->get($objectIdentifier);
			}
			$autoId++;
			$this->trackingCache->set($objectIdentifier, $autoId);

			$this->releaseLock($exclusiveLock);
		}

		return $autoId;
	}

	/**
	 * Lock the process
	 *
	 * @param $lockObj
	 * @param $key              String to identify the lock in the system
	 * @param bool $exclusive Exclusive lock (shared if FALSE)
	 * @return bool             Returns TRUE if the lock could be obtained, FALSE otherwise
	 */
	protected function acquireLock(&$lockObj, $key, $exclusive = TRUE) {
		try {
			if (!is_object($lockObj)) {
				/* @var $lockObj \EssentialDots\ExtbaseHijax\Lock\Lock */
				$lockObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Lock\\Lock', $key);
			}

			$success = FALSE;
			if (strlen($key)) {
				$success = $lockObj->acquire($exclusive);
				if ($success) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (\Exception $e) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking: Failed to acquire lock: ' . $e->getMessage(), 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			// If locking fails, return with FALSE and continue without locking
			$success = FALSE;
		}

		return $success;
	}

	/**
	 * Release the lock
	 *
	 * @param    \EssentialDots\ExtbaseHijax\Lock\Lock Reference to a locking object
	 * @return    boolean        Returns TRUE on success, FALSE otherwise
	 * @see acquireLock()
	 */
	protected function releaseLock(&$lockObj) {
		$success = FALSE;
		// If lock object is set and was acquired, release it:
		if (is_object($lockObj) && $lockObj instanceof \EssentialDots\ExtbaseHijax\Lock\Lock && $lockObj->getLockStatus()) {
			$success = $lockObj->release();
			$lockObj->sysLog('Released lock');
			$lockObj = NULL;
		}
		return $success;
	}
}