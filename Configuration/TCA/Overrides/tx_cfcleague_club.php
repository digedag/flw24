<?php

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
    exit('Access denied.');
}

$columns = [
    'feusers' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xlf:tx_cfcleague_club_feusers',
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
            'wizards' => Sys25\RnBase\Backend\Utility\TcaTool::getWizards('tx_cfcleague_club', ['suggest' => true]),
        ],
    ],
];

\Sys25\RnBase\Utility\Extensions::addTCAcolumns('tx_cfcleague_club', $columns, 1);
\Sys25\RnBase\Utility\Extensions::addToAllTCAtypes('tx_cfcleague_club', 'feusers', '', 'after:favorite');
