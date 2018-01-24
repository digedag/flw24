<?php
namespace System25\Flw24\Form;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2017-2018 Rene Nitzsche (rene@system25.de)
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
\tx_rnbase::load('tx_cfcleague_models_MatchNote');

class Ticker
{

    private $playerNames = [];

    /** In dieser Box werden vorhandene Notes bearbeitet */
    const MODALBOX_TICKER = 'editbox_ticker';

    /**
     * Speichern von Tickermeldungen
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbTickerSubmitClick($params, $form)
    {
        \tx_rnbase::load('tx_t3users_models_feuser');
        // Die Match-UID wird im DataHandler persistiert
        $uid = $form->getDataHandler()->getStoredData('uid');
        $record = [
            'crfeuser' => \tx_t3users_models_feuser::getCurrent()->getUid(),
            'game' => $uid
        ];
        $fields = [
            'minute',
            'extra_time',
            'type',
            'player_home',
            'player_guest',
            'comment'
        ];
        foreach ($fields as $fieldName) {
            $record[$fieldName] = $form->getWidget($fieldName)->getValue();
        }

        /* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
        $repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
        $model = $repo->createNewModel($record);
        if($record['type'] == \tx_cfcleague_models_MatchNote::TYPE_CHANGEOUT) {
            $this->handleChange($form, $model, $repo);
        }
        $repo->persist($model);

        $ret = array(
            $form->getWidget('box_base')->majixClearValue(),
            $form->getWidget('box_players')->majixDisplayNone(),
            $form->getWidget('box_change')->majixDisplayNone(),
            $form->getWidget('matchnotes')->majixRepaint()
        );

        // Spielticker ggf. aktivieren, wenn das Spiel nicht in Vergangenheit liegt
        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);
        if ($this->ensureTickerActive($match, $form, $model->getMinute())) {
            $ret[] = $form->getWidget('link_ticker')->majixSetValue($match->getProperty('link_ticker'));
            $ret[] = $form->getWidget('status')->majixSetValue($match->getProperty('status'));
        }
        if ($this->ensureScore($model, $match, $form)) {
            $ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
            $ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
        }
        if ($this->ensureStatus($model, $match, $form)) {
            $ret[] = $form->getWidget('status')->majixSetValue($match->getStatus());
        }

        return $ret;
    }

    /**
     * Sonderbehandlung für Spielerwechsel. Es muss ein zweite Note angelegt werden.
     * @param \tx_mkforms_forms_Base $form
     * @param \tx_cfcleague_model_MatchNote $model
     * @param \Tx_Cfcleague_Model_Repository_MatchNote $repo
     */
    protected function handleChange(\tx_mkforms_forms_Base $form, \tx_cfcleague_models_MatchNote $model, $repo)
    {
        $team = 'home';
        $playerOut = $form->getWidget('player_home_changeout')->getValue();
        $playerIn = $form->getWidget('player_home_changein')->getValue();
        if(!$playerOut) {
            $playerOut = $form->getWidget('player_guest_changeout')->getValue();
            $playerIn = $form->getWidget('player_guest_changein')->getValue();
            $team = 'guest';
        }
        $record = (array) (object) $model->getProperty();
        // Den Spieler im vorhandenen Model setzen
        $model->setProperty('player_'.$team, $playerOut);
        // Jetzt ein weiteres Model anlegen
        $model2 = clone $model;
        $model2->setProperty($record);
        $model2->setProperty('player_'.$team, $playerIn);
        $model2->setProperty('type', \tx_cfcleague_models_MatchNote::TYPE_CHANGEIN);
        $repo->persist($model2);
    }
    /**
     *
     * @param tx_cfcleague_models_MatchNote $ticker
     * @param tx_cfcleague_models_Match $match
     * @param \tx_mkforms_forms_Base $form
     */
    protected function ensureStatus($ticker, $match, \tx_mkforms_forms_Base $form)
    {
        if ($ticker->getType() != 1000) {
            return false;
        }
        $match->setProperty('status', 2);
        \tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
        return true;
    }

    /**
     *
     * @param tx_cfcleague_models_MatchNote $ticker
     * @param tx_cfcleague_models_Match $match
     * @param \tx_mkforms_forms_Base $form
     */
    protected function ensureScore($ticker, $match, \tx_mkforms_forms_Base $form)
    {
        if (! $ticker->isGoal()) {
            return false;
        }

        \tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
        $tickerArr = \tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
        // im letzten Eintrag steht der aktuelle Spielstand
        $lastTicker = end($tickerArr);
        if ($lastTicker) {
            $match->setProperty('goals_home_2', $lastTicker->getProperty('goals_home'));
            $match->setProperty('goals_guest_2', $lastTicker->getProperty('goals_guest'));
            \tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
        }

        return true;
    }

    /**
     * Spiel- und Tickerstatus automatisch setzen
     *
     * @param tx_cfcleague_models_Match $match
     */
    protected function ensureTickerActive($match, \tx_mkforms_forms_Base $form, $minute)
    {
        \tx_rnbase::load('tx_rnbase_util_Dates');
        if (($match->isTicker() && $match->isRunning()) || $match->isFinished()) {
            return false;
        }
        // Liegt das Spiel in der Vergangenheit
        $kickoff = \tx_rnbase_util_Dates::date_tstamp2mysql($match->getDate());
        $kickoff = \tx_rnbase_util_Dates::date_mysql2int($match->getDate());
        if (\tx_rnbase_util_Dates::getTodayDateString() > $kickoff) {
            return false;
        }
        $match->setProperty('link_ticker', 1);
        if (((int) $minute) > 0) {
            $match->setProperty('status', \tx_cfcleague_models_Match::MATCH_STATUS_RUNNING);
        }
        \tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
        return true;
    }
    /**
     * Show modal box to edit match note
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbEditMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
        if (! $matchNote->isValid()) {
            return [];
        }

        // keeping the current uid
//        $form->oSandBox->iRecordUid = $matchNote->getUid();
        // init the modalbox/childs with this record
        $form->getWidget(self::MODALBOX_TICKER)->setValue($matchNote->getProperty());
        //			tx_mkforms_util_Div::debug4ajax($aRecord);

        // open the box
        return $form->getWidget(self::MODALBOX_TICKER)->majixShowBox();
    }
    public function cbBtnCancelTicker($params, $form) {
        // close the box
        return $form->getWidget(self::MODALBOX_TICKER)->majixCloseBox();
    }

    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbUpdateMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params[self::MODALBOX_TICKER.'__uid']);
        if (! $matchNote->isValid()) {
            return $this->cbBtnCancelTicker($params, $form);
        }
        $prefix = self::MODALBOX_TICKER.'__';
        $fields = [
            'minute',
            'extra_time',
            'type',
            'player_home',
            'player_guest',
            'comment'
        ];
        foreach ($fields as $fieldName) {
            if (isset($params[$prefix . $fieldName])) {
                $matchNote->setProperty($fieldName, $params[$prefix . $fieldName]);
            }
        }

        /* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
        $repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
        $repo->persist($matchNote);

        $ret = [
            $form->getWidget('matchnotes')->majixRepaint(),
            $form->getWidget(self::MODALBOX_TICKER)->majixCloseBox()
        ];

        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchNote->getProperty('game'));
        if ($this->ensureScore($matchNote, $match, $form)) {
            $ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
            $ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
        }

        return $ret;
    }

    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbDeleteMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
        if (! $matchNote->isValid()) {
            return [];
        }

        /* @var $match \tx_cfcleague_models_Match */
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchNote->getProperty('game'));
        $matchNoteClone = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $matchNote->getProperty());

        /* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
        $repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
        $repo->handleDelete($matchNote);

        $ret = [
            $form->getWidget('matchnotes')->majixRepaint()
        ];

        if ($this->ensureScore($matchNoteClone, $match, $form)) {
            $ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
            $ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
        }
        return $ret;
    }

    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function cbMatchSubmitClick($params, $form)
    {
        $uid = $form->getDataHandler()->getStoredData('uid');
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

        $fields = [
            'goals_home_2',
            'goals_home_1',
            'goals_guest_2',
            'goals_guest_1',
            'visitors',
            'status',
            'link_ticker'
        ];
        foreach ($fields as $fieldName) {
            $match->setProperty($fieldName, $form->getWidget($fieldName)
                ->getValue());
        }

        \tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
        return array();
    }

    public function getMatchNoteSql($params, $form)
    {
        $uid = (int) $form->getDataHandler()->getStoredData('uid');
        $options = [
            'sqlonly' => 1,
            'where' => 'game=' . $uid
            // 'orderby' => 'minute desc, extra_time desc',
        ];

        return \Tx_Rnbase_Database_Connection::getInstance()->doSelect('*', 'tx_cfcleague_match_notes', $options);
    }

    /**
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function validatePlayer($params, $form)
    {
        $type = $form->getWidget('type')->getValue();
        if ($type == 100 || $type >= 1000) {
            // Hier ist der Spieler egal
            return true;
        }
        if ($type == \tx_cfcleague_models_MatchNote::TYPE_CHANGEOUT) {
            // Bei Auswechslungen werden zwei Spieler benötigt
            if(
                !(
                ($this->hasValue($form, 'player_home_changeout') && $this->hasValue($form, 'player_home_changein'))
                ||
                ($this->hasValue($form, 'player_guest_changeout') && $this->hasValue($form, 'player_guest_changein'))
                )) {
                return false;
            }
        }
        else {
            $home = $form->getWidget('player_home')->getValue();
            $guest = $form->getWidget('player_guest')->getValue();
            // Jetzt muss genau ein Spieler gesetzt sein
            if ($home != 0 && $guest != 0 || $home == 0 && $guest == 0) {
                return false;
                // return "LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_msg_player_not_set";
            }
        }

        return true;
    }
    protected function hasValue($form, $widgetName)
    {
        return $form->getWidget($widgetName)->getValue() > 0;
    }

    /**
     * Validator für TickerType
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     * @return []
     */
    public function validatePlayerModal($params, $form)
    {
        $type = $form->getWidget(self::MODALBOX_TICKER. '__type')->getValue();
        if ($type == 100 || $type == 1000) {
            // Hier ist der Spieler egal
            return true;
        }
        $home = $form->getWidget(self::MODALBOX_TICKER. '__player_home')->getValue();
        $guest = $form->getWidget(self::MODALBOX_TICKER. '__player_guest')->getValue();
        // Jetzt muss genau ein Spieler gesetzt sein
        if ($home != 0 && $guest != 0 || $home == 0 && $guest == 0) {
            return false;
            // return "LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_msg_player_not_set";
        }

        return true;
    }

    public function fillMatchForm($params, \tx_mkforms_forms_IForm $form)
    {
        $match = $form->getParent()->getItem();

        $starttime = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(Watch::FIELD_TICKER_STARTTIME, 'flw24');
        if ($starttime) {
            $match->setProperty(Watch::FIELD_TICKER_STARTTIME, $starttime);
        }
        $pausetime = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(Watch::FIELD_TICKER_PAUSETIME, 'flw24');
        if ($pausetime) {
            $match->setProperty(Watch::FIELD_TICKER_PAUSETIME, $pausetime);
        }
        $offset = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(Watch::FIELD_TICKER_OFFSET, 'flw24');
        if ($offset) {
            $match->setProperty(Watch::FIELD_TICKER_OFFSET, $offset);
        }
        $offset = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(Watch::FIELD_TICKER_MATCHPART, 'flw24');
        if ($offset) {
            $match->setProperty(Watch::FIELD_TICKER_MATCHPART, $offset);
        }

        return $match->getProperty();
    }

    /**
     * Liefert die Tickertypen ohne Auswechslung
     *
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     * @return []
     */
    public function getTickerTypes($params, \tx_mkforms_forms_IForm $form)
    {
        $tcaTypes = $this->loadTickerTypes();
        $data = [];
        foreach ($tcaTypes as $typeDef) {
            if (! $this->isChange($typeDef[1])) {
                $data[] = [
                    'caption' => $typeDef[0],
                    'value' => $typeDef[1]
                ];
            }
        }
        return $data;
    }
    /**
     * Liefert alle Tickertypen. Das wird für die Darstellung im Lister benötigt.
     *
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     * @return []
     */
    public function getTickerTypesAll($params, \tx_mkforms_forms_IForm $form)
    {
        $tcaTypes = $this->loadTickerTypes();
        $data = [];
        foreach ($tcaTypes as $typeDef) {
            $data[] = [
                'caption' => $typeDef[0],
                'value' => $typeDef[1]
            ];
        }
        return $data;
    }
    /**
     * Liefert alle Spieler von Aufstellung und Bank.
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     * @return number[][]|string[][]
     */
    public function getPlayers($params, \tx_mkforms_forms_IForm $form)
    {
        /* @var $match \tx_cfcleague_models_Match */
        $uid = $form->getDataHandler()->getStoredData('uid');
        $match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

        $data = $this->getPlayerNames($match, $params['team'], $form);
        return $data;
    }

    /**
     *
     * @param tx_cfcleague_models_Match $match
     * @param string $team
     */
    protected function getPlayerNames($match, $team, \tx_mkforms_forms_IForm $form)
    {
        if (isset($this->playerNames[$team])) {
            return $this->playerNames[$team];
        }

        $profileSrv = \tx_cfcleague_util_ServiceRegistry::getProfileService();
        if ($team == 'home') {
            $players = $profileSrv->loadProfiles($match->getPlayersHome(true));
        } else {
            $players = $profileSrv->loadProfiles($match->getPlayersGuest(true));
        }

        $this->playerNames = [
            $team => [
                [
                    'value' => 0,
                    'caption' => ''
                ]
            ]
        ];
        foreach ($players as $player) {
            $this->playerNames[$team][] = [
                'caption' => $player->getName(true),
                'value' => $player->getUid()
            ];
        }
        $this->playerNames[$team][] = [
            'value' => - 1,
            'caption' => $form->getConfigurations()->getLL('label_flw24_ticker_player_unknown'),
        ];
        usort($this->playerNames[$team], [LineUp::class, 'sortByCaption']);

        return $this->playerNames[$team];
    }

    protected function isChange($type)
    {
        return $type == 81;
    }

    protected function loadTickerTypes()
    {
        $srv = \tx_cfcleague_util_ServiceRegistry::getMatchService();
        return $srv->getMatchNoteTypes4TCA();
    }
}
