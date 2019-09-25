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
    'author' => 'Dev-Team Typoheads',
    'author_email' => 'dev@typoheads.at',
    'author_company' => 'Typoheads GmbH',
    'version' => '2.0.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '6.2.3-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
