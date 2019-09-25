<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['webkitpdf_pi1'] = 'layout,pages,select_key';

ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:webkitpdf/Resource/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
        'webkitpdf_pi1',
    ],
    'list_type',
    'webkitpdf'
);
