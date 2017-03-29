<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

tx_rnbase::load('tx_rnbase_util_TYPO3');
// TODO: Nach umstellung der TCA in cfc_league hier auf die T3-Version prüfen
//if(!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
	tx_rnbase::load('tx_rnbase_util_Extensions');
	require tx_rnbase_util_Extensions::extPath($_EXTKEY).'Configuration/TCA/Overrides/tx_cfcleague_games.php';
//}

////////////////////////////////
// Plugin anmelden
////////////////////////////////
tx_rnbase::load('tx_rnbase_controller');

// Einige Felder ausblenden
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_flw24_form']='layout,select_key,pages';

// Das tt_content-Feld pi_flexform einblenden
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_flw24_form']='pi_flexform';

tx_rnbase_util_Extensions::addPiFlexFormValue('tx_flw24_form','FILE:EXT:'.$_EXTKEY.'/Configuration/Flexform/plugin_form.xml');
tx_rnbase_util_Extensions::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_db.php:plugin.flw24_form.label','tx_flw24_form'));


tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'Configuration/Typoscript/flw24/', 'flw24 Anpassungen');
