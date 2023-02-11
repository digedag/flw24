<?php

namespace System25\Flw24\Form;

use Sys25\RnBase\Configuration\Processor;
use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Domain\Repository\FeUserRepository;
use Sys25\RnBase\Utility\Strings;
use System25\T3sports\Model\Fixture;
use System25\T3sports\Model\Profile;
use System25\T3sports\Model\Repository\ProfileRepository;
use System25\T3sports\Model\Repository\TeamRepository;
use System25\T3sports\Model\Team as TeamModel;
use tx_rnbase;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017-2023 Rene Nitzsche (rene@system25.de)
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
    public const MODALBOX_TEAMMEMBER_HOME = 'editbox_teammember_home';
    public const MODALBOX_TEAMMEMBER_GUEST = 'editbox_teammember_guest';

    private $teamRepo;
    private $profileRepo;
    private $feUserRepo;

    public function __construct(TeamRepository $teamRepo = null, ProfileRepository $profileRepo = null, FeUserRepository $feUserRepo)
    {
        $this->teamRepo = $teamRepo ?: new TeamRepository();
        $this->profileRepo = $profileRepo ?: new ProfileRepository();
        $this->feUserRepo = $feUserRepo ?: new FeUserRepository();
    }

    /**
     * Show modal box to edit team member home.
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbEditTeamMemberHome($params, $form)
    {
        return $this->editTeamMember($params, $form, true);
    }

    /**
     * Show modal box to edit team member home.
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbEditTeamMemberGuest($params, $form)
    {
        return $this->editTeamMember($params, $form, false);
    }

    protected function editTeamMember($params, $form, $isHome)
    {
        $uid = $form->getDataHandler()->getStoredData('uid');
        /** @var Fixture $match */
        $match = tx_rnbase::makeInstance(Fixture::class, $uid);
        $team = $isHome ? $match->getHome() : $match->getGuest();

        // init the modalbox/childs with this record
        $form->getWidget($this->getTeamMemberWidget($isHome))->setValue($team->getProperty());

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
        /** @var Fixture $match */
        $match = tx_rnbase::makeInstance(Fixture::class, $uid);

        $team = $isHome ? $match->getHome() : $match->getGuest();
        $options = [
            'sqlonly' => 1,
//            'orderby' => 'last_name asc, first_name asc',
        ];
        $players = $team->getProperty('players');
        if ($players) {
            $options['where'] = 'uid IN ('.$players.')';
        } else {
            $options['where'] = '1=2';
        }

        return Connection::getInstance()->
            doSelect(sprintf('uid,first_name,last_name,%d As team, \'%s\' As side',
                $team->getUid(),
                $isHome ? 'home' : 'guest'
            ),
            'tx_cfcleague_profiles', $options);
    }

    /**
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return null[]
     */
    public function cbNewPlayerSubmitClick($params, $form)
    {
        $isHome = isset($params[self::MODALBOX_TEAMMEMBER_HOME.'__uid']);
        $prefix = $this->getTeamMemberWidget($isHome).'__';
        $teamUid = (int) $params[$prefix.'uid'];
        /** @var TeamModel */
        $team = tx_rnbase::makeInstance(TeamModel::class, $teamUid);

        $profile = $this->createProfile($form, $prefix);
        // In Team aufnehmen
        $players = $team->getProperty('players');
        $players = $players ? Strings::intExplode(',', $players) : [];
        $players[] = $profile->getUid();
        $team->setProperty('players', implode(',', $players));
        $this->teamRepo->persist($team);

        $ret = [];
        $ret[] = $form->getWidget($prefix.'players')->majixRepaint();
        $ret[] = $form->getWidget($prefix.'first_name')->majixClearData();
        $ret[] = $form->getWidget($prefix.'last_name')->majixClearData();

        return $ret;
    }

    /**
     * @param \tx_mkforms_forms_Base $form
     *
     * @return Profile
     */
    protected function createProfile($form, $prefix)
    {
        $feUser = $this->feUserRepo->getCurrent();
        $record = [
            'pid' => Processor::getExtensionCfgValue('cfc_league', 'profileRootPageId'),
            'crfeuser' => $feUser ? $feUser->getUid() : '',
        ];
        $fields = [
            'first_name',
            'last_name',
        ];
        foreach ($fields as $fieldName) {
            $record[$fieldName] = $form->getWidget($prefix.$fieldName)->getValue();
        }
        $profile = tx_rnbase::makeInstance(Profile::class, $record);

        $this->profileRepo->persist($profile);

        return $profile;
    }

    /**
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbRemoveProfile($params, $form)
    {
        /** @var Profile $profile */
        $profile = tx_rnbase::makeInstance(Profile::class, $params['uid']);
        if (!$profile->isValid()) {
            return [];
        }
        $isHome = 'home' == $params['side'];
        $prefix = $this->getTeamMemberWidget($isHome).'__';

        /** @var TeamModel $match */
        $team = tx_rnbase::makeInstance(TeamModel::class, $params['team']);
        $players = $team->getProperty('players');
        $players = $players ? Strings::intExplode(',', $players) : [];
        $idx = array_search($profile->getUid(), $players);
        if (false !== $idx) {
            unset($players[$idx]);
            $team->setProperty('players', implode(',', $players));
            $this->teamRepo->persist($team);
        }

        $ret = [];
        $ret[] = $form->getWidget($prefix.'players')->majixRepaint();

        return $ret;
    }
}
