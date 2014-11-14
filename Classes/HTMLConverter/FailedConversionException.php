<?php
namespace EssentialDots\ExtbaseHijax\HTMLConverter;

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
 * Class FailedConversionException
 *
 * @package EssentialDots\ExtbaseHijax\HTMLConverter
 */
class FailedConversionException extends \Exception {
	/**
	 * @var int
	 */
	protected $returnValue;

	/**
	 * @var string
	 */
	protected $output;

	/**
	 * @var string
	 */
	protected $input;

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @param string $error
	 * @return void
	 */
	public function setError($error) {
		$this->error = $error;
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @param string $input
	 */
	public function setInput($input) {
		$this->input = $input;
	}

	/**
	 * @return string
	 */
	public function getInput() {
		return $this->input;
	}

	/**
	 * @param string $output
	 */
	public function setOutput($output) {
		$this->output = $output;
	}

	/**
	 * @return string
	 */
	public function getOutput() {
		return $this->output;
	}

	/**
	 * @param int $returnValue
	 */
	public function setReturnValue($returnValue) {
		$this->returnValue = $returnValue;
	}

	/**
	 * @return int
	 */
	public function getReturnValue() {
		return $this->returnValue;
	}


}