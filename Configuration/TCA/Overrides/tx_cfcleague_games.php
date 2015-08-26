<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

tx_rnbase::load('tx_rnbase_util_Extensions');

$columns = array(
	'newspreview2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xml:tx_cfcleague_games_newspreview2',
			'config' => Array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_news',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
			)
	),
	'newsreport2' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xml:tx_cfcleague_games_newsreport2',
			'config' => Array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => 'tt_news',
					'size' => 1,
					'minitems' => 0,
					'maxitems' => 1,
			)
	),
);

tx_rnbase_util_Extensions::addTCAcolumns('tx_cfcleague_games',$columns,1);
tx_rnbase_util_Extensions::addToAllTCAtypes('tx_cfcleague_games','newspreview2, newsreport2','','after:newsreport');

