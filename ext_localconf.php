<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');



// -------------------------
// ------- HOOKS -----------
// -------------------------
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/MatchMarker.php:Tx_Flw24_Hook_MatchMarker->addNewsRecords';

// Hook for match search
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getTableMapping_hook'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/Search.php:&System25\Flw24\Hook\Search->getTableMappingMatch';
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getJoins_hook'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/Search.php:&System25\Flw24\Hook\Search->getJoinsMatch';
