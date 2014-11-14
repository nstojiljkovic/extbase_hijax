<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Animation;

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
 * Class SceneViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Animation
 */
class SceneViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'div';

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @inject
	 */
	protected $extensionService;

	/**
	 * Render the animation element.
	 *
	 * @param string $id
	 * @param string $duration
	 * @param string $nextSceneId
	 * @param string $resultTarget target where the results will be loaded
	 * @param string $loaders target where the loader will be shown
	 * @return string rendered element
	 */
	public function render($id = '', $duration = '', $nextSceneId = '', $resultTarget = NULL, $loaders = NULL) {

		$this->tag->addAttribute('data-hijax-scene-id', $id);
		$this->tag->addAttribute('data-hijax-scene-duration', $duration);
		$this->tag->addAttribute('data-hijax-scene-next-scene-id', $nextSceneId);
		$this->tag->addAttribute('data-hijax-element-type', 'scene');
		$this->tag->addAttribute('class', trim($this->arguments['class'] . ' hijax-element'));

		if ($resultTarget) {
			$this->tag->addAttribute('data-hijax-result-target', $resultTarget);
		} else {
			/* @var $listener \EssentialDots\ExtbaseHijax\Event\Listener */
			$listener = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('EssentialDots\\ExtbaseHijax\\MVC\\Dispatcher')->getCurrentListener();
			$this->tag->addAttribute('data-hijax-result-target', "jQuery(this).parents('.hijax-element[data-hijax-listener-id=\"" . $listener->getId() . "\"]')");
			$this->tag->addAttribute('data-hijax-result-wrap', 'false');
		}
		if ($loaders) {
			$this->tag->addAttribute('data-hijax-loaders', $loaders);
		}

		$this->tag->setContent($this->renderChildren());

		$this->tag->setContent('<div class="hijax-content">' . $this->tag->getContent() . '</div><div class="hijax-loading"></div>');

		return $this->tag->render();
	}
}
