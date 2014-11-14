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
 * Class AbstractLock
 *
 * @package EssentialDots\ExtbaseHijax\Lock
 */
abstract class AbstractLock {

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @param bool $exclusive
	 * @return bool
	 * @throws \RuntimeException
	 */
	abstract public function acquire($exclusive = TRUE);

	/**
	 * Release the lock
	 *
	 * @return    boolean        Returns TRUE on success or FALSE on failure
	 */
	abstract public function release();

	/**
	 * Return the ID which is currently used
	 *
	 * @return    string        Locking ID
	 */
	abstract public function getId();

	/**
	 * Return the status of a lock
	 *
	 * @return    string        Returns TRUE if lock is acquired, FALSE otherwise
	 */
	abstract public function getLockStatus();

	/**
	 * @param    string $message : The message to be logged
	 * @param    integer $severity : Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return    void
	 */
	abstract public function sysLog($message, $severity = 0);

}