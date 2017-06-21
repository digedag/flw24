<?php

########################################################################
# Extension Manager/Repository config file for ext "flw24".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'flw 24',
	'description' => 'Anpassungen für flw24.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.1.0',
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
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-7.6.99',
			'php' => '5.3.7-8.1.99',
			'rn_base' => '0.15.0-0.0.0',
			'cfc_league' => '1.0.2-0.0.0',
			'more4t3sports' => '0.3.0-0.0.0',
			'tt_news' => '3.6.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array(
	),
);
