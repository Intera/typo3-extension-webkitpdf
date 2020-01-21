<?php
namespace Tx\Webkitpdf\Generator;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Intera GmbH
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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Interface for PDF generators.
 */
class PdfGeneratorFactory implements SingletonInterface {

	/**
	 * Registry of all available PDF generator classes.
	 *
	 * @var array
	 */
	protected $generatorRegistry = array(
		'foreground' => ForegroundPdfGenerator::class,
		'background' => BackgroundPdfGenerator::class,
		'puppeteer' => PuppeteerPdfGenerator::class,
	);

	/**
	 * Creates a new PDF generator instance of the given type.
	 *
	 * @param string $type The type of the PDF generator that should be created.
	 * @return PdfGeneratorInterface
	 */
	public function getPdfGenerator($type) {

		if (isset($this->generatorRegistry[$type])) {
			$type = $this->generatorRegistry[$type];
		}

		if (!class_exists($type)) {
			throw new \InvalidArgumentException(sprintf('No PDF generator with the type %s is available.', $type), 1438072793);
		}

		$pdfGenerator = GeneralUtility::makeInstance($type);

		if (!$pdfGenerator instanceof PdfGeneratorInterface) {
			throw new \RuntimeException(sprintf('The PDF generator %s did not implement the PdfGeneratorInterface', get_class($pdfGenerator)), 1438072897);
		}

		return $pdfGenerator;
	}

	/**
	 * Creates a PDF generator instance based on the given config and initializes it
	 * with the configuration options passes in the config array.
	 *
	 * @param array $config
	 * @return PdfGeneratorInterface
	 */
	public function getPdfGeneratorForConfig(array $config) {

		$pdfGeneratorType = 'foreground';
		if (!empty($config['options']['customPdfGenerator'])) {
			$pdfGeneratorType = $config['options']['customPdfGenerator'];
		}

		$pdfGenerator = $this->getPdfGenerator($pdfGeneratorType);

		if (!empty($config['options']['customScriptPath'])) {
			$pdfGenerator->setWebkitExecutablePath($config['options']['customScriptPath']);;
		}

		$generatorOptions = isset($config['pdfGenerators'][$pdfGeneratorType]) ? $config['pdfGenerators'][$pdfGeneratorType] : array();
		$pdfGenerator->setGeneratorOptions($generatorOptions);

		$pdfGenerator->setIsDebugEnabled(!empty($config['options']['debug']));

		return $pdfGenerator;
	}
}
