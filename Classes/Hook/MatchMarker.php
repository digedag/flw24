<?php

use Sys25\More4T3sports\Hook\MatchMarkerHook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Rene Nitzsche (rene@system25.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_Flw24_Hook_MatchMarker extends MatchMarkerHook
{
    /**
     * Integrates output of preview and matchreport fields to matches.
     *
     * @param array $params
     * @param tx_cfcleaguefe_util_MatchMarker $parent
     */
    public function addNewsRecords($params, $parent)
    {
        $template = $params['template'];
        $match = $params['match'];
        $formatter = $params['formatter'];
        $confId = $params['confid'];

        $template = $this->addNews($match, $template, $params['marker'], $confId, $formatter, 'newsreport2');
        $template = $this->addNews($match, $template, $params['marker'], $confId, $formatter, 'newspreview2');
        $params['template'] = $template;
    }

    /**
     * @param string $content
     * @param array $config
     *
     * @return bool
     */
    public function disableLink($content, $config)
    {
        $matchData = $this->cObj->data;
        if (!isset($matchData['uid']) || !$matchData['uid']) {
            return true;
        }
        $feuser = tx_t3users_models_feuser::getCurrent();
        if (!$feuser) {
            return true;
        }

        return !Tx_Flw24_Utility_Access::isTickerAllowed($feuser, $matchData['uid']);
    }
}
