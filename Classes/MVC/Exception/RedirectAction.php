<?php
namespace EssentialDots\ExtbaseHijax\MVC\Exception;

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
 * Class RedirectAction
 *
 * @package EssentialDots\ExtbaseHijax\MVC\Exception
 */
class RedirectAction extends \TYPO3\CMS\Extbase\Mvc\Exception {

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $httpStatus;

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getHttpStatus() {
		return $this->httpStatus;
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * @param string $httpStatus
	 */
	public function setHttpStatus($httpStatus) {
		$this->httpStatus = $httpStatus;
	}


}
