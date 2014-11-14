<?php
namespace EssentialDots\ExtbaseHijax\Lock;

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
 * Class Lock
 *
 * @package EssentialDots\ExtbaseHijax\Lock
 */
class Lock extends AbstractLock {
	/**
	 * @var string Locking method: One of 'flock', 'semaphore' or 'disable'
	 */
	protected $method;

	/**
	 * @var mixed Identifier used for this lock
	 */
	protected $id;

	/**
	 * @var mixed Resource used for this lock (can be a file or a semaphore resource)
	 */
	protected $resource;

	/**
	 * @var resource File pointer if using flock method
	 */
	protected $filepointer;

	/**
	 * @var boolean True if lock is acquired
	 */
	protected $isAcquired = FALSE;

	/**
	 * @var string Logging facility
	 */
	protected $syslogFacility = 'cms';

	/**
	 * @var boolean True if locking should be logged
	 */
	protected $isLoggingEnabled = TRUE;

	/**
	 * Constructor:
	 * initializes locking, check input parameters and set variables accordingly.
	 *
	 * @param string $id ID to identify this lock in the system
	 * @param string $method Define which locking method to use. Defaults to "flock".
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct($id, $method = NULL) {
		// Force ID to be string
		$id = (string)$id;

		if (!$method) {
			$method = $GLOBALS['TYPO3_CONF_VARS']['SYS']['extbase_hijax']['lockingMode'];
		}

		$this->method = $method;

		switch ($this->method) {
			case 'flock':
				$genTempPath = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . 'extbase_hijax' . DIRECTORY_SEPARATOR;
				if (!is_dir($genTempPath)) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($genTempPath);
				}
				$path = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . 'extbase_hijax' . DIRECTORY_SEPARATOR . 'locks' . DIRECTORY_SEPARATOR;
				if (!is_dir($path)) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($path);
				}
				$this->id = md5($id);
				$this->resource = $path . $this->id;
				break;
			case 'semaphore':
				$this->id = abs(crc32($id));
				if (($this->resource = sem_get($this->id, 1)) === FALSE) {
					throw new \Exception(
						'Unable to get semaphore',
						1313828196
					);
				}
				break;
			case 'disable':
				break;
			default:
				throw new \InvalidArgumentException(
					'No such method "' . $method . '"',
					1294586097
				);
		}
	}

	/**
	 * Destructor:
	 * Releases lock automatically when instance is destroyed.
	 *
	 * @return    void
	 */
	public function __destruct() {
		// we don't want to automatically release on destruct
		// in the context of a child process
		// see https://bugs.php.net/bug.php?id=47227 and http://linux.die.net/man/2/flock for more details
		if (function_exists('posix_getppid') && posix_getppid() == 0) {
			$this->release();
		}
	}

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @param bool $exclusive
	 * @return bool
	 * @throws \RuntimeException
	 */
	public function acquire($exclusive = TRUE) {
		$isAcquired = FALSE;

		switch ($this->method) {
			case 'flock':
				if (($this->filepointer = fopen($this->resource, 'w+')) == FALSE) {
					throw new \RuntimeException('Lock file could not be opened', 1294586099);
				}

				if ($exclusive) {
					if (flock($this->filepointer, LOCK_EX) == TRUE) {
						$isAcquired = TRUE;
					}
				} else {
					// shared lock
					if (flock($this->filepointer, LOCK_SH) == TRUE) {
						$isAcquired = TRUE;
					}
				}
				break;
			case 'semaphore':
				if (sem_acquire($this->resource)) {
					// Unfortunately it seems not possible to find out if the request was blocked, so we return FALSE in any case to make sure the operation is tried again.
					$isAcquired = TRUE;
				}
				break;
			case 'disable':
				$isAcquired = TRUE;
				break;
			default:
				$isAcquired = FALSE;
		}

		$this->isAcquired = $isAcquired;

		return $this->isAcquired;
	}

	/**
	 * Release the lock
	 *
	 * @return    boolean        Returns TRUE on success or FALSE on failure
	 */
	public function release() {
		if (!$this->isAcquired) {
			return TRUE;
		}

		$success = TRUE;
		switch ($this->method) {
			case 'flock':
				if (flock($this->filepointer, LOCK_UN) == FALSE) {
					$success = FALSE;
				}
				fclose($this->filepointer);
				//if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($this->resource) &&
				//    \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->resource, PATH_site . 'typo3temp'.DIRECTORY_SEPARATOR.'extbase_hijax'.DIRECTORY_SEPARATOR.'locks'.DIRECTORY_SEPARATOR)) {
				// TODO: add a scheduler task to remove old lock files
				// unlink($this->resource);
				//}
				break;
			case 'semaphore':
				if (@sem_release($this->resource)) {
					sem_remove($this->resource);
				} else {
					$success = FALSE;
				}
				break;
			case 'disable':
				$success = FALSE;
				break;
			default;
				$success = FALSE;
		}

		$this->isAcquired = FALSE;
		return $success;
	}

	/**
	 * Return the locking method which is currently used
	 *
	 * @return    string        Locking method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Return the ID which is currently used
	 *
	 * @return    string        Locking ID
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Return the resource which is currently used.
	 * Depending on the locking method this can be a filename or a semaphore resource.
	 *
	 * @return    mixed        Locking resource (filename as string or semaphore as resource)
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Return the status of a lock
	 *
	 * @return    string        Returns TRUE if lock is acquired, FALSE otherwise
	 */
	public function getLockStatus() {
		return $this->isAcquired;
	}

	/**
	 * Sets the facility (extension name) for the syslog entry.
	 *
	 * @param string $syslogFacility
	 */
	public function setSyslogFacility($syslogFacility) {
		$this->syslogFacility = $syslogFacility;
	}

	/**
	 * Enable/ disable logging
	 *
	 * @param boolean $isLoggingEnabled
	 */
	public function setEnableLogging($isLoggingEnabled) {
		$this->isLoggingEnabled = $isLoggingEnabled;
	}

	/**
	 * Adds a common log entry for this locking API using \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog().
	 * Example: 25-02-08 17:58 - cms: Locking [flock::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
	 *
	 * @param    string $message : The message to be logged
	 * @param    integer $severity : Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return    void
	 */
	public function sysLog($message, $severity = 0) {
		if ($this->isLoggingEnabled) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking [' . $this->method . '::' . $this->id . ']: ' . trim($message), $this->syslogFacility, $severity);
		}
	}
}