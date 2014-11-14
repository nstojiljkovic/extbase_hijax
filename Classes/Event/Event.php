<?php
namespace EssentialDots\ExtbaseHijax\Event;

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
 * Class Event
 *
 * @package EssentialDots\ExtbaseHijax\Event
 */
class Event implements \ArrayAccess {

	/**
	 * @var mixed
	 */
	protected $value = NULL;

	/**
	 * @var bool
	 */
	protected $processed = FALSE;

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var array|null
	 */
	protected $parameters = NULL;

	/**
	 * Constructs a new \EssentialDots\ExtbaseHijax\Event\Event
	 *
	 * @param $name
	 * @param array $parameters
	 */
	public function __construct($name, $parameters = array()) {
		$this->name = $name;
		$this->parameters = $parameters;
	}

	/**
	 * Returns the event name.
	 *
	 * @return string The event name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the return value for this event.
	 *
	 * @param mixed $value The return value
	 */
	public function setReturnValue($value) {
		$this->value = $value;
	}

	/**
	 * Returns the return value.
	 *
	 * @return mixed The return value
	 */
	public function getReturnValue() {
		return $this->value;
	}

	/**
	 * Sets the processed flag.
	 *
	 * @param Boolean $processed The processed flag value
	 */
	public function setProcessed($processed) {
		$this->processed = (boolean)$processed;
	}

	/**
	 * Returns whether the event has been processed by a listener or not.
	 *
	 * @return Boolean TRUE if the event has been processed, FALSE otherwise
	 */
	public function isProcessed() {
		return $this->processed;
	}

	/**
	 * Returns the event parameters.
	 *
	 * @return array The event parameters
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Returns TRUE if the parameter exists (implements the ArrayAccess interface).
	 *
	 * @param    string $name The parameter name
	 *
	 * @return Boolean TRUE if the parameter exists, FALSE otherwise
	 */
	public function offsetExists($name) {
		return array_key_exists($name, $this->parameters);
	}

	/**
	 * Returns a parameter value (implements the ArrayAccess interface).
	 *
	 * @param    string $name The parameter name
	 *
	 * @return mixed    The parameter value
	 * @throws \InvalidArgumentException
	 */
	public function offsetGet($name) {
		if (!array_key_exists($name, $this->parameters)) {
			throw new \InvalidArgumentException(sprintf('The event "%s" has no "%s" parameter.', $this->name, $name));
		}

		return $this->parameters[$name];
	}

	/**
	 * Sets a parameter (implements the ArrayAccess interface).
	 *
	 * @param string $name The parameter name
	 * @param mixed $value The parameter value
	 */
	public function offsetSet($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * Removes a parameter (implements the ArrayAccess interface).
	 *
	 * @param string $name The parameter name
	 */
	public function offsetUnset($name) {
		unset($this->parameters[$name]);
	}
}