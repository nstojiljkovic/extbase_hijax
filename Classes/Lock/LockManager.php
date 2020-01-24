<?php
namespace EssentialDots\ExtbaseHijax\Lock;

use Psr\Log\LoggerAwareTrait;

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
 * Class LockManager
 *
 * @package EssentialDots\ExtbaseHijax\Lock
 */
class LockManager implements \TYPO3\CMS\Core\SingletonInterface, \Psr\Log\LoggerAwareInterface {

	use LoggerAwareTrait;

	/**
	 * @var array
	 */
	protected $existingLocksCount = array();

	/**
	 * @var array
	 */
	protected $lockKeyType = array();

	/**
	 * @var array
	 */
	protected $lockObjectsKeys = array();

	/**
	 * Lock the process
	 *
	 * @param \EssentialDots\ExtbaseHijax\Lock\Lock $lockObj
	 * @param $key                  String to identify the lock in the system
	 * @param bool $exclusive Exclusive lock (shared if FALSE)
	 * @param NULL|string $methodOrLockClass
	 * @return bool                 Returns TRUE if the lock could be obtained, FALSE otherwise
	 */
	public function acquireLock(&$lockObj, $key, $exclusive = TRUE, $methodOrLockClass = NULL) {
		try {
			if (!is_object($lockObj)) {
				if (!$methodOrLockClass) {
					$methodOrLockClass = $GLOBALS['TYPO3_CONF_VARS']['SYS']['extbase_hijax']['lockingMode'];
				}

				if ($methodOrLockClass && class_exists($methodOrLockClass) && is_subclass_of($methodOrLockClass, 'EssentialDots\\ExtbaseHijax\\Lock\\AbstractLock')) {
					$lockObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($methodOrLockClass, $key);
				} else {
					/* @var $lockObj \EssentialDots\ExtbaseHijax\Lock\AbstractLock */
					$lockObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Lock\\Lock', $key, $methodOrLockClass);
				}
			}

			if (array_key_exists($key, $this->lockKeyType) && $this->lockKeyType[$key] !== $exclusive) {
				error_log('The same key cannot be used for shared and exclusive locks atm. Key: ' . $key);
				return FALSE;
			}

			$this->lockKeyType[$key] = $exclusive;
			$this->lockObjectsKeys[spl_object_hash($lockObj)] = $key;

			if (array_key_exists($key, $this->existingLocksCount) && $this->existingLocksCount[$key] > 0) {
				$this->existingLocksCount[$key]++;
				return TRUE;
			} else {
				$this->existingLocksCount[$key] = 1;
			}

			$success = FALSE;
			if (strlen($key)) {
				$success = $lockObj->acquire($exclusive);
				if ($success) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (\Exception $e) {
			// @extensionScannerIgnoreLine
			$this->logger->error('Locking: Failed to acquire lock: ' . $e->getMessage());
			// If locking fails, return with FALSE and continue without locking
			$success = FALSE;
		}

		return $success;
	}

	/**
	 * Release the lock
	 *
	 * @param    \EssentialDots\ExtbaseHijax\Lock\AbstractLock Reference to a locking object
	 * @return    boolean        Returns TRUE on success, FALSE otherwise
	 * @see acquireLock()
	 */
	public function releaseLock(&$lockObj) {
		$success = FALSE;
		// If lock object is set and was acquired, release it:
		if (is_object($lockObj) && $lockObj instanceof \EssentialDots\ExtbaseHijax\Lock\AbstractLock) {
			if (!array_key_exists(spl_object_hash($lockObj), $this->lockObjectsKeys)) {
				return FALSE;
			} else {
				$key = $this->lockObjectsKeys[spl_object_hash($lockObj)];
				$leftoverLocks = --$this->existingLocksCount[$key];
			}
			unset($this->lockObjectsKeys[spl_object_hash($lockObj)]);

			if ($leftoverLocks == 0 && $lockObj->getLockStatus()) {
				unset($this->lockKeyType[$key]);
				unset($this->existingLocksCount[$key]);

				$success = $lockObj->release();
				$lockObj->sysLog('Released lock');
				$lockObj = NULL;
			} elseif ($leftoverLocks == 0) {
				error_log('The lock created using LockManager was unlocked directly. Please avoid this!. Lock key: ' . $key);
			}
		}
		return $success;
	}
}