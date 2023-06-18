<?php

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
    exit('Access denied.');
}

$columns = [
    'crfeuser' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xlf:tx_cfcleague_profile_crfeuser',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'fe_users',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
        ],
    ],
];

\Sys25\RnBase\Utility\Extensions::addTCAcolumns('tx_cfcleague_profiles', $columns, 1);
\Sys25\RnBase\Utility\Extensions::addToAllTCAtypes('tx_cfcleague_profiles', 'crfeuser', '', 'after:description');
