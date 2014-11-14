<?php
namespace EssentialDots\ExtbaseHijax\Core\Parser;

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
 * Class TemplateParser
 *
 * @package EssentialDots\ExtbaseHijax\Core\Parser
 */
class TemplateParser extends \TYPO3\CMS\Fluid\Core\Parser\TemplateParser {

	/**
	 * Extracts namespace definitions out of the given template string and sets
	 * $this->namespaces.
	 *
	 * @param string $templateString Template string to extract the namespaces from
	 * @return string The updated template string without namespace declarations inside
	 * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception if a namespace can't be resolved or has been declared already
	 */
	protected function extractNamespaceDefinitions($templateString) {
		$templateString = parent::extractNamespaceDefinitions($templateString);

		foreach ($this->namespaces as $identifier => $phpNamespace) {
			if ($phpNamespace === 'Tx_ExtbaseHijax_ViewHelpers') {
				$this->namespaces[$identifier] = 'EssentialDots\\ExtbaseHijax\\ViewHelpers';
			}
		}

		return $templateString;
	}
}
