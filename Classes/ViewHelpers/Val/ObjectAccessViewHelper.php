<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Val;

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
 * Class ObjectAccessViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Val
 */
class ObjectAccessViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('as', 'string', 'Variable name to insert result into, suppresses output');
		$this->registerArgument('object', 'string', 'Object');
		$this->registerArgument('attribute', 'string', 'Attribute');
		$this->registerArgument('getter', 'string', 'Getter');
	}

	/**
	 * @return string
	 */
	public function render() {
		$value = NULL;
		if ($this->arguments['object']) {
			if ($this->arguments['attribute']) {
				$getter = 'get' . ucfirst($this->arguments['attribute']);
			} else {
				$getter = $this->arguments['getter'];
			}
			if ($getter && is_object($this->arguments['object']) && method_exists($this->arguments['object'], $getter)) {
				$value = call_user_func(array($this->arguments['object'], $getter));
			} else {
				$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->arguments['object'], $this->arguments['attribute']);
			}
		}

		if ($this->arguments['as']) {
			$variableNameArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $this->arguments['as'], TRUE, 2);

			$variableName = $variableNameArr[0];
			$attributePath = $variableNameArr[1];

			if ($this->templateVariableContainer->exists($variableName)) {
				$oldValue = $this->templateVariableContainer->get($variableName);
				$this->templateVariableContainer->remove($variableName);
			}
			if ($attributePath) {
				if ($oldValue && is_array($oldValue)) {
					$templateValue = $oldValue;
					$templateValue[$attributePath] = $value;
				} else {
					$templateValue = array(
						$attributePath => $value
					);
				}
			} else {
				$templateValue = $value;
			}
			$this->templateVariableContainer->add($variableName, $templateValue);
			$result = '';
		} else {
			$result = $value;
		}

		return $result;
	}
}

