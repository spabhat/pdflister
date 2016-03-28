<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Scwebs.' . $_EXTKEY,
	'Listpdf',
	array(
		'ListPdf' => 'list, get',
		
	),
	// non-cacheable actions
	array(
		'ListPdf' => 'list, get',
		
	)
);

if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['folder_cache'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['folder_cache'] = array();
}
