<?php

defined('TYPO3_MODE') or die();

call_user_func(function () {
    $extKey = 'flw24';

    ////////////////////////////////
    // Plugin Competition anmelden
    ////////////////////////////////

    // Einige Felder ausblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_flw24_form']='layout,select_key,pages';

    // Das tt_content-Feld pi_flexform einblenden
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_flw24_form']='pi_flexform';

    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',scope.betgame';

    tx_rnbase_util_Extensions::addPiFlexFormValue(
        'tx_flw24_form',
        'FILE:EXT:'.$extKey.'/Configuration/Flexform/plugin_form.xml'
    );

    tx_rnbase_util_Extensions::addPlugin([
            'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_db.php:plugin.flw24_form.label',
            'tx_flw24_form'
        ],
        'list_type',
        $extKey
    );
});
