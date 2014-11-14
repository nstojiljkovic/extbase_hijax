<?php
namespace EssentialDots\ExtbaseHijax\Service\Serialization;

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
 * Class RequestFactory
 *
 * @package EssentialDots\ExtbaseHijax\Service\Serialization
 */
class RequestFactory extends \EssentialDots\ExtbaseHijax\Service\Serialization\AbstractFactory {

	/**
	 * @var array
	 */
	protected $properties = array(
		'format', 'method', 'isCached', 'baseUri', 'controllerObjectName',
		'pluginName', 'controllerExtensionName', 'controllerExtensionKey',
		'controllerSubpackageKey', 'controllerName', 'controllerActionName');

	/**
	 * Unserialize an object
	 *
	 * @param string $str
	 * @return object
	 */
	public function unserialize($str) {
		$object = parent::unserialize($str);

		$object->setRequestUri(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));

		return $object;
	}
}