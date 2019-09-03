<?php
$EM_CONF[$_EXTKEY] = array(
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
	'version' => '2.1.1-dev',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '9.5.0-9.5.99'
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
