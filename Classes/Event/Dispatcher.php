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
 * Class Dispatcher
 *
 * @package EssentialDots\ExtbaseHijax\Event
 */
class Dispatcher implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $listeners = array();

	/**
	 * @var array
	 */
	protected $currentElementListenersStack = array();

	/**
	 * @var array
	 */
	protected $currentElementListeners = array();

	/**
	 * @var array
	 */
	protected $pendingEvents = array();

	/**
	 * @var array
	 */
	protected $nextPhasePendingEvents = array();

	/**
	 * @var array
	 */
	protected $pendingEventNames = array();

	/**
	 * @var array
	 */
	protected $nextPhasePendingEventNames = array();

	/**
	 * @var array
	 */
	protected $skipPendingEvents = array();

	/**
	 * @var array
	 */
	protected $nextPhaseSkipPendingEvents = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Service\Serialization\ListenerFactory
	 */
	protected $listenerFactory;

	/**
	 * @var boolean
	 */
	protected $isHijaxElement;

	/**
	 * @var boolean
	 */
	protected $xmlCommentsFound = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->listenerFactory = $this->objectManager->get('EssentialDots\\ExtbaseHijax\\Service\\Serialization\\ListenerFactory');
	}

	/**
	 * Connects a listener to a given event name.
	 *
	 * @param string $name An event name
	 * @param mixed $callback Callback function
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $listener TYPO3 Extbase listener
	 * @return array
	 */
	public function connect($name, $callback = NULL, $listener = NULL) {
		$this->setIsHijaxElement(TRUE);
		if (!$listener) {
			/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
			$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
		}

		if (!isset($this->listeners[$name])) {
			$this->listeners[$name] = array();
		}

		$events = array();

		if (in_array($name, $this->pendingEventNames)) {
			foreach ($this->pendingEvents[$name] as $event) {
				/* @var $event \EssentialDots\ExtbaseHijax\Event\Event */
				$events[] = $event;
				if ($callback) {
					if (is_string($callback)) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($callback, $event, $this, $checkPrefix = FALSE);
					} else {
						call_user_func($callback, $event);
					}
				}
			}
		}

		$this->listeners[$name][] = array('listener' => $listener, 'callback' => $callback);

		if (!isset($this->currentElementListeners[$name])) {
			$this->currentElementListeners[$name] = array();
		}

		$this->currentElementListeners[$name][] = array('listener' => $listener, 'callback' => $callback);

		return $events;
	}

	/**
	 * @param boolean $isHijaxElement
	 * @return void
	 */
	public function setIsHijaxElement($isHijaxElement) {
		$this->isHijaxElement = $isHijaxElement;
	}

	/**
	 * @return boolean
	 */
	public function getIsHijaxElement() {
		return $this->isHijaxElement;
	}

	/**
	 * Disconnects a listener for a given event name.
	 *
	 * @param string $name An event name
	 * @param mixed $callback A PHP callable
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $listener TYPO3 Extbase listener
	 *
	 * @return bool FALSE if listener does not exist, TRUE otherwise
	 */
	public function disconnect($name, $callback = NULL, $listener = NULL) {
		if (!isset($this->listeners[$name])) {
			return FALSE;
		}

		if (!$listener) {
			$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
		}

		foreach ($this->listeners[$name] as $i => $callable) {
			if ($listener->getId() === $callable['listener']->getId() && $callback === $callable['callback']) {
				unset($this->listeners[$name][$i]);
				if (count($this->listeners[$name]) == 0) {
					unset($this->listeners[$name]);
				}
			}
		}

		foreach ($this->currentElementListeners[$name] as $i => $callable) {
			if ($listener->getId() === $callable['listener']->getId() && $callback === $callable['callback']) {
				unset($this->currentElementListeners[$name][$i]);
				if (count($this->currentElementListeners[$name]) == 0) {
					unset($this->currentElementListeners[$name]);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Notifies all listeners of a given event.
	 *
	 * @param \EssentialDots\ExtbaseHijax\Event\Event $event A \EssentialDots\ExtbaseHijax\Event\Event instance
	 * @param boolean $skipNotifier Skips notifier when processing the event (prevents dead loops)
	 * @param \EssentialDots\ExtbaseHijax\Event\Listener $listener TYPO3 Extbase listener
	 *
	 * @return \EssentialDots\ExtbaseHijax\Event\Event The \EssentialDots\ExtbaseHijax\Event\Event instance
	 */
	public function notify(\EssentialDots\ExtbaseHijax\Event\Event $event, $skipNotifier = FALSE, $listener = NULL) {
		if (!isset($this->nextPhasePendingEvents[$event->getName()])) {
			$this->nextPhasePendingEvents[$event->getName()] = array();
		}
		$this->nextPhasePendingEvents[$event->getName()][] = $event;

		if ($skipNotifier) {
			if (!$listener) {
				/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
				$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
			}

			if (!in_array($event->getName(), $this->nextPhaseSkipPendingEvents)) {
				$this->nextPhaseSkipPendingEvents[] = $event->getName() . ';' . $listener->getId();
			}
		}

		if (!in_array($event->getName(), $this->nextPhasePendingEventNames)) {
			$this->nextPhasePendingEventNames[] = $event->getName();
		}

		return $event;
	}

	/**
	 * @return void
	 */
	public function promoteNextPhaseEvents() {
		$this->pendingEvents = $this->nextPhasePendingEvents;
		$this->pendingEventNames = $this->nextPhasePendingEventNames;
		$this->skipPendingEvents = $this->nextPhaseSkipPendingEvents;

		$this->nextPhasePendingEvents = array();
		$this->nextPhasePendingEventNames = array();
		$this->nextPhaseSkipPendingEvents = array();
		$this->isHijaxElement = FALSE;
	}

	/**
	 * @return boolean
	 */
	public function hasPendingNextPhaseEvents() {
		return (boolean)count($this->nextPhasePendingEventNames);
	}

	/**
	 * @return boolean
	 */
	public function hasPendingEvents() {
		return (boolean)count($this->pendingEventNames);
	}

	/**
	 * @param $eventName
	 * @param $listenerId
	 * @return bool
	 */
	public function hasPendingEventWithName($eventName, $listenerId) {
		return in_array($eventName, $this->pendingEventNames) && !in_array($eventName . ';' . $listenerId, $this->skipPendingEvents);
	}

	/**
	 * Returns TRUE if the given event name has some listeners.
	 *
	 * @param    string $name The event name
	 * @param    boolean $current Determines if the lookup should be done only on current element listeners
	 *
	 * @return Boolean TRUE if some listeners are connected, FALSE otherwise
	 */
	public function hasListeners($name = '', $current = FALSE) {
		if ($current) {
			$listeners = &$this->currentElementListeners;
		} else {
			$listeners = &$this->listeners;
		}

		if ($name) {
			if (!isset($listeners[$name])) {
				$listeners[$name] = array();
			}

			$result = (boolean)count($listeners[$name]);
		} else {
			$result = (boolean)count($listeners);
		}

		return $result;
	}

	/**
	 * Returns all listeners associated with a given event name.
	 *
	 * @param string $name
	 * @param bool $current
	 * @return array
	 */
	public function getListeners($name = '', $current = FALSE) {
		if ($current) {
			$listeners = &$this->currentElementListeners;
		} else {
			$listeners = &$this->listeners;
		}

		if ($name) {
			if (isset($listeners[$name])) {
				return $listeners[$name];
			}
		} else {
			return $listeners;
		}

		return array();
	}

	/**
	 * Denotes start of content element rendering execution
	 *
	 * @return void
	 */
	public function startContentElement() {
		array_push($this->currentElementListenersStack, $this->currentElementListeners);
		$this->currentElementListeners = array();
		$this->resetContextArguments();
		$this->setIsHijaxElement(FALSE);
	}

	/**
	 * Denotes end of content element rendering execution
	 *
	 * @return void
	 */
	public function endContentElement() {
		$this->currentElementListeners = array_pop($this->currentElementListenersStack);
	}

	/**
	 * @param $content
	 * @return void
	 */
	public function parseAndRunEventListeners(&$content) {
		// @todo: migrate this to plain string functions, do not use regular expressions
		// Count how many times we increase the limit
		$iSet = 0;
		while ($iSet < 10) {
		// If the default limit is 100'000 characters the highest new limit will be 250'000 characters
			$tempContent = preg_replace_callback(
				'/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->(?P<content>.*?)<!-- ###EVENT_LISTENER_(\\1)### END -->/msU',
				array($this, 'parseAndRunEventListenersCallback'),
				$content);

			// Only check on backtrack limit failure
			if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
				// Get current limit and increase
				ini_set('pcre.backtrack_limit', (int)ini_get('pcre.backtrack_limit') + 15000);
				// Do not overkill the server
				$iSet++;
			} else {
				// No fail
				if ($tempContent !== NULL) {
					$content = $tempContent;
				} else {
					error_log('PCRE backtrack limit reached! Event listener elements have not been processed!');
				}
				break;
			}
		}
	}

	/**
	 * @param array $match
	 * @return string
	 */
	protected function parseAndRunEventListenersCallback($match) {
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-zA-Z0-9_-]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);

		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];
		$eventNames = $this->convertCsvToArray($matchesListenerDef['eventNames']);

		$shouldProcess = FALSE;

		foreach ($eventNames as $eventName) {
			if ($this->hasPendingEventWithName($eventName, $listenerId)) {
				$shouldProcess = TRUE;
				break;
			}
		}

		if ($shouldProcess) {
			/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
			$listener = $this->listenerFactory->findById($listenerId);

			if ($listener) {
				/* @var $bootstrap \TYPO3\CMS\Extbase\Core\Bootstrap */
				$bootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
				$bootstrap->cObj = $listener->getContentObject();
				$bootstrap->initialize($listener->getConfiguration());
				$request = $listener->getRequest();
				$request->setDispatched(FALSE);

				/* @var $response \TYPO3\CMS\Extbase\Mvc\Web\Response */
				$response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');

				$dispatcher = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher');
				$dispatcher->dispatch($request, $response);

				$result = $response->getContent();
			} else {
				// TODO: log error message
				$result = $match[0];
			}
		} else {
			$result = $match[0];
		}

		return $result;
	}

	/**
	 * @param $content
	 * @param string $format
	 */
	public function replaceXmlCommentsWithDivs(&$content, $format = 'html') {
		$this->xmlCommentsFound = TRUE;
		while ($this->xmlCommentsFound) {
			$this->xmlCommentsFound = FALSE;
			if ($format == 'html') {
				$content = preg_replace_callback('/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->/msU', array($this, 'replaceXmlCommentsWithDivsCallback'), $content);
				$content = preg_replace('/<!-- ###EVENT_LISTENER_(\d*)### END -->/msU', '</div><div class="hijax-loading"></div></div>', $content);
			} else {
				$content = preg_replace('/<!-- ###EVENT_LISTENER_(\d*)### START (.*) -->/msU', '', $content);
				$content = preg_replace('/<!-- ###EVENT_LISTENER_(\d*)### END -->/msU', '', $content);
			}
		}
	}

	/**
	 * @var array
	 */
	protected $contextArguments = array();

	/**
	 * @param array $contextArguments
	 *
	 * @return void
	 */
	public function registerContextArguments($contextArguments) {
		$this->contextArguments = array_merge($this->contextArguments, $contextArguments);
	}

	/**
	 * @return array
	 */
	public function getContextArguments() {
		return $this->contextArguments;
	}

	/**
	 * @return void
	 */
	protected function resetContextArguments() {
		$this->contextArguments = array();
	}

	/**
	 * @param array $match
	 * @return string
	 */
	protected function replaceXmlCommentsWithDivsCallback($match) {
		$this->xmlCommentsFound = TRUE;
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-zA-Z0-9_-]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);

		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];

		return
			'<div class="hijax-element hijax-js-listener" data-hijax-result-target="this" data-hijax-result-wrap="false" data-hijax-element-type="listener" data-hijax-element-id="' .
			$elementId . '" data-hijax-listener-id="' . $listenerId . '" data-hijax-listener-events="' . htmlspecialchars($matchesListenerDef['eventNames']) . '"><div class="hijax-content">';
	}

	/**
	 * @param string $data
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return array
	 */
	protected function convertCsvToArray($data, $delimiter = ',', $enclosure = '"') {
		$instream = fopen('php://temp', 'r+');
		fwrite($instream, $data);
		rewind($instream);
		$csv = fgetcsv($instream, 9999999, $delimiter, $enclosure);
		fclose($instream);
		return $csv;
	}
}