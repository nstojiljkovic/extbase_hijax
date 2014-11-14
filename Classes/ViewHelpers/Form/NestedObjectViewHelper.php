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
 * Class NestedObjectViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Form
 */
class NestedObjectViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $overwrittenVariables = array();

	/**
	 * @var array
	 */
	protected $newVariables = array();

	/**
	 * Render the sub-form.
	 *
	 * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
	 * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @param boolean $renderHiddenIdentityFieldIfNeeded
	 * @return string
	 */
	public function render($objectName, $object = NULL, $renderHiddenIdentityFieldIfNeeded = TRUE) {
		$formObjectName = $this->getNameWithoutPrefix($objectName);
		$this->setViewHelperVariableContainer('formObjectName', $formObjectName);
		$this->setViewHelperVariableContainer('formObject', $object ? $object : new \stdClass());
		$this->setViewHelperVariableContainer('additionalIdentityProperties', array());

		if ($object && $renderHiddenIdentityFieldIfNeeded) {
			$content = $this->renderHiddenIdentityField($object, $formObjectName);
		} else {
			$content = '';
		}

		$content .= $this->renderChildren();

		$this->revertViewHelperVariableContainer('additionalIdentityProperties');
		$this->revertViewHelperVariableContainer('formObject');
		$this->revertViewHelperVariableContainer('formObjectName');

		return $content;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param string $viewHelperName
	 */
	protected function setViewHelperVariableContainer($key, $value, $viewHelperName = 'TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper') {
		if ($this->viewHelperVariableContainer->exists($viewHelperName, $key)) {
			$this->overwrittenVariables[$key] = $this->viewHelperVariableContainer->get($viewHelperName, $key);
			$this->viewHelperVariableContainer->addOrUpdate($viewHelperName, $key, $value);
		} else {
			$this->newVariables[$key] = $value;
			$this->viewHelperVariableContainer->add($viewHelperName, $key, $value);
		}
	}

	/**
	 * @param $key
	 * @param string $viewHelperName
	 */
	protected function revertViewHelperVariableContainer($key, $viewHelperName = 'TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper') {
		if (array_key_exists($key, $this->overwrittenVariables)) {
			$this->viewHelperVariableContainer->addOrUpdate($viewHelperName, $key, $this->overwrittenVariables[$key]);
			unset($this->overwrittenVariables[$key]);
		} elseif (array_key_exists($key, $this->newVariables)) {
			$this->viewHelperVariableContainer->remove($viewHelperName, $key);
		}
	}

	/**
	 * Returns the name of the object that is bound to this form.
	 * If the "objectName" argument has been specified, this is returned. Otherwise the name attribute of this form.
	 * If neither objectName nor name arguments have been set, NULL is returned.
	 *
	 * @return string specified Form name or NULL if neither $objectName nor $name arguments have been specified
	 */
	protected function getFormObjectName() {
		$formObjectName = NULL;
		if ($this->hasArgument('objectName')) {
			$formObjectName = $this->arguments['objectName'];
		} elseif ($this->hasArgument('name')) {
			$formObjectName = $this->arguments['name'];
		}
		return $formObjectName;
	}

	/**
	 * Get the name of this form element, without prefix.
	 *
	 * @return string name
	 */
	protected function getNameWithoutPrefix() {
		$formObjectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
		if (!empty($formObjectName)) {
			$propertySegments = explode('.', $this->arguments['objectName']);
			$propertyPath = '';
			foreach ($propertySegments as $segment) {
				$propertyPath .= '[' . $segment . ']';
			}
			$name = $formObjectName . $propertyPath;
		} else {
			$name = $this->arguments['objectName'];
		}

		return $name;
	}

	/**
	 * Renders a hidden form field containing the technical identity of the given object.
	 *
	 * @param object $object Object to create the identity field for
	 * @param string $name Name
	 * @return string A hidden field containing the Identity (UID in TYPO3 Flow, uid in Extbase) of the given object or NULL if the object is unknown to the persistence framework
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\Argument::setValue()
	 */
	protected function renderHiddenIdentityField($object, $name) {
		if (!is_object($object)
			|| !($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject)
			|| ($object->_isNew() && !$object->_isClone())
		) {
			return '';
		}
		// Intentionally NOT using PersistenceManager::getIdentifierByObject here!!
		// Using that one breaks re-submission of data in forms in case of an error.
		$identifier = $object->getUid();
		if ($identifier === NULL) {
			return chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);
		}
		$name = $this->prefixFieldName($name) . '[__identity]';
		$this->registerFieldNameForFormTokenGeneration($name);

		return chr(10) . '<div><input type="hidden" name="' . $name . '" value="' . $identifier . '" /></div>' . chr(10);
	}

	/**
	 * Prefixes / namespaces the given name with the form field prefix
	 *
	 * @param string $fieldName field name to be prefixed
	 * @return string namespaced field name
	 */
	protected function prefixFieldName($fieldName) {
		if ($fieldName === NULL || $fieldName === '') {
			return '';
		}
		if (!$this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')) {
			return $fieldName;
		}
		$fieldNamePrefix = (string)$this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix');
		if ($fieldNamePrefix === '') {
			return $fieldName;
		}
		$fieldNameSegments = explode('[', $fieldName, 2);
		$fieldName = $fieldNamePrefix . '[' . $fieldNameSegments[0] . ']';
		if (count($fieldNameSegments) > 1) {
			$fieldName .= '[' . $fieldNameSegments[1];
		}
		return $fieldName;
	}


	/**
	 * Register a field name for inclusion in the HMAC / Form Token generation
	 *
	 * @param string $fieldName name of the field to register
	 * @return void
	 */
	protected function registerFieldNameForFormTokenGeneration($fieldName) {
		if ($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formFieldNames')) {
			$formFieldNames = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formFieldNames');
		} else {
			$formFieldNames = array();
		}
		$formFieldNames[] = $fieldName;
		$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formFieldNames', $formFieldNames);
	}
}
