<?php
namespace Tx\Webkitpdf\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

use Tx\Webkitpdf\Generator\PdfGeneratorFactory;
use Tx\Webkitpdf\Utility\CacheManager;
use Tx\Webkitpdf\Utility\PdfUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Plugin 'WebKit PDFs' for the 'webkitpdf' extension.
 *
 * @author Reinhard Führicht <rf@typoheads.at>
 */
class PdfController extends AbstractPlugin {

	/**
	 * Should be same as classname of the plugin, used for CSS classes, variables
	 *
	 * @var string
	 */
	public $prefixId = 'tx_webkitpdf_pi1';

	/**
	 * The Extension key.
	 *
	 * @var string
	 */
	public $extKey = 'webkitpdf';

	/**
	 * If set, then caching is disabled if piVars are incoming while no cHash was set (Set this for all USER plugins!)
	 *
	 * @var bool
	 */
	public $pi_checkCHash = FALSE;

	/**
	 * If set, then links are 1) not using cHash and 2) not allowing pages to be cached. (Set this for all USER_INT plugins!)
	 *
	 * @var bool
	 */
	public $pi_USER_INT_obj = 1;

	/**
	 * @var CacheManager
	 */
	protected $cacheManager;

	/**
	 * The name of the GET parameter in which the URLs that should be converted
	 * to PDFs are passed to the plugin. "urls" is used by default but can be
	 * overwritten with the "customParameterName" option.^
	 *
	 * @var string
	 */
	protected $paramName = 'urls';

	/**
	 * The value that is used for th Content-Disposition header. By default attachment is used.
	 * With the config option "openFilesInline" the value will be changed to "inline".
	 *
	 * @var string
	 */
	protected $useInlineContentDisposition = FALSE;

	/**
	 * The filename that is used when the PDF is written to the storage.
	 * The prefix of this filename can be influenced with the "filePrefix" setting.
	 *
	 * @var string
	 */
	protected $filenameStorage;

	/**
	 * The filename that is used when the file is delivered. By default the storage filename is used.
	 * Can be overwritten with the "staticFileName" setting.
	 *
	 * @var string
	 */
	protected $filenameDownload;

	/**
	 * @var PdfUtility
	 */
	protected $pdfUtility;

	/**
	 * All values from the "options" namespace in the TypoScript configuration.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Init parameters. Reads TypoScript settings.
	 *
	 * @param    array $conf : The PlugIn configuration
	 * @return    void
	 */
	protected function init($conf) {

		$this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
		$this->pdfUtility = GeneralUtility::makeInstance(PdfUtility::class);

		$this->buildStaticFileNameInPageContext($conf);

		if (!empty($conf['options.']['additionalStylesheet']) && !empty($conf['options.']['additionalStylesheet.']['getFileAbsFileName'])) {
			$conf['options.']['additionalStylesheet'] = GeneralUtility::getFileAbsFileName($conf['options.']['additionalStylesheet']);
			unset($conf['options.']['additionalStylesheet.']['getFileAbsFileName']);
		}

		foreach (array('options', 'scriptParams') as $typoScriptPath) {

			$conf[$typoScriptPath] = array();
			if (is_array($conf[$typoScriptPath . '.']) && !empty($conf[$typoScriptPath . '.'])) {
				$conf[$typoScriptPath] = $this->processStdWraps($conf[$typoScriptPath . '.']);
			}

			unset($conf[$typoScriptPath . '.']);
		}

		if (is_array($conf['pdfGenerators.'])) {
			foreach ($conf['pdfGenerators.'] as $pdfGenerator => $pdfGeneratorConf) {
				$pdfGenerator = rtrim($pdfGenerator, '.');
				$conf['pdfGenerators'][$pdfGenerator] = array();
				if (is_array($pdfGeneratorConf) && !empty($pdfGeneratorConf)) {
					$conf['pdfGenerators'][$pdfGenerator] = $this->processStdWraps($pdfGeneratorConf);
				}
			}
		}
		unset($conf['pdfGenerators.']);

		$this->conf = $conf;
		$this->options = $conf['options'];

		$this->pi_setPiVarDefaults();

		if (!empty($this->options['customParameterName'])) {
			$this->paramName = $this->options['customParameterName'];
		}

		$this->filenameStorage = $this->filenameDownload = $this->options['filePrefix'] . GeneralUtility::hmac(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\Random::class)->generateRandomBytes(512), 'TxWebkitpdfPdfFilename') . '.pdf';
		if (!empty($this->options['staticFileName'])) {
			$this->filenameDownload = $this->pdfUtility->sanitizeFilename($this->options['staticFileName']);
		}

		if (substr($this->filenameDownload, strlen($this->filenameDownload) - 4) !== '.pdf') {
			$this->filenameDownload .= '.pdf';
		}

		if (!empty($this->options['openFilesInline'])) {
			$this->useInlineContentDisposition = TRUE;
		}
	}

	/**
	 * Uses the TypoScript settings from
	 *
	 * @param array $conf
	 */
	protected function buildStaticFileNameInPageContext(&$conf) {

		if (!is_array($this->piVars['pageUids']) || count($this->piVars['pageUids']) !== 1) {
			return;
		}

		if (empty($conf['options.']['staticFileNameInPageContext.'])) {
			return;
		}

		$pageUid = (int)$this->piVars['pageUids'][0];
		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

		$pageData = $this->getTypoScriptFrontendController()->sys_page->getPage($pageUid);

		$contentObject->start((array)$pageData, 'pages');
		$staticFileName = $contentObject->stdWrap('', $conf['options.']['staticFileNameInPageContext.']);

		if (!empty($staticFileName)) {

			// Overwrite the default statice filename setting and prevent stdWrap processing.
			$conf['options.']['staticFileName'] = $staticFileName;
			unset($conf['options.']['staticFileName.']);

			// Prevent duplicate stdWrap processing.
			unset($conf['options.']['staticFileNameInPageContext.']);
		}
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getTypoScriptFrontendController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param    string $content : The PlugIn content
	 * @param    array $conf : The PlugIn configuration
	 * @return    string        The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		$urls = $this->piVars[$this->paramName];
		if (!$urls) {
			if (isset($this->options['urls.'])) {
				$urls = $this->options['urls.'];
			} else {
				$urls = array($this->options['urls']);
			}
		}

		try {

			if (!is_array($urls) || empty($urls)) {
				throw new \InvalidArgumentException('No URL was submitted to the PDF generator.', 1423589347);
			}

			foreach ($urls as $url) {
				if (trim($url) === '') {
					throw new \InvalidArgumentException('An empty URL was submitted to the PDF generator.', 1423589578);
				}
			}

			$allowedHosts = FALSE;
			if (!empty($this->options['allowedHosts'])) {
				$allowedHosts = GeneralUtility::trimExplode(',', $this->options['allowedHosts']);
			}

			foreach ($urls as $key => $url) {

				// Do not use cache if a Frontend use is logged in and append the session ID to the URLs.
				if ($GLOBALS['TSFE']->loginUser) {
					$url = $this->pdfUtility->appendFESessionInfoToURL($url);
				}

				$urls[$key] = $this->pdfUtility->sanitizeURL($url, $allowedHosts);
			}

			$pdfFile = $this->generatePdfOrReadFromCache($urls);

			if ($this->options['fileOnly'] == 1) {
				return $pdfFile->getPublicUrl();
			}

			$this->dumpPdfFile($pdfFile);

			if (!$this->isCachingEnabled()) {
				$pdfFile->delete();
			}

			exit(0);

		} catch (\Exception $e) {
			header(HttpUtility::HTTP_STATUS_400);
			$this->cObj->data['tx_webkitpdf_error'] = $e->getMessage();
			$content .= $this->cObj->cObjGetSingle($this->conf['contentObjects.']['errorMessage'], $this->conf['contentObjects.']['errorMessage.']);
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * @param array $urls
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function generatePdfOrReadFromCache(array $urls) {

		$cachingEnabled = $this->isCachingEnabled();

		if ($cachingEnabled) {
			$pdfFile = $this->cacheManager->get($urls);
			if (isset($pdfFile)) {
				return $pdfFile;
			}
		}

		$tempFile = GeneralUtility::tempnam('tx_webkitpdf_temp_' . sha1(uniqid('tx_webkitpdf_temp_', TRUE)));

		$pdfGenerator = $this->getPdfGenerator();
		$pdfGenerator->generatePdf($urls, $tempFile);

		if (filesize($tempFile) > 0) {
			$pageUids = !empty($this->piVars['pageUids']) && is_array($this->piVars['pageUids']) ? $this->piVars['pageUids'] : array();
			$pdfFile = $this->cacheManager->store($urls, $tempFile, $pageUids, $cachingEnabled);
		} else {
			throw new \RuntimeException('The PDF generator did not fill the PDF file with contents.');
		}

		if (file_exists($tempFile)) {
			GeneralUtility::unlink_tempfile($tempFile);
		}

		return $pdfFile;
	}

	/**
	 * Initializes a PDF generator instance with all required config options.
	 *
	 * @return \Tx\Webkitpdf\Generator\PdfGeneratorInterface
	 */
	protected function getPdfGenerator() {
		$pdfGenerator = GeneralUtility::makeInstance(PdfGeneratorFactory::class)->getPdfGeneratorForConfig($this->conf);
		$this->initializeScriptOptions($pdfGenerator);
		return $pdfGenerator;
	}

	/**
	 * Dumps the PDF file contents.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 */
	protected function dumpPdfFile($file) {

		header('Content-Transfer-Encoding: Binary');
		header('X-Robots-Tag: noindex');

		$file->getStorage()->dumpFileContents($file, !$this->useInlineContentDisposition, $this->filenameDownload);
	}

	/**
	 * Checks if caching should be enabled for this call.
	 *
	 * @return bool
	 */
	protected function isCachingEnabled() {

		if (!empty($this->options['disableCache'])) {
			return FALSE;
		}

		if (!empty($this->options['debug'])) {
			return FALSE;
		}

		if (!empty($GLOBALS['TSFE']->loginUser)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Initializes the script options in the given PDF generator.
	 *
	 * @param \Tx\Webkitpdf\Generator\PdfGeneratorInterface $pdfGenerator
	 * @return string The parameter string
	 */
	protected function initializeScriptOptions($pdfGenerator) {

		if (!empty($this->options['pageURLInHeader'])) {
			$pdfGenerator->setOption('header-center', '[webpage]');
		}

		if (!empty($this->options['copyrightNotice'])) {
			$pdfGenerator->setOption('footer-left', '© ' . date('Y', time()) . $this->options['copyrightNotice']);
		}

		if (!empty($this->options['additionalStylesheet'])) {
			$pdfGenerator->setOption('user-style-sheet', $this->options['additionalStylesheet']);
		}

		if (!empty($this->options['overrideUserAgent'])) {
			$pdfGenerator->setOption('custom-header-propagation', '');
			$pdfGenerator->setOption('custom-header', array('User-Agent', $this->options['overrideUserAgent']));
		}

		foreach ($this->conf['scriptParams'] as $name => $value) {
			$pdfGenerator->setOption($name, $value);
		}
	}

	/**
	 * Processes the stdWrap properties of the input array
	 *
	 * @param    array $tsSettings The TypoScript array
	 * @return    array    The processed values
	 */
	protected function processStdWraps($tsSettings) {

		// Get TS values and process stdWrap properties
		if (is_array($tsSettings)) {
			foreach ($tsSettings as $key => $value) {

				if (
					(substr($key, -1) === '.' && !array_key_exists(substr($key, 0, -1), $tsSettings))
					|| (substr($key, -1) !== '.' && array_key_exists($key . '.', $tsSettings))
				) {

					$tsSettings[$key] = $this->cObj->stdWrap($value, $tsSettings[$key . '.']);

					// Remove the additional TS properties after processing, otherwise they'll be translated to pdf properties
					if (isset($tsSettings[$key . '.'])) {
						unset($tsSettings[$key . '.']);
					}
				}
			}
		}

		return $tsSettings;
	}
}
