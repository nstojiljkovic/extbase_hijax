<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Widget\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Essential Dots d.o.o. Belgrade
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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class OrderingsController
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Widget\Controller
 */
class OrderingsController extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController {

	/**
	 * @var array
	 */
	protected $configuration = array('defaultSortBy' => NULL, 'defaultOrder' => NULL, 'insertAbove' => FALSE, 'insertBelow' => FALSE, 'orderingsTemplate' => FALSE);

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	protected $objects;

	/**
	 * @return void
	 */
	public function initializeAction() {
		$this->objects = $this->widgetConfiguration['objects'];
		ArrayUtility::mergeRecursiveWithOverrule($this->configuration, $this->widgetConfiguration['configuration'], TRUE);
	}

	/**
	 * @param string $sortBy
	 * @param string $order
	 * @return void
	 */
	public function indexAction($sortBy = NULL, $order = NULL) {
		$sortBy = is_null($sortBy) ? $this->configuration['defaultSortBy'] : $sortBy;
		$order = is_null($order) ? $this->configuration['defaultOrder'] : $order;
		$order = (is_string($order) && strtolower($order) === 'desc') ?
			\TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING : \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING;
		$objects = $this->objects;

		if (
			$sortBy &&
			(
				$objects instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult ||
				$objects instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
			)
		) {
			$query = $objects->getQuery();
			$orderings = array();
			foreach (GeneralUtility::trimExplode(',', $sortBy) as $sortByField) {
				$orderings[$sortByField] = $order;
			}
			$query->setOrderings($orderings);
			$objects = $this->objectManager->get(get_class($objects), $query);
		}

		$this->view->assign('contentArguments', array(
			$this->widgetConfiguration['as'] => $objects
	));
		$this->view->assign('configuration', $this->configuration);
		$this->view->assign('variables', array(
			'order' => $order,
			'sortBy' => $sortBy
		));
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		parent::setViewConfiguration($view);

		// Template Path Override
		if ($this->configuration['orderingsTemplate']) {
			$templatePathAndFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->configuration['orderingsTemplate']);
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($templatePathAndFilename)) {
				$view->setTemplatePathAndFilename($templatePathAndFilename);
			}
		}
	}
}
