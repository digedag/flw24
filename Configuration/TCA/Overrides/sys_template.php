<?php

if (!(defined('TYPO3') || defined('TYPO3_MODE'))) {
    exit('Access denied.');
}

call_user_func(function () {
    $extKey = 'flw24';

    // list static templates in templates selection
    \Sys25\RnBase\Utility\Extensions::addStaticFile($extKey, 'Configuration/Typoscript/flw24/', 'flw24 Anpassungen');
});
