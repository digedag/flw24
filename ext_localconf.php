<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');



// -------------------------
// ------- HOOKS -----------
// -------------------------
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/MatchMarker.php:Tx_Flw24_Hook_MatchMarker->addNewsRecords';

// Hook for match search
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getTableMapping_hook'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/Search.php:&System25\Flw24\Hook\Search->getTableMappingMatch';
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['search_Match_getJoins_hook'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/Search.php:&System25\Flw24\Hook\Search->getJoinsMatch';

\tx_rnbase::load('tx_cfcleague_util_Misc');

\tx_cfcleague_util_Misc::removeMatchNote('11');
\tx_cfcleague_util_Misc::removeMatchNote('12');
//\tx_cfcleague_util_Misc::registerMatchNote('Mein Ereignis','1000');
