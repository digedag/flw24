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
class Team
{

    const MODALBOX_TEAMMEMBER_HOME = 'editbox_teammember_home';
    const MODALBOX_TEAMMEMBER_GUEST = 'editbox_teammember_guest';


    /**
     * Show modal box to edit team member home
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbEditTeamMemberHome($params, $form)
    {
        return $this->editTeamMember($params, $form, true);
    }
    /**
     * Show modal box to edit team member home
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbEditTeamMemberGuest($params, $form)
    {
        return $this->editTeamMember($params, $form, false);
    }
    protected function editTeamMember($params, $form, $isHome)
    {
        $uid = $form->getDataHandler()->getStoredData('uid');
        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);
        $team = $isHome ? $match->getHome() : $match->getGuest();

        // init the modalbox/childs with this record
        $form->getWidget($this->getTeamMemberWidget($isHome))->setValue($team->getProperty());
        //			tx_mkforms_util_Div::debug4ajax($aRecord);

        // open the box
        return $form->getWidget($this->getTeamMemberWidget($isHome))->majixShowBox();
    }

    protected function getTeamMemberWidget($isHome)
    {
        return $isHome ? self::MODALBOX_TEAMMEMBER_HOME : self::MODALBOX_TEAMMEMBER_GUEST;
    }
    public function cbBtnCloseTeamMemberHome($params, $form)
    {
        // close the box
        return $form->getWidget(self::MODALBOX_TEAMMEMBER_HOME)->majixCloseBox();
    }
    public function cbBtnCloseTeamMemberGuest($params, $form)
    {
        // close the box
        return $form->getWidget(self::MODALBOX_TEAMMEMBER_GUEST)->majixCloseBox();
    }

    public function getPlayersHomeSql($params, $form)
    {
        return $this->getPlayersSql($params, $form, true);
    }
    public function getPlayersGuestSql($params, $form)
    {
        return $this->getPlayersSql($params, $form, false);
    }
    protected function getPlayersSql($params, $form, $isHome)
    {
        $uid = (int) $form->getDataHandler()->getStoredData('uid');
        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

        $team = $isHome ? $match->getHome() : $match->getGuest();
        $options = [
            'sqlonly' => 1,
            // 'orderby' => 'minute desc, extra_time desc',
        ];
        $players = $team->getProperty('players');
        if($players) {
            $options['where'] = 'uid IN (' . $players. ')';
        }
        else {
            $options['where'] = '1=2';
        }

        return \Tx_Rnbase_Database_Connection::getInstance()->
            doSelect(sprintf('uid,first_name,last_name,%d As team, \'%s\' As side',
                $team->getUid(),
                $isHome ? 'home' : 'guest'
            ),
            'tx_cfcleague_profiles', $options);
    }
    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return NULL[]
     */
    public function cbNewPlayerSubmitClick($params, $form)
    {
        $isHome = isset($params[self::MODALBOX_TEAMMEMBER_HOME.'__uid']);
        $prefix = $this->getTeamMemberWidget($isHome) . '__';
        $teamUid = (int) $params[$prefix.'uid'];
        /* @var $team \tx_cfcleague_models_Team */
        $team = \tx_rnbase::makeInstance('tx_cfcleague_models_Team', $teamUid);

        $profile = $this->createProfile($form, $prefix);
        // In Team aufnehmen
        $players = $team->getProperty('players');
        $players = $players ? \Tx_Rnbase_Utility_Strings::intExplode(',', $players) : [];
        $players[] = $profile->getUid();
        $team->setProperty('players', implode(',', $players));
        \tx_cfcleague_util_ServiceRegistry::getTeamService()->persist($team);

        $ret = [];
        $ret[] = $form->getWidget($prefix.'players')->majixRepaint();
        $ret[] = $form->getWidget($prefix.'first_name')->majixClearData();
        $ret[] = $form->getWidget($prefix.'last_name')->majixClearData();

        return $ret;
    }

    /**
     *
     * @param \tx_mkforms_forms_Base $form
     * @return \tx_cfcleague_models_Profile
     */
    protected function createProfile($form, $prefix)
    {
        \tx_rnbase::load('tx_t3users_models_feuser');
        $record = [
            'pid' => \tx_rnbase_configurations::getExtensionCfgValue('cfc_league', 'profileRootPageId'),
            'crfeuser' => \tx_t3users_models_feuser::getCurrent()->getUid(),
        ];
        $fields = [
            'first_name',
            'last_name',
        ];
        foreach ($fields as $fieldName) {
            $record[$fieldName] = $form->getWidget($prefix.$fieldName)->getValue();
        }
        $profile = \tx_rnbase::makeInstance('tx_cfcleague_models_Profile', $record);

        \tx_cfcleague_util_ServiceRegistry::getProfileService()->persist($profile);

        return $profile;
    }

    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbRemoveProfile($params, $form)
    {
        /* @var $profile \tx_cfcleague_models_Profile */
        $profile = \tx_rnbase::makeInstance('tx_cfcleague_models_Profile', $params['uid']);
        if (! $profile->isValid()) {
            return [];
        }
        $isHome = $params['side'] == 'home';
        $prefix = $this->getTeamMemberWidget($isHome) . '__';

        /* @var $match \tx_cfcleague_models_Team */
        $team = \tx_rnbase::makeInstance('tx_cfcleague_models_Team', $params['team']);
        $players = $team->getProperty('players');
        $players = $players ? \Tx_Rnbase_Utility_Strings::intExplode(',', $players) : [];
        $idx = array_search($profile->getUid(), $players);
        if($idx !== false) {
            unset($players[$idx]);
            $team->setProperty('players', implode(',', $players));
            \tx_cfcleague_util_ServiceRegistry::getTeamService()->persist($team);
        }

        $ret = [];
        $ret[] = $form->getWidget($prefix.'players')->majixRepaint();
        return $ret;
    }
}
