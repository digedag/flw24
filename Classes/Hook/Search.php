<?php

namespace System25\Flw24\Hook;

use Sys25\RnBase\Database\Query\Join;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017-2018 Rene Nitzsche
 * Contact: rene@system25.de
 * All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * *************************************************************
 */

/**
 * Make additional join for match search.
 *
 * @author Rene Nitzsche
 */
class Search
{
    public function getTableMappingMatch($params, $parent)
    {
        $params['tableMapping']['TEAM1FEUSER'] = 'usr1';
        $params['tableMapping']['TEAM2FEUSER'] = 'usr2';
    }

    public function getJoinsMatch($params, $parent)
    {
        $useQB = is_array($params['join']);
        if (isset($params['tableAliases']['TEAM1FEUSER'])) {
            if ($useQB ?
                !$this->hasJoin($params['join'], 'tx_cfcleague_teams', 't1') :
                false === stripos($params['join'], 'tx_cfcleague_teams As t1')) {
                if ($useQB) {
                    $params['join'][] = new Join('MATCH', 'tx_cfcleague_teams', 't1.uid = MATCH.home', 't1');
                } else {
                    $params['join'] .= ' INNER JOIN tx_cfcleague_teams As t1 ON tx_cfcleague_games.home = t1.uid ';
                }
            }
            if ($useQB) {
                $params['join'][] = new Join('t1', 'tx_cfcleague_club2feusers_mm', 't1.club = usr1.uid_local', 'usr1', Join::TYPE_LEFT);
            } else {
                $params['join'] .= ' LEFT JOIN tx_cfcleague_club2feusers_mm AS usr1 ON t1.club = usr1.uid_local ';
            }
        }
        if (isset($params['tableAliases']['TEAM2FEUSER'])) {
            if ($useQB ?
                !$this->hasJoin($params['join'], 'tx_cfcleague_teams', 't2') :
                false === stripos($params['join'], 'tx_cfcleague_teams As t2')) {
                if ($useQB) {
                    $params['join'][] = new Join('MATCH', 'tx_cfcleague_teams', 't2.uid = MATCH.home', 't2');
                } else {
                    $params['join'] .= ' INNER JOIN tx_cfcleague_teams As t2 ON tx_cfcleague_games.guest = t2.uid ';
                }
            }
            if ($useQB) {
                $params['join'][] = new Join('t2', 'tx_cfcleague_club2feusers_mm', 't2.club = usr2.uid_local', 'usr2', Join::TYPE_LEFT);
            } else {
                $params['join'] .= ' LEFT JOIN tx_cfcleague_club2feusers_mm AS usr2 ON t2.club = usr2.uid_local ';
            }
        }
    }

    private function hasJoin($joins, $tablename, $alias)
    {
        foreach ($joins as $join) {
            /* @var $join Join */
            if ($join->getTable() == $tablename && $join->getAlias() == $alias) {
                return true;
            }
        }

        return false;
    }
}
