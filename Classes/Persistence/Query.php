<?php
namespace EssentialDots\ExtbaseHijax\Persistence;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Query
 *
 * @package EssentialDots\ExtbaseHijax\Persistence
 */
class Query extends \TYPO3\CMS\Extbase\Persistence\Generic\Query {

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @var string
	 */
	protected $sqlStatement = '';

	/**
	 * @var array
	 */
	protected $edScaleTablesUsed = array();

	/**
	 * @var \EssentialDots\ExtbaseHijax\Persistence\Parser\SQL
	 */
	protected $sqlParser;

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \TYPO3\CMS\Extbase\Persistence\Generic\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\CMS\Extbase\Persistence\Generic\QueryInterface::ORDER_DESCENDING
	 * )
	 * where 'foo' and 'bar' are property names.
	 *
	 * @param array $orderings The property names to order by
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	// TODO: implement this
	/*
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;

		return $this;
	}
	*/

	/**
	 * Returns the property names to order the result by. Like this:
	 * array(
	 *  'foo' => \TYPO3\CMS\Extbase\Persistence\Generic\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\CMS\Extbase\Persistence\Generic\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 */
	// TODO: implement this
	/*
	public function getOrderings() {
		return $this->orderings;
	}
	*/

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function setLimit($limit) {
		parent::setLimit($limit);

		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($this->getEdScaleTablesUsedComment() . $statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * Resets a previously set maximum size of the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function unsetLimit() {
		parent::unsetLimit();

		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($this->getEdScaleTablesUsedComment() . $statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function setOffset($offset) {
		parent::setOffset($offset);

		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($this->getEdScaleTablesUsedComment() . $statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getLimitStatement() {
		if ($this->limit || $this->offset) {
			$limitStatement = ' LIMIT ';
			if ($this->offset) {
				$limitStatement .= $this->offset . ', ';
			}
			if ($this->limit) {
				$limitStatement .= $this->limit;
			} else {
				$limitStatement .= ' 18446744073709551615';
			}
		}

		return $limitStatement;
	}

	/**
	 * @return string
	 */
	protected function getEdScaleTablesUsedComment() {
		if (count($this->edScaleTablesUsed) > 0) {
			return '# @tables_used = ' . implode(',', $this->edScaleTablesUsed) . chr(10);
		}

		return '';
	}

	/**
	 * Sets the statement of this query programmatically. If you use this, you will lose the abstraction from a concrete storage
	 * backend (database).
	 *
	 * @param string $statement The statement
	 * @param array $parameters An array of parameters. These will be bound to placeholders '?' in the $statement.
	 * @return $this|\TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function statement($statement, array $parameters = array()) {
		$this->parameters = $parameters;
		if (preg_match('/^\s*#\s*@tables_used\s*=\s*(.*)\s*;/msU', $statement, $matches)) {
			$this->edScaleTablesUsed = GeneralUtility::trimExplode(',', $matches[1]);
			$this->sqlStatement = preg_replace('/^\s*#\s*@tables_used\s*=\s*(.*)\s*;/msU', '', $statement);
		} else {
			$this->edScaleTablesUsed = array();
			$this->sqlStatement = $statement;
		}
		$this->sqlParser = \EssentialDots\ExtbaseHijax\Persistence\Parser\SQL::parseString($this->sqlStatement);
		$this->statement = $this->qomFactory->statement($statement, $parameters);
		return $this;
	}
}