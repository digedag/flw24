<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

// -------------------------
// ------- HOOKS -----------
// -------------------------
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = 'System25\Flw24\Hook\Flw24MatchMarkerHook->addNewsRecords';

// Hook for match search
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getTableMapping_hook'][] = 'System25\Flw24\Hook\Search->getTableMappingMatch';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getJoins_hook'][] = 'System25\Flw24\Hook\Search->getJoinsMatch';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league']['search_Match_getTableMapping_hook'][] = 'System25\Flw24\Hook\Search->getTableMappingMatch';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league']['search_Match_getJoins_hook'][] = 'System25\Flw24\Hook\Search->getJoinsMatch';

System25\T3sports\Utility\Misc::removeMatchNote('11');
System25\T3sports\Utility\Misc::removeMatchNote('12');
System25\T3sports\Utility\Misc::removeMatchNote('13');
System25\T3sports\Utility\Misc::removeMatchNote('31');
System25\T3sports\Utility\Misc::removeMatchNote('32');
System25\T3sports\Utility\Misc::removeMatchNote('33');
System25\T3sports\Utility\Misc::removeMatchNote('200');

//\tx_cfcleague_util_Misc::registerMatchNote('LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_flw24_event_matchend','1000');
// \tx_cfcleague_util_Misc::registerMatchNote('LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_flw24_event_prereport','1010');
// \tx_cfcleague_util_Misc::registerMatchNote('LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_flw24_event_midreport','1011');
// \tx_cfcleague_util_Misc::registerMatchNote('LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_flw24_event_endreport','1012');
