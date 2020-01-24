<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers;

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
 * Class TrackCollectionViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class TrackCollectionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Tracking\Manager
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $trackingManager;

	/**
	 * @param mixed $collection Object to use
	 * @param boolean $clearCacheOnAllHashesForCurrentPage Clear cache on all hashes for current page
	 * @return string the rendered string
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	public function render($collection = NULL, $clearCacheOnAllHashesForCurrentPage = FALSE) {
		foreach ($collection as $object) {
			if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
				if ($clearCacheOnAllHashesForCurrentPage) {
					$this->trackingManager->trackObjectOnPage($object, 'id');
				} else {
					$this->trackingManager->trackObjectOnPage($object, 'hash');
				}
			}
		}

		return $this->renderChildren();
	}
}

