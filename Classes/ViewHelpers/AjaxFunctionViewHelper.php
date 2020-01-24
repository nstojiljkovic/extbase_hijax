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
 * Class AjaxFunctionViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class AjaxFunctionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\JSBuilder
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $jsBuilder;

	/**
	 * @param NULL $action
	 * @param array $arguments
	 * @param NULL $controller
	 * @param NULL $extension
	 * @param NULL $plugin
	 * @param string $format
	 * @param string $section
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Exception
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extension = NULL, $plugin = NULL, $format = '', $section = 'footer') {
		return $this->jsBuilder->getAjaxFunction($action, $arguments, $controller, $extension, $plugin, $format, $section);
	}
}
