<?php
namespace System25\Flw24\Form;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2017 Rene Nitzsche (rene@system25.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */
class LineUp
{

    const MODALBOX_LINEUP_HOME = 'editbox_lineup_home';
    const MODALBOX_LINEUP_GUEST = 'editbox_lineup_guest';


    /**
     * Show modal box to edit team member home
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbEditHome($params, $form)
    {
        return $this->editLineup($params, $form, true);
    }
    /**
     * Show modal box to edit team member home
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbEditGuest($params, $form)
    {
        return $this->editLineup($params, $form, false);
    }
    protected function editLineup($params, $form, $isHome)
    {
        $uid = $form->getDataHandler()->getStoredData('uid');
        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

        // init the modalbox/childs with this record
        $players = $match->getProperty($isHome ? 'players_home' : 'players_guest');
        $players = $players ? \Tx_Rnbase_Utility_Strings::intExplode(',', $players) : [];
        $record = [
            'uid' => $match->getUid(),
            'players' => $players,
        ];
        $form->getWidget($this->getLineUpWidget($isHome))->setValue($record);

        // open the box
        return $form->getWidget($this->getLineUpWidget($isHome))->majixShowBox();
    }

    protected function getLineUpWidget($isHome)
    {
        return $isHome ? self::MODALBOX_LINEUP_HOME : self::MODALBOX_LINEUP_GUEST;
    }

    public function cbBtnCloseHome($params, $form)
    {
        // close the box
        return $form->getWidget(self::MODALBOX_LINEUP_HOME)->majixCloseBox();
    }
    public function cbBtnCloseGuest($params, $form)
    {
        // close the box
        return $form->getWidget(self::MODALBOX_LINEUP_GUEST)->majixCloseBox();
    }

    public function getPlayers($params, \tx_mkforms_forms_IForm $form)
    {
        /* @var $match \tx_cfcleague_models_Match */
        $uid = $form->getDataHandler()->getStoredData('uid');
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);
        $team = $params['team'] == 'home' ? $match->getHome() : $match->getGuest();
        $items = [];
        foreach ($team->getPlayers() As $profile) {
            /* @var $profile \tx_cfcleague_models_Profile */
            $items[] = [
                'caption' => $profile->getName(true),
                'value' => $profile->getUid(),
            ];
        }
        return $items;
    }

    /**
     * Save new lineup
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbUpdateLineup($params, $form)
    {
        $isHome = isset($params[self::MODALBOX_LINEUP_HOME.'__uid']);
        $prefix = $this->getLineUpWidget($isHome) . '__';
        $matchUid = $params[$prefix.'uid'];
        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchUid);
        if($match->isValid()) {
            // init the modalbox/childs with this record
            $players = $params[$prefix.'players'];
            $players = is_array($players) && !empty($players) ? implode(',', $players) : '';
            $match->setProperty($isHome ? 'players_home' : 'players_guest', $players);
            \tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
        }

        $ret = [];
        $ret[] = $isHome ? $this->cbBtnCloseHome($params, $form) : $this->cbBtnCloseGuest($params, $form);
        $ret[] = $form->getWidget( $isHome ? 'player_home' : 'player_guest')->majixRepaint();
        return $ret;
    }
}
