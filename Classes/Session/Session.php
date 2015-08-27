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
	 * @var string
	 */
	protected $sessionId = '';

	/**
	 * @return string
	 */
	public function getId() {
		$this->start();

		return $this->sessionId;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		$this->start();

		return $GLOBALS['_SESSION'][$key];
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	public function set($key, $value) {
		$this->start();

		$GLOBALS['_SESSION'][$key] = $value;

		return $GLOBALS['_SESSION'][$key];
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	public function setIfNotExist($key, $value) {
		$this->start();

		if (!isset($GLOBALS['_SESSION'][$key])) {
			$GLOBALS['_SESSION'][$key] = $value;
		}

		return $GLOBALS['_SESSION'][$key];
	}

	/**
	 * @param bool $startRemoteEvenIfLocalSessionExist
	 * @return bool
	 */
	public function start($startRemoteEvenIfLocalSessionExist = FALSE) {
		$result = TRUE;

		if (!$this->sessionStarted) {
			if ($startRemoteEvenIfLocalSessionExist || !$GLOBALS['_SESSION'] || count($GLOBALS['_SESSION']) === 0) {
				$result = session_start();
				if ($result) {
					$this->sessionId = session_id();
				} else {
					$this->sessionId = '';
				}
			}
			$this->sessionStarted = $result;
		}

		return $result;
	}

	/**
	 * @param bool $unsetLocalSession
	 * @return bool
	 */
	public function commit($unsetLocalSession = TRUE) {
		$result = FALSE;

		if ($this->sessionStarted) {
			$result = TRUE;
			$this->sessionStarted = FALSE;
			session_write_close();

			if ($unsetLocalSession) {
				unset($GLOBALS['_SESSION']);
				$GLOBALS['_SESSION'] = NULL;
			}
		}

		return $result;
	}
}