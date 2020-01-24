<?php
namespace EssentialDots\ExtbaseHijax\Event;

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
 * Class CObj
 *
 * @package EssentialDots\ExtbaseHijax\Event
 */
class CObj {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var bool|int
	 */
	protected $userObjectType = FALSE;

	/**
	 * Constructs a new \EssentialDots\ExtbaseHijax\Event\Listener.
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject An array of parameters
	 */
	public function __construct($contentObject = NULL) {
		$this->contentObject = $contentObject;

		$reset = TRUE;

		$this->userObjectType = $this->contentObject->getUserObjectType();

		if ($this->contentObject && $this->contentObject->currentRecord) {
			list($table, $uid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->contentObject->currentRecord);
			if ($table == 'tt_content' && $uid) {
				$this->data = $this->contentObject->data;
				list($this->table) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $this->contentObject->currentRecord);
				$reset = FALSE;
			}
		}

		if ($reset) {
			$this->data = ($this->contentObject && $this->contentObject->data) ? $this->contentObject->data : array();
			$this->table = '';
			$this->contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$this->contentObject->start($this->data, $this->table);
		}
	}

	/**
	 * @return void
	 */
	public function reconstitute() {
		$this->contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->contentObject->start($this->data, $this->table);
	}

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public function getContentObject() {
		return $this->contentObject;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 */
	public function setContentObject($contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}

	/**
	 * @param bool|int $userObjectType
	 */
	public function setUserObjectType($userObjectType) {
		$this->userObjectType = $userObjectType;
	}

	/**
	 * @return bool|int
	 */
	public function getUserObjectType() {
		return $this->userObjectType;
	}
}