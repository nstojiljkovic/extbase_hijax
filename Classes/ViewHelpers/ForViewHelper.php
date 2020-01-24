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
 * Class ForViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class ForViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\ForViewHelper {

	/**
	 * Iterates through elements of $each and renders child nodes
	 *
	 * @param array $each The array or \TYPO3\CMS\Extbase\Persistence\ObjectStorage to iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @param boolean $reverse If enabled, the iterator will start with the last element and proceed reversely
	 * @param string $iteration The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)
	 * @param int $fromIndex
	 * @param int $toIndex
	 * @return string Rendered string
	 * @api
	 */
	public function render($each, $as, $key = '', $reverse = FALSE, $iteration = NULL, $fromIndex = -1, $toIndex = -1) {
		return self::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
	}

	/**
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$variableProvider = $renderingContext->getVariableProvider();
		if ($arguments['each'] === NULL) {
			return '';
		}
		if (is_object($arguments['each']) && !$arguments['each'] instanceof \Traversable) {
			throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('ForViewHelper only supports arrays and objects implementing Traversable interface', 1248728393);
		}

		if ($arguments['reverse'] === TRUE) {
			// array_reverse only supports arrays
			if (is_object($arguments['each'])) {
				$arguments['each'] = iterator_to_array($arguments['each']);
			}
			$arguments['each'] = array_reverse($arguments['each']);
		}
		$iterationData = array(
			'index' => 0,
			'cycle' => 1,
			'total' => count($arguments['each'])
		);

		$output = '';

		foreach ($arguments['each'] as $keyValue => $singleElement) {
			$variableProvider->add($arguments['as'], $singleElement);
			if ($arguments['key'] !== '') {
				$variableProvider->add($arguments['key'], $keyValue);
			}
			if ($arguments['iteration'] !== NULL) {
				$iterationData['isFirst'] = $iterationData['cycle'] === 1;
				$iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
				$iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
				$iterationData['isOdd'] = !$iterationData['isEven'];
				$variableProvider->add($arguments['iteration'], $iterationData);
				$iterationData['cycle']++;
			}
			$iterationData['index']++;
			if (
				($arguments['fromIndex'] == -1 || ($arguments['fromIndex'] <= $iterationData['index'])) &&
				($arguments['toIndex'] == -1 || ($arguments['toIndex'] >= $iterationData['index']))
			) {
				$output .= $renderChildrenClosure();
			}
			$variableProvider->remove($arguments['as']);
			if ($arguments['key'] !== '') {
				$variableProvider->remove($arguments['key']);
			}
			if ($arguments['iteration'] !== NULL) {
				$variableProvider->remove($arguments['iteration']);
			}
		}
		return $output;
	}
}

