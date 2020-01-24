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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractFactory
 *
 * @package EssentialDots\ExtbaseHijax\Service\Serialization
 */
abstract class AbstractFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $storage;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\Container\Container
	 */
	protected $objectContainer;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $objectCache;

	/**
	 * Constructor
	 * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
	 */
	public function __construct() {
		$this->objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var CacheManager $cacheManager */
		$cacheManager = $GLOBALS['typo3CacheManager'] ?: GeneralUtility::makeInstance(CacheManager::class);
		$this->storage = $cacheManager->getCache('extbase_hijax_storage');
		$this->objectCache = array();
	}

	/**
	 * Serialize an object
	 *
	 * @param object $object
	 * @return string
	 */
	public function serialize($object) {
		$properties = array();
		foreach ($this->properties as $property) {
			if (method_exists($object, 'get' . ucfirst($property))) {
				$properties[$property] = call_user_func(array($object, 'get' . ucfirst($property)));
			} elseif (method_exists($object, $property)) {
				$properties[$property] = call_user_func(array($object, $property));
			}
		}

		return serialize(array('properties' => $properties, 'className' => get_class($object)));
	}

	/**
	 * Unserialize an object
	 *
	 * @param string $str
	 * @return object
	 */
	public function unserialize($str) {
		$o = unserialize($str);
		$className = $o['className'];
		$object = $this->objectContainer->getEmptyObject($className);

		foreach ($o['properties'] as $property => $value) {
			if (method_exists($object, 'set' . ucfirst($property))) {
				call_user_func(array($object, 'set' . ucfirst($property)), $value);
			}
		}

		return $object;
	}

	/**
	 * @param object $object
	 * @return bool
	 */
	public function persist($object) {
		$id = $this->getIdForObject($object);
		if ($id) {
			if (!$this->storage->has($id)) {
				$this->storage->set($id, $this->serialize($object));
			}
			$result = TRUE;
		} else {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * @param string $id
	 * @return object
	 */
	public function findById($id) {
		$fullId = 'serialized-' . str_replace('\\', '_', get_class($this)) . '-' . $id;
		$object = NULL;

		if ($this->objectCache[$fullId]) {
			$object = $this->objectCache[$fullId];
		} elseif ($this->storage->has($fullId)) {
			$object = $this->unserialize($this->storage->get($fullId));
			$this->objectCache[$fullId] = $object;
		}

		return $object;
	}

	/**
	 *
	 * @param mixed $object
	 * @return string
	 */
	protected function getIdForObject($object) {
		if (method_exists($object, 'getId')) {
			$result = 'serialized-' . str_replace('\\', '_', get_class($this)) . '-' . $object->getId();
		} else {
			$result = FALSE;
		}

		return $result;
	}
}