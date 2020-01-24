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
 * Class CObjectViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class CObjectViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string $typoScriptObjectPath TypoScript setup path of the TypoScript object to render
	 * @param string $loaders
	 * @return string
	 */
	public function render($typoScriptObjectPath, $loaders = '') {
		$value =
			'<div class="hijax-element" data-hijax-loaders="' . $loaders . '" data-hijax-ajax-tssource="' . $typoScriptObjectPath .
			'" data-hijax-result-wrap="false" data-hijax-result-target="jQuery(this)" data-hijax-element-type="ajax"><div class="hijax-content"><p>&nbsp;</p></div><div class="hijax-loading"></div></div>';
		return $value;
	}
}

