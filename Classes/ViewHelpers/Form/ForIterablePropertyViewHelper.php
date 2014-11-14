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
 * Class ForIterablePropertyViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Form
 */
class ForIterablePropertyViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper {

	/**
	 * Iterates through elements of $each and renders child nodes
	 *
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @param boolean $reverse If enabled, the iterator will start with the last element and proceed reversely
	 * @param string $iteration The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)
	 * @param int $fromIndex
	 * @param int $toIndex
	 * @return string Rendered string
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
	 */
	public function render($as, $key = '', $reverse = FALSE, $iteration = NULL, $fromIndex = -1, $toIndex = -1) {
		$templateVariableContainer = $this->renderingContext->getTemplateVariableContainer();
		$propertyValue = $this->getValue(FALSE);

		if ($propertyValue === NULL) {
			return '';
		}
		if (is_object($propertyValue) && !$propertyValue instanceof \Traversable) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('ForViewHelper only supports arrays and objects implementing Traversable interface', 1248728393);
		}

		if ($this->arguments['reverse'] === TRUE) {
			// array_reverse only supports arrays
			if (is_object($propertyValue)) {
				$propertyValue = iterator_to_array($propertyValue);
			}
			$propertyValue = array_reverse($propertyValue);
		}
		$iterationData = array(
			'index' => 0,
			'cycle' => 1,
			'total' => count($propertyValue)
		);

		$output = '';

		foreach ($propertyValue as $keyValue => $singleElement) {
			$templateVariableContainer->add($this->arguments['as'], $singleElement);
			if ($this->arguments['key'] !== '') {
				$templateVariableContainer->add($this->arguments['key'], $keyValue);
			}
			if ($this->arguments['iteration'] !== NULL) {
				$iterationData['isFirst'] = $iterationData['cycle'] === 1;
				$iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
				$iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
				$iterationData['isOdd'] = !$iterationData['isEven'];
				$templateVariableContainer->add($this->arguments['iteration'], $iterationData);
				$iterationData['cycle']++;
			}
			$iterationData['index']++;
			if (
				($this->arguments['fromIndex'] == -1 || ($this->arguments['fromIndex'] <= $iterationData['index'])) &&
				($this->arguments['toIndex'] == -1 || ($this->arguments['toIndex'] >= $iterationData['index']))
			) {
				//$renderChildrenClosure();?
				$output .= $this->renderChildren();
			}
			$templateVariableContainer->remove($this->arguments['as']);
			if ($this->arguments['key'] !== '') {
				$templateVariableContainer->remove($this->arguments['key']);
			}
			if ($this->arguments['iteration'] !== NULL) {
				$templateVariableContainer->remove($this->arguments['iteration']);
			}
		}
		return $output;
	}
}

