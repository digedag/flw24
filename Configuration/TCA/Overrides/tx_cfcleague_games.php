<?php

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
        exit('Access denied.');
    }
    
$tableName = Sys25\RnBase\Utility\TYPO3::isExtLoaded('news') ? 'tx_news_domain_model_news' : 'tt_news';

$columns = [
    'newspreview2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xlf:tx_cfcleague_games_newspreview2',
            'config' => [
                    'type' => 'group',
                    'internal_type' => 'db',
                    'allowed' => $tableName,
                    'size' => 1,
                    'minitems' => 0,
                    'maxitems' => 1,
            ],
    ],
    'newsreport2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xlf:tx_cfcleague_games_newsreport2',
            'config' => [
                    'type' => 'group',
                    'internal_type' => 'db',
                    'allowed' => $tableName,
                    'size' => 1,
                    'minitems' => 0,
                    'maxitems' => 1,
            ],
    ],
];

\Sys25\RnBase\Utility\Extensions::addTCAcolumns('tx_cfcleague_games', $columns, 1);
\Sys25\RnBase\Utility\Extensions::addToAllTCAtypes('tx_cfcleague_games', 'newspreview2, newsreport2', '', 'after:newsreport');
