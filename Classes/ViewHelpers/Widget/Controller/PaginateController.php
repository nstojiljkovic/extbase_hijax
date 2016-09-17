<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers\Widget\Controller;

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
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class PaginateController
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers\Widget\Controller
 */
class PaginateController extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController {

	/**
	 * @var array
	 */
	protected $configuration = array('itemsPerPage' => 10, 'insertAbove' => FALSE, 'insertBelow' => TRUE, 'pagerTemplate' => FALSE);

	/**
	 * @var array
	 */
	protected $variables = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	protected $objects;

	/**
	 * @var integer
	 */
	protected $currentPage = 1;

	/**
	 * @var integer
	 */
	protected $numberOfPages = 1;

	/**
	 * @return void
	 */
	public function initializeAction() {
		$this->objects = $this->widgetConfiguration['objects'];
		ArrayUtility::mergeRecursiveWithOverrule($this->configuration, $this->widgetConfiguration['configuration'], TRUE);
		$this->variables = $this->widgetConfiguration['variables'];

		$objectsCount = 0;
		foreach ($this->objects as $objects) {
			$objectsCount += count($objects);
		}
		$this->numberOfPages = ceil($objectsCount / (integer)$this->configuration['itemsPerPage']);
	}

	/**
	 * @param integer $currentPage
	 * @return void
	 */
	public function indexAction($currentPage = 1) {
		// set current page
		$this->currentPage = (integer)$currentPage;
		if ($this->currentPage < 1) {
			$this->currentPage = 1;
		} elseif ($this->currentPage > $this->numberOfPages) {
			$this->currentPage = $this->numberOfPages;
		}

		// modify query
		$itemsPerPage = (integer)$this->configuration['itemsPerPage'];

		$paginatedItems = array();
		$previousObjectSetsCount = 0;
		foreach ($this->objects as $objects) {
			if (count($paginatedItems) == $itemsPerPage) {
				break;
			}
			$limit = $itemsPerPage - count($paginatedItems);
			$offset = 0;
			if ($this->currentPage > 1) {
				$offset = (integer)(($itemsPerPage * ($this->currentPage - 1)) + count($paginatedItems) - $previousObjectSetsCount);
			}

			if ($objects instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult || $objects instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
				$query = $objects->getQuery();
				// respect limit set by upper instances, for example by controllers
				$query->setLimit($objects->count() < $limit + $offset ? $objects->count() - $offset : $limit);
				$query->setOffset($offset);

				if (count($this->objects) === 1 && $objects instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult) {
					$paginatedItems = $this->objectManager->get(get_class($objects), $query);
					break;
				} else {
					// mixed bag of bunch of collections
					$modifiedObjects = $query->execute();
					foreach ($modifiedObjects as $obj) {
						$paginatedItems[] = $obj;
					}
				}
			} else {
				$i = 0;
				foreach ($objects as $object) {
					if ($i >= $offset && $i < ($offset + $limit)) {
						$paginatedItems[] = $object;
					}
					$i++;
				}
			}
			$previousObjectSetsCount += count($objects);
		}

		$this->view->assign('contentArguments', array(
			$this->widgetConfiguration['as'] => $paginatedItems
		));
		$this->view->assign('configuration', $this->configuration);
		$this->view->assign('pagination', $this->buildPagination());
		$this->view->assign('variables', $this->variables);
	}

	/**
	 * Returns an array with the keys "pages", "current", "numberOfPages", "nextPage" & "previousPage"
	 *
	 * @return array
	 */
	protected function buildPagination() {
		$pages = array();
		for ($i = 1; $i <= $this->numberOfPages; $i++) {
			$pages[] = array('number' => $i, 'isCurrent' => ($i === $this->currentPage));
		}
		$pagination = array(
			'pages' => $pages,
			'current' => $this->currentPage,
			'numberOfPages' => $this->numberOfPages,
		);
		if ($this->currentPage < $this->numberOfPages) {
			$pagination['nextPage'] = $this->currentPage + 1;
		}
		if ($this->currentPage > 1) {
			$pagination['previousPage'] = $this->currentPage - 1;
		}
		return $pagination;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		parent::setViewConfiguration($view);

		// Template Path Override
		if ($this->configuration['pagerTemplate']) {
			$templatePathAndFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->configuration['pagerTemplate']);
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($templatePathAndFilename)) {
				$view->setTemplatePathAndFilename($templatePathAndFilename);
			}
		}
	}
}
