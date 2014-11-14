<?php
namespace EssentialDots\ExtbaseHijax\Cache;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class PageCacheFacade
 *
 * @package EssentialDots\ExtbaseHijax\Cache
 */
class PageCacheFacade implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * the page cache object, use this to save pages to the cache and to
	 * retrieve them again
	 *
	 * @var \TYPO3\CMS\Core\Cache\Backend\AbstractBackend
	 */
	protected $pageCache;

	/**
	 * @var \tx_ncstaticfilecache
	 */
	protected $ncStaticFileCache;

	/**
	 * @var array
	 */
	protected $queuedPageHashs;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->queuedPageHashs = array();
		$this->pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		if (ExtensionManagementUtility::isLoaded('nc_staticfilecache')) {
			$this->ncStaticFileCache = GeneralUtility::makeInstance('tx_ncstaticfilecache');
		}
	}

	/**
	 * @param array $pageHashs
	 * @param bool $queue
	 * @return void
	 */
	public function flushCacheByHashIdentifiers($pageHashs, $queue = TRUE) {
		if ($queue) {
			if (is_array($pageHashs) && count($pageHashs)) {
				foreach ($pageHashs as $pageHash) {
					if (!in_array($pageHash, $this->queuedPageHashs)) {
						$this->queuedPageHashs[] = $pageHash;
					}
				}
			}
		} else {
			$this->flushCacheByHashIdentifiersImplementation($pageHashs);
		}
	}

	/**
	 * @param array $pageHashs
	 * @return void
	 */
	protected function flushCacheByHashIdentifiersImplementation(&$pageHashs) {
		if (is_array($pageHashs) && count($pageHashs)) {
			foreach ($pageHashs as $pageHash) {
				if (substr($pageHash, 0, 3) == 'id-') {
					$pageId = substr($pageHash, 3);
					$this->pageCache->flushByTag('pageId_' . $pageId);
					if ($this->ncStaticFileCache) {
						$params = array(
							'cacheCmd' => $pageId
						);
						$this->ncStaticFileCache->clearStaticFile($params);
					}
				} elseif (substr($pageHash, 0, 5) == 'hash-') {
					$identifier = substr($pageHash, 5);
					$this->pageCache->remove($identifier);
					if ($this->ncStaticFileCache) {
						$this->ncStaticFileCache->deleteStaticCacheByIdentifier($identifier);
					}
				}
			}
		}
		$pageHashs = array();
	}

	/**
	 * destructor
	 */
	public function __destruct() {
		$this->flushCacheByHashIdentifiersImplementation($this->queuedPageHashs);
	}
}