<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

tx_rnbase::load('tx_rnbase_util_Extensions');

$columns = [
    'feusers' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xml:tx_cfcleague_club_feusers',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'fe_users',
            'size' => 5,
            'autoSizeMax' => 20,
            'minitems' => 0,
            'maxitems' => 100,
            'MM' => 'tx_cfcleague_club2feusers_mm',
            'MM_match_fields' => [
                    'tablenames' => 'fe_users',
            ],
            'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards('tx_cfcleague_club', ['suggest' => true]),
        ],
    ],
];

tx_rnbase_util_Extensions::addTCAcolumns('tx_cfcleague_club', $columns, 1);
tx_rnbase_util_Extensions::addToAllTCAtypes('tx_cfcleague_club', 'feusers', '', 'after:favorite');
