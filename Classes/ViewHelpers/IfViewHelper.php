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
use \TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * Class IfViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class IfViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	/**
	 * An array of \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
	 * @var array
	 */
	protected $childNodes = array();

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * renders <f:then> child if $condition is TRUE, otherwise renders <f:else> child.
	 *
	 * @param string $condition View helper condition
	 * @param string $animate
	 * @return string the rendered string
	 */
	public function render($condition, $animate = 'true') {
		$thenChild = $this->renderThenChild();
		if ($thenChild) {
			$thenChild = '<div class="hijax-content">' . $thenChild . '</div>';
		}
		$elseChild = $this->renderElseChild();
		if ($elseChild) {
			$elseChild = '<div class="hijax-content-else">' . $elseChild . '</div>';
		}

		return
			'<div class="hijax-element hijax-js-conditional" data-hijax-animate="' . $animate .
			'" data-hijax-element-type="conditional" data-hijax-condition="' . $this->arguments['condition'] . '">' .
			$thenChild . $elseChild . '<div class="hijax-loading"></div></div>';
	}

	/**
	 * Returns value of "then" attribute.
	 * If then attribute is not set, iterates through child nodes and renders ThenViewHelper.
	 * If then attribute is not set and no ThenViewHelper and no ElseViewHelper is found, all child nodes are rendered
	 *
	 * @return string rendered ThenViewHelper or contents of <f:if> if no ThenViewHelper was found
	 */
	protected function renderThenChild() {
		if ($this->hasArgument('then')) {
			return $this->arguments['then'];
		}
		if ($this->hasArgument('__thenClosure')) {
			$thenClosure = $this->arguments['__thenClosure'];
			return $thenClosure();
		} elseif ($this->hasArgument('__elseClosure') || $this->hasArgument('else')) {
			return '';
		}

		$elseViewHelperEncountered = FALSE;
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'EssentialDots\\ExtbaseHijax\\ViewHelpers\\ThenViewHelper'
			) {
				$data = $childNode->evaluate($this->renderingContext);
				return $data;
			}
			if ($childNode instanceof \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'EssentialDots\\ExtbaseHijax\\ViewHelpers\\ElseViewHelper'
			) {
				$elseViewHelperEncountered = TRUE;
			}
		}

		if (!$elseViewHelperEncountered) {
			return $this->renderChildren();
		}

		return '';
	}

	/**
	 * Returns value of "else" attribute.
	 * If else attribute is not set, iterates through child nodes and renders ElseViewHelper.
	 * If else attribute is not set and no ElseViewHelper is found, an empty string will be returned.
	 *
	 * @return string rendered ElseViewHelper or an empty string if no ThenViewHelper was found
	 */
	protected function renderElseChild() {
		if ($this->hasArgument('else')) {
			return $this->arguments['else'];
		}
		if ($this->hasArgument('__elseClosure')) {
			$elseClosure = $this->arguments['__elseClosure'];
			return $elseClosure();
		}
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'EssentialDots\\ExtbaseHijax\\ViewHelpers\\ElseViewHelper'
			) {
				return $childNode->evaluate($this->renderingContext);
			}
		}

		return '';
	}

	/**
	 * The compiled ViewHelper adds two new ViewHelper arguments: __thenClosure and __elseClosure.
	 * These contain closures which are be executed to render the then(), respectively else() case.
	 *
	 * @param string $argumentsName
	 * @param string $closureName
	 * @param string $initializationPhpCode
	 * @param ViewHelperNode $node
	 * @param TemplateCompiler $compiler
	 * @return string
	 */
	public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler) {
		foreach ($node->getChildNodes() as $childNode) {
			if ($childNode instanceof \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'EssentialDots\\ExtbaseHijax\\ViewHelpers\\ThenViewHelper'
			) {

				$childNodesAsClosure = $compiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__thenClosure\'] = %s;', $argumentsName, $childNodesAsClosure) . chr(10);
			}
			if ($childNode instanceof \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				&& $childNode->getViewHelperClassName() === 'EssentialDots\\ExtbaseHijax\\ViewHelpers\\ElseViewHelper'
			) {

				$childNodesAsClosure = $compiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__elseClosure\'] = %s;', $argumentsName, $childNodesAsClosure) . chr(10);
			}
		}
		return \TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler::SHOULD_GENERATE_VIEWHELPER_INVOCATION;
	}
}
