<?php
$libraryClassesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('pdflister') . 'Resources/Private/Library/';
return array(
    'FPDF' => $libraryClassesPath . 'fpdf17/fpdf.php',
    'FPDI' => $libraryClassesPath . 'FPDI-1.6.0/fpdi.php',
);