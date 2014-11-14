<?php
namespace EssentialDots\ExtbaseHijax\Property\TypeConverter;

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
 * Class ObjectStorageConverter
 *
 * @package EssentialDots\ExtbaseHijax\Property\TypeConverter
 */
class ObjectStorageConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\ObjectStorageConverter {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Property\TypeConverterService\ObjectStorageMappingService
	 * @inject
	 */
	protected $objectStorageMappingService;

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		foreach ($convertedChildProperties as $subPropertyKey => $subProperty) {
			$objectStorage->attach($subProperty);
			$this->objectStorageMappingService->mapSplObjectHashToSourceKey(spl_object_hash($subProperty), $subPropertyKey);
		}
		return $objectStorage;
	}
}
