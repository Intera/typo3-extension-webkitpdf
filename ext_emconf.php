<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */
/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Webkit PDFs',
    'description' => 'Generate PDF files using WebKit rendering engine.',
    'category' => 'plugin',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => 'typo3temp/tx_webkitpdf/',
    'clearCacheOnLoad' => 0,
    'author' => 'Dev-Team Typoheads, EnzephaloN IT-Solutions',
    'author_email' => 'dev@typoheads.at, info@enzephalon.de',
    'author_company' => 'Typoheads GmbH, EnzephaloN IT-Solutions',
    'version' => '2.0.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
