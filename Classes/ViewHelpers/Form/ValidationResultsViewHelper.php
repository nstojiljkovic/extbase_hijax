<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Form;

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
 * Class ValidationResultsViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Form
 */
class ValidationResultsViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \EssentialDots\ExtbaseHijax\Property\TypeConverterService\ObjectStorageMappingService
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $objectStorageMappingService;

	/**
	 * Iterates through selected errors of the request.
	 *
	 * @param string $for The name of the error name (e.g. argument name or property name).
	 * @param string $as The name of the variable to store the current error
	 * @return string Rendered string
	 * @api
	 */
	public function render($for = '', $as = 'validationResults') {
		$for = $this->getNameWithoutPrefix();
		$validationResults = $this->renderingContext->getControllerContext()->getRequest()->getOriginalRequestMappingResults();
		if ($validationResults !== NULL && $for !== '') {
			$validationResults = $validationResults->forProperty($for);
		}
		$this->templateVariableContainer->add($as, $validationResults);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove($as);
		return $output;
	}

	/**
	 * Get the name of this form element, without prefix.
	 *
	 * @return string name
	 */
	protected function getNameWithoutPrefix() {
		$formObjectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
		if (!empty($formObjectName)) {
			$formObjectPath = str_replace(']', '', str_replace('[', '.', $formObjectName));
			$propertySegments = explode('.', $this->arguments['for']);
			$propertyPath = '';
			foreach ($propertySegments as $segment) {
				$propertyPath .= '.' . $segment;
			}
			$name = $formObjectPath . $propertyPath;
		} else {
			$name = $this->arguments['for'];
		}

		$nameArr = explode('.', $name);
		foreach ($nameArr as $i => $segment) {
			$nameArr[$i] = $this->objectStorageMappingService->getSplObjectHashForSourceKey($segment);
		}

		return implode('.', $nameArr);
	}
}
