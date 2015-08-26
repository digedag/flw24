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


tx_rnbase_util_Extensions::addStaticFile($_EXTKEY,'Configuration/Typoscript/flw24/', 'flw24 Anpassungen');
