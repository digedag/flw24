<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');



// -------------------------
// ------- HOOKS -----------
// -------------------------
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cfc_league_fe']['matchMarker_initRecord'][] = 'EXT:' . $_EXTKEY . '/Classes/Hook/MatchMarker.php:Tx_Flw24_Hook_MatchMarker->addNewsRecords';

