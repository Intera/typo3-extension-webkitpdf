<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile(
    'webkitpdf',
    'Configuration/TypoScript/',
    'WebKit PDF'
);
