<?php

namespace System25\Flw24\Utility;
use Sys25\RnBase\Domain\Model\FeUser;
use System25\T3sports\Utility\ServiceRegistry;

/***************************************************************
*  Copyright notice
*
*  (c) 2017-2022 Rene Nitzsche (rene@system25.de)
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

class Access
{
    public const CODE_NOT_LOGGED_IN = 1000;

    public static function isTickerAllowed(FeUser $feuser, $matchUid)
    {
        $fields = [
            'TEAM1FEUSER.UID_FOREIGN' => [OP_EQ_INT => $feuser->getUid()],
            'MATCH.UID' => [OP_EQ_INT => $matchUid],
        ];
        $options = [
            'count' => 1,
        ];
        $cnt = ServiceRegistry::getMatchService()->search($fields, $options);
        $home = $cnt > 0;

        $fields = [
            'TEAM2FEUSER.UID_FOREIGN' => [OP_EQ_INT => $feuser->getUid()],
            'MATCH.UID' => [OP_EQ_INT => $matchUid],
        ];
        $options = [
            'count' => 1,
        ];
        $cnt = ServiceRegistry::getMatchService()->search($fields, $options);
        $guest = $cnt > 0;

        return $home || $guest;
    }
}
