<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

tx_rnbase::load('tx_rnbase_util_Extensions');

$columns = array(
	'crfeuser' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:flw24/Resources/Private/Language/locallang_db.xml:tx_cfcleague_profile_crfeuser',
		'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
		)
	),
);



tx_rnbase_util_Extensions::addTCAcolumns('tx_cfcleague_profiles',$columns,1);
tx_rnbase_util_Extensions::addToAllTCAtypes('tx_cfcleague_profiles','crfeuser','','after:description');

