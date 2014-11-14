<?php
namespace EssentialDots\ExtbaseHijax\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class ScriptViewHelper
 *
 * @package EssentialDots\ExtbaseHijax\ViewHelpers
 */
class ScriptViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \EssentialDots\ExtbaseHijax\Utility\Ajax\Dispatcher
	 * @inject
	 */
	protected $ajaxDispatcher;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 * @inject
	 */
	protected $pageRenderer;

	/**
	 * Returns TRUE if what we are outputting may be cached
	 *
	 * @return boolean
	 */
	protected function isCached() {
		$userObjType = $this->configurationManager->getContentObject()->getUserObjectType();
		return ($userObjType !== \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER_INT);
	}

	/**
	 * @param string $src
	 * @param string $type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @param string $section
	 * @param boolean $preventMarkupUpdateOnAjaxLoad
	 * @param boolean $moveToExternalFile
	 * @param boolean $noCache
	 * @param string $name
	 *
	 * @return string
	 */
	public function render(
			$src = '', $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '',
			$excludeFromConcatenation = FALSE, $section = 'footer', $preventMarkupUpdateOnAjaxLoad = FALSE,
			$moveToExternalFile = FALSE, $noCache = FALSE, $name = '') {
		$content = $this->renderChildren();

		if ($this->ajaxDispatcher->getIsActive()) {
			if ($preventMarkupUpdateOnAjaxLoad) {
				$this->ajaxDispatcher->setPreventMarkupUpdateOnAjaxLoad(TRUE);
			}
			// need to just echo the code in ajax call
			if (!$src) {
				if ($compress) {
					$content = $this->compressScript($content);
				}
				return \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
			} else {
				return '<script type="' . htmlspecialchars($type) . '" src="' . htmlspecialchars($src) . '"></script>';
			}
		} else {
			if ($this->isCached()) {
				if ($noCache) {
					if ($src) {
						$content = '<script type="' . htmlspecialchars($type) . '" src="' . htmlspecialchars($src) . '"></script>';
					} else {
						if ($compress) {
							$content = $this->compressScript($content);
						}
						$content = \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
					}
					$tslibFrontEndHook = GeneralUtility::makeInstance('EssentialDots\\ExtbaseHijax\\Tslib\\FE\\Hook');
					/* @var $tslibFrontEndHook \EssentialDots\ExtbaseHijax\Tslib\FE\Hook */

					if ($section == 'footer') {
						$tslibFrontEndHook->addNonCacheableFooterCode($name ? $name : md5($content), $content);
					} else {
						$tslibFrontEndHook->addNonCacheableHeaderCode($name ? $name : md5($content), $content);
					}

					return '';
				} else {
					if (!$src && $moveToExternalFile) {
						$src = 'typo3temp' . DIRECTORY_SEPARATOR . 'extbase_hijax' . DIRECTORY_SEPARATOR . md5($content) . '.js';
						\TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir(PATH_site . $src, $content);

						if ($GLOBALS['TSFE']) {
							if ($GLOBALS['TSFE']->baseUrl) {
								$src = $GLOBALS['TSFE']->baseUrl . $src;
							} elseif ($GLOBALS['TSFE']->absRefPrefix) {
								$src = $GLOBALS['TSFE']->absRefPrefix . $src;
							}
						}
					}

					if (!$src) {
						if ($section == 'footer') {
							$this->pageRenderer->addJsFooterInlineCode($name ? $name : md5($content), $content, $compress, $forceOnTop);
						} else {
							$this->pageRenderer->addJsInlineCode($name ? $name : md5($content), $content, $compress, $forceOnTop);
						}
					} else {
						if ($section == 'footer') {
							$this->pageRenderer->addJsFooterFile($src, $type, $compress, $forceOnTop, $allWrap, $excludeFromConcatenation);
						} else {
							$this->pageRenderer->addJsFile($src, $type, $compress, $forceOnTop, $allWrap, $excludeFromConcatenation);
						}
					}
				}
			} else {
				// additionalFooterData not possible in USER_INT
				if (!$src) {
					$GLOBALS['TSFE']->additionalHeaderData[$name ? $name : md5($content)] = \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($content);
				} else {
					$GLOBALS['TSFE']->additionalHeaderData[$name ? $name : md5($content)] = '<script type="' . htmlspecialchars($type) . '" src="' . htmlspecialchars($src) . '"></script>';
				}
			}
		}

		return '';
	}

	/**
	 * @param string $source
	 * @return string
	 */
	protected function compressScript($source) {
		if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'])) {
			$sourceArr = array($source);
			// Use external compression routine
			$params = array(
				'jsInline' => &$sourceArr,
			);
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'], $params, $this->pageRenderer);
			$compressedSource = $params['jsInline'][0];
		} else {
			$error = '';
			$compressedSource = \TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript($source, $error);
			if ($error) {
				$compressedSource = $source;
				error_log('Error with minify JS Inline Block: ' . $error);
			}
		}

		return $compressedSource;
	}
}
