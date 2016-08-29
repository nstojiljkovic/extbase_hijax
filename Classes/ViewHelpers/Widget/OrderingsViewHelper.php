<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Widget;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Essential Dots d.o.o. Belgrade
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
 * Class OrderingsViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Widget
 */
class OrderingsViewHelper extends \EssentialDots\ExtbaseHijax\Core\Widget\AbstractWidgetViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\ViewHelpers\Widget\Controller\OrderingsController
	 * @inject
	 */
	protected $controller;

	/**
	 *
	 * @param mixed $objects
	 * @param string $as
	 * @param array $configuration
	 * @param array $variables
	 * @return string
	 */
	public function render($objects, $as, array $configuration = NULL, $variables = array()) {
		if (is_null($configuration)) {
			$configuration = array(
				'defaultSortBy' => NULL,
				'defaultOrder' => NULL,
				'insertAbove' => TRUE,
				'insertBelow' => FALSE,
				'orderingsTemplate' => FALSE,
				'widgetIdentifier' => ''
			);
		}
		if ($configuration['widgetIdentifier']) {
			$this->widgetContext->setWidgetIdentifier($configuration['widgetIdentifier']);
		}
		return $this->initiateSubRequest();
	}
}
