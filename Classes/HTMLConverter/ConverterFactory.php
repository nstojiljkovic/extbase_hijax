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
 * Class ConverterFactory
 *
 * @package EssentialDots\ExtbaseHijax\HTMLConverter
 */
class ConverterFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $converterClassNames = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * @param $format
	 * @return \EssentialDots\ExtbaseHijax\HTMLConverter\AbstractConverter
	 */
	public function getConverter($format) {
		$converter = NULL;
		if ($this->converterClassNames[$format]) {
			$converter = $this->objectManager->get($this->converterClassNames[$format]);
		} elseif (class_exists('EssentialDots\\ExtbaseHijax\\HTMLConverter\\' . strtoupper($format) . 'Converter')) {
			$converter = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\HTMLConverter\\' . strtoupper($format) . 'Converter');
		} elseif (strpos($format, '.html') !== FALSE) {
			$converter = $this->getConverter(str_replace('.html', '', $format));
		} else {
			$converter = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\HTMLConverter\\NullConverter');
		}

		return $converter;
	}

	/**
	 * @param string $format
	 * @param string $converterClassName
	 */
	public function registerConverter($format, $converterClassName) {
		$this->converterClassNames[$format] = $converterClassName;
	}
}