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
            'typo3' => '7.6.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
