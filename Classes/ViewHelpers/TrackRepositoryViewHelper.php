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
 * Class TrackRepositoryViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class TrackRepositoryViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Tracking\Manager
	 * @inject
	 */
	protected $trackingManager;

	/**
	 * @param mixed $object Object to use
	 * @param boolean $clearCacheOnAllHashesForCurrentPage Clear cache on all hashes for current page
	 * @return string the rendered string
	 */
	public function render($object = NULL, $clearCacheOnAllHashesForCurrentPage = FALSE) {
		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
			if ($clearCacheOnAllHashesForCurrentPage) {
				$this->trackingManager->trackRepositoryOnPage($object, 'id');
			} else {
				$this->trackingManager->trackRepositoryOnPage($object, 'hash');
			}
		}

		return $this->renderChildren();
	}
}

