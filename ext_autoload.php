<?php
/*
 * Register necessary class names with autoloader
 *
 */
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extensionPath = ExtensionManagementUtility::extPath('flw24');


return array(
    'tx_flw24_utility_access'                 => $extensionPath. 'Classes/Utility/Access.php',
    'system25\flw24\utility\errors'                 => $extensionPath. 'Classes/Utility/Errors.php',
);
