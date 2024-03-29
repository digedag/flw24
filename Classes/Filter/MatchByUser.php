<?php

namespace System25\Flw24\Filter;

use Sys25\RnBase\Frontend\Request\RequestInterface;
use System25\T3sports\Filter\MatchFilter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Rene Nitzsche (rene@system25.de)
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

class MatchByUser extends MatchFilter
{
    /**
     * Abgeleitete Filter können diese Methode überschreiben und zusätzliche Filter setzen.
     *
     * @param array $fields
     * @param array $options
     * @param \tx_rnbase_IParameters $parameters
     * @param \Tx_Rnbase_Configuration_ProcessorInterface $configurations
     * @param string $confId
     */
    protected function initFilter(&$fields, &$options, RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        parent::initFilter($fields, $options, $request);
        $configurations->convertToUserInt();

        $feuser = \tx_t3users_models_feuser::getCurrent();
        if ($feuser->isValid()) {
            // 			$fields['TEAM1FEUSER.UID_FOREIGN'][OP_GT_INT] = 0;
            // 			$fields['TEAM2FEUSER.UID_FOREIGN'][OP_GT_INT] = 0;
            $fields[SEARCH_FIELD_JOINED][] = [
                'value' => $feuser->getUid(),
                'cols' => ['TEAM1FEUSER.UID_FOREIGN', 'TEAM2FEUSER.UID_FOREIGN'],
                'operator' => OP_IN_INT,
            ];
        }

        return true;
    }
}
