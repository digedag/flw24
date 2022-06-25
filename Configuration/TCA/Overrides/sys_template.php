<?php

defined('TYPO3_MODE') or exit();

call_user_func(function () {
    $extKey = 'flw24';

    // list static templates in templates selection
    tx_rnbase_util_Extensions::addStaticFile($extKey, 'Configuration/Typoscript/flw24/', 'flw24 Anpassungen');
});
