<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);
/** @var string $_EXTKEY The current extension key. */
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheLifetime'] =
    isset($_EXTCONF['cacheLifetime']) ? intval($_EXTCONF['cacheLifetime']) : 2592000;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheLimit'] =
    isset($_EXTCONF['cacheLimit']) ? intval($_EXTCONF['cacheLimit']) : 0;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debug'] =
    isset($_EXTCONF['debug']) ? boolval($_EXTCONF['debug']) : false;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['pdfCacheFolderIdentifier'] =
    isset($_EXTCONF['pdfCacheFolderIdentifier']) ? $_EXTCONF['pdfCacheFolderIdentifier'] : '0:/typo3temp/tx_webkitpdf/';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\Tx\Webkitpdf\Utility\CacheManager::CACHE_IDENTIFIER] = [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class,
    'backend' => \Tx\Webkitpdf\Utility\CacheDatabaseBackend::class,
    'options' => [
        'defaultLifetime' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheLifetime'],
        'maximumNumberOfEntries' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['cacheLimit'],
    ],
    'groups' => ['pages', 'all'],
];
