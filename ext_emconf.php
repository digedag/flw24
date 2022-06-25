<?php

//#######################################################################
// Extension Manager/Repository config file for ext "flw24".
//
//
// Manual updates:
// Only the data in the array - everything else is removed by next
// writing. "version" and "dependencies" must not be touched!
//#######################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'flw 24',
    'description' => 'Anpassungen für flw24.',
    'category' => 'misc',
    'shy' => 0,
    'version' => '0.3.1',
    'dependencies' => 'cms',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Rene Nitzsche',
    'author_email' => 'rene@system25.de',
    'author_company' => 'System 25',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-0.0.0',
            'rn_base' => '0.15.0-0.0.0',
            'cfc_league' => '1.0.2-0.0.0',
            'more4t3sports' => '0.3.0-0.0.0',
            't3sportstats' => '0.3.0-0.0.0',
            'news' => '7.2.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    '_md5_values_when_last_written' => '',
    'suggests' => [],
];
