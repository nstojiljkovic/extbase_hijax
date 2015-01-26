<?php
namespace EssentialDots\ExtbaseHijax\Session;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Essential Dots d.o.o. Belgrade
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
 * Class Session
 *
 * @package EssentialDots\ExtbaseHijax\Session
 */
class Session extends AbstractSession {

	/**
	 * @var bool
	 */
	protected $sessionStarted = FALSE;

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		$this->start();

		// @codingStandardsIgnoreStart
		return $_SESSION[$key];
		// @codingStandardsIgnoreEnd
	}

	/**
	 * @param string $key
	 * @param $value
	 */
	public function set($key, $value) {
		$this->start();

		// @codingStandardsIgnoreStart
		$_SESSION[$key] = $value;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	public function setIfNotExist($key, $value) {
		$this->start();

		// @codingStandardsIgnoreStart
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = $value;
		}

		return $_SESSION[$key];
		// @codingStandardsIgnoreEnd
	}

	/**
	 * @return bool
	 */
	public function start() {
		$result = TRUE;

		if (!$this->sessionStarted) {
			$result = session_start();
			$this->sessionStarted = $result;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function commit() {
		$result = FALSE;

		if ($this->sessionStarted) {
			$result = TRUE;
			$this->sessionStarted = FALSE;
			session_write_close();

			// @codingStandardsIgnoreStart
			unset($_SESSION);
			$_SESSION = NULL;
			// @codingStandardsIgnoreEnd
		}

		return $result;
	}
}