<?php

namespace System25\Flw24\Form;

use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Utility\Dates;
use System25\T3sports\Model\Match;
use System25\T3sports\Model\MatchNote;
use System25\T3sports\Model\Repository\MatchNoteRepository;
use System25\T3sports\Model\Repository\MatchRepository;
use System25\T3sports\Utility\MatchTicker;
use System25\T3sports\Utility\ServiceRegistry;
use tx_rnbase;

/*
 * *************************************************************
 * Copyright notice
 *
 * (c) 2017-2022 Rene Nitzsche (rene@system25.de)
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

class Ticker
{
    private $playerNames = [];

    /** In dieser Box werden vorhandene Notes bearbeitet */
    public const MODALBOX_TICKER = 'editbox_ticker';

    private $mnRepo;
    private $matchRepo;

    public function __construct(MatchNoteRepository $mnRepo = null, MatchRepository $matchRepo = null)
    {
        $this->mnRepo = $mnRepo ?: new MatchNoteRepository();
        $this->matchRepo = $matchRepo ?: new MatchRepository();
    }

    /**
     * @param \tx_mkforms_forms_IForm $form
     *
     * @return \tx_cfcleague_models_Match
     */
    protected function getCurrentMatch(\tx_mkforms_forms_IForm $form)
    {
        // Die Match-UID wird im DataHandler persistiert
        $uid = $form->getDataHandler()->getStoredData('uid');
        /* @var $match \tx_cfcleague_models_Match */
        return tx_rnbase::makeInstance(Match::class, $uid);
    }

    /**
     * Speichern von Tickermeldungen.
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbTickerSubmitClick($params, $form)
    {
        $match = $this->getCurrentMatch($form);
        $feuser = \tx_t3users_models_feuser::getCurrent();
        if (!$feuser) {
            // throw new \Exception('Login please!', \System25\Flw24\Utility\Errors::CODE_NOT_LOGGED_IN);
            \tx_mkforms_util_Div::debug4ajax('Session timed out. Login please!');

            return [];
        }

        $record = [
            'crfeuser' => $feuser->getUid(),
            'game' => $match->getUid(),
            'pid' => $match->getProperty('pid'),
        ];
        $fields = [
            'minute',
            'extra_time',
            'type',
            'player_home',
            'player_guest',
            'comment',
        ];
        foreach ($fields as $fieldName) {
            $record[$fieldName] = $form->getWidget($fieldName)->getValue();
        }

        $model = $this->mnRepo->createNewModel($record);
        if (MatchNote::TYPE_CHANGEOUT == $record['type']) {
            $this->handleChange($form, $model, $this->mnRepo);
        }
        $this->mnRepo->persist($model);

        $ret = [
            $form->getWidget('box_base')->majixClearValue(),
            $form->getWidget('box_players')->majixDisplayNone(),
            $form->getWidget('box_change')->majixDisplayNone(),
            $form->getWidget('matchnotes')->majixRepaint(),
        ];

        // Spielticker ggf. aktivieren, wenn das Spiel nicht in Vergangenheit liegt
        $this->ensureTickerActive($match, $form, $model->getMinute());
        $this->ensureScore($model, $match, $form);
        $this->ensureStatus($model, $match, $form);

        return $ret;
    }

    /**
     * Sonderbehandlung für Spielerwechsel. Es muss ein zweite Note angelegt werden.
     *
     * @param \tx_mkforms_forms_Base $form
     * @param MatchNote $model
     * @param MatchNoteRepository $repo
     */
    protected function handleChange(\tx_mkforms_forms_Base $form, MatchNote $model, $repo)
    {
        $team = 'home';
        $playerOut = $form->getWidget('player_home_changeout')->getValue();
        $playerIn = $form->getWidget('player_home_changein')->getValue();
        if (!$playerOut) {
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
     * @param MatchNote $ticker
     * @param Match $match
     * @param \tx_mkforms_forms_Base $form
     */
    protected function ensureStatus($ticker, $match, \tx_mkforms_forms_Base $form)
    {
        if (1000 != $ticker->getType()) {
            return false;
        }

        $match->setProperty('status', 2);
        $this->matchRepo->persist($match);

        return true;
    }

    /**
     * @param MatchNote $ticker
     * @param Match $match
     * @param \tx_mkforms_forms_Base $form
     *
     * @return bool
     */
    protected function ensureScore($ticker, $match, \tx_mkforms_forms_Base $form)
    {
        if (!$ticker->isGoal()) {
            return false;
        }

        $this->persistScore($match, $this->getMatchPartFinal($match));

        return true;
    }

    /**
     * @param Match $match
     * @param string $part
     *
     * @return \tx_cfcleague_models_MatchNote|null
     */
    protected function persistScore($match, $part = '2')
    {
        $matchTicker = new MatchTicker();
        $tickerArr = $matchTicker->getTicker4Match($match);
        // im letzten Eintrag steht der aktuelle Spielstand
        /* @var $lastTicker \tx_cfcleague_models_MatchNote */
        $lastTicker = end($tickerArr);
        if ($lastTicker) {
            $match->setProperty('goals_home_'.$part, $lastTicker->getProperty('goals_home'));
            $match->setProperty('goals_guest_'.$part, $lastTicker->getProperty('goals_guest'));
            $this->matchRepo->persist($match);

            return $lastTicker;
        }

        return null;
    }

    /**
     * Spiel- und Tickerstatus automatisch setzen.
     *
     * @param Match $match
     */
    protected function ensureTickerActive($match, \tx_mkforms_forms_Base $form, $minute)
    {
        if (($match->isTicker() && $match->isRunning()) || $match->isFinished()) {
            return false;
        }
        // Liegt das Spiel in der Vergangenheit
        $kickoff = \tx_rnbase_util_Dates::date_tstamp2mysql($match->getDate());
        $kickoff = \tx_rnbase_util_Dates::date_mysql2int($match->getDate());
        if (Dates::getTodayDateString() > $kickoff) {
            return false;
        }
        $match->setProperty('link_ticker', 1);
        if (((int) $minute) > 0) {
            $match->setProperty('status', Match::MATCH_STATUS_RUNNING);
        }
        $this->matchRepo->persist($match);

        return true;
    }

    /**
     * Show modal box to edit match note.
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbEditMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = tx_rnbase::makeInstance(MatchNote::class, $params['uid']);
        if (!$matchNote->isValid()) {
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

    public function cbBtnCancelTicker($params, $form)
    {
        // close the box
        return $form->getWidget(self::MODALBOX_TICKER)->majixCloseBox();
    }

    /**
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbUpdateMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = tx_rnbase::makeInstance(MatchNote::class, $params[self::MODALBOX_TICKER.'__uid']);
        if (!$matchNote->isValid()) {
            return $this->cbBtnCancelTicker($params, $form);
        }
        $prefix = self::MODALBOX_TICKER.'__';
        $fields = [
            'minute',
            'extra_time',
            'type',
            'player_home',
            'player_guest',
            'comment',
        ];
        foreach ($fields as $fieldName) {
            if (isset($params[$prefix.$fieldName])) {
                $matchNote->setProperty($fieldName, $params[$prefix.$fieldName]);
            }
        }

        $this->mnRepo->persist($matchNote);

        $ret = [
            $form->getWidget('matchnotes')->majixRepaint(),
            $form->getWidget(self::MODALBOX_TICKER)->majixCloseBox(),
        ];

        /* @var $match \tx_cfcleague_models_Match */
        $match = tx_rnbase::makeInstance(Match::class, $matchNote->getProperty('game'));
        $this->ensureScore($matchNote, $match, $form);

        return $ret;
    }

    /**
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function cbDeleteMatchNote($params, $form)
    {
        /* @var $matchNote \tx_cfcleague_models_MatchNote */
        $matchNote = tx_rnbase::makeInstance(MatchNote::class, $params['uid']);
        if (!$matchNote->isValid()) {
            return [];
        }

        /* @var $match \tx_cfcleague_models_Match */
        $match = tx_rnbase::makeInstance(Match::class, $matchNote->getProperty('game'));
        $matchNoteClone = tx_rnbase::makeInstance(MatchNote::class, $matchNote->getProperty());

        // FIXME: es gibt kein DELETE im Repo.
        /* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
        $repo = tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
        $repo->handleDelete($matchNote, '', 1);
//        $this->mnRepo->persist($model);

        $ret = [
            $form->getWidget('matchnotes')->majixRepaint(),
        ];

        $this->ensureScore($matchNoteClone, $match, $form);

        return $ret;
    }

    public function getMatchNoteSql($params, $form)
    {
        $uid = (int) $form->getDataHandler()->getStoredData('uid');
        $options = [
            'sqlonly' => 1,
            'where' => 'game='.$uid,
            // Wirft SQL-Fehler beim Count
            // 'orderby' => 'minute desc, extra_time desc',
        ];

        return Connection::getInstance()->doSelect('*', 'tx_cfcleague_match_notes', $options);
    }

    /**
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function validatePlayer($params, $form)
    {
        $type = $form->getWidget('type')->getValue();
        if (100 == $type || $type >= 1000) {
            // Hier ist der Spieler egal
            return true;
        }
        if (MatchNote::TYPE_CHANGEOUT == $type) {
            // Bei Auswechslungen werden zwei Spieler benötigt
            if (
                !(
                ($this->hasValue($form, 'player_home_changeout') && $this->hasValue($form, 'player_home_changein'))
                ||
                ($this->hasValue($form, 'player_guest_changeout') && $this->hasValue($form, 'player_guest_changein'))
                )) {
                return false;
            }
        } else {
            $home = $form->getWidget('player_home')->getValue();
            $guest = $form->getWidget('player_guest')->getValue();
            // Jetzt muss genau ein Spieler gesetzt sein
            if (0 != $home && 0 != $guest || 0 == $home && 0 == $guest) {
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
     * Validator für TickerType.
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return []
     */
    public function validatePlayerModal($params, $form)
    {
        $type = $form->getWidget(self::MODALBOX_TICKER.'__type')->getValue();
        if (100 == $type || 1000 == $type) {
            // Hier ist der Spieler egal
            return true;
        }
        $home = $form->getWidget(self::MODALBOX_TICKER.'__player_home')->getValue();
        $guest = $form->getWidget(self::MODALBOX_TICKER.'__player_guest')->getValue();
        // Jetzt muss genau ein Spieler gesetzt sein
        if (0 != $home && 0 != $guest || 0 == $home && 0 == $guest) {
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
        $paused = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(Watch::FIELD_TICKER_PAUSED, 'flw24');
        $match->setProperty(Watch::FIELD_TICKER_PAUSED, (int) $paused);

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
     * Liefert die Tickertypen ohne Auswechslung.
     *
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     *
     * @return []
     */
    public function getTickerTypes($params, \tx_mkforms_forms_IForm $form)
    {
        $tcaTypes = $this->loadTickerTypes();
        $data = [];
        foreach ($tcaTypes as $typeDef) {
            if (!$this->isChange($typeDef[1])) {
                $data[] = [
                    'caption' => $typeDef[0],
                    'value' => $typeDef[1],
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
     *
     * @return []
     */
    public function getTickerTypesAll($params, \tx_mkforms_forms_IForm $form)
    {
        $tcaTypes = $this->loadTickerTypes();
        $data = [];
        foreach ($tcaTypes as $typeDef) {
            $data[] = [
                'caption' => $typeDef[0],
                'value' => $typeDef[1],
            ];
        }

        return $data;
    }

    /**
     * Liefert alle Spieler von Aufstellung und Bank.
     *
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     *
     * @return number[][]|string[][]
     */
    public function getPlayers($params, \tx_mkforms_forms_IForm $form)
    {
        /* @var $match \tx_cfcleague_models_Match */
        $match = $this->getCurrentMatch($form);

        $data = $this->getPlayerNames($match, $params['team'], $form);

        return $data;
    }

    /**
     * @param Match $match
     * @param string $team
     */
    protected function getPlayerNames($match, $team, \tx_mkforms_forms_IForm $form)
    {
        if (isset($this->playerNames[$team])) {
            return $this->playerNames[$team];
        }

        $profileSrv = ServiceRegistry::getProfileService();
        if ('home' == $team) {
            $players = $profileSrv->loadProfiles($match->getPlayersHome(true));
        } else {
            $players = $profileSrv->loadProfiles($match->getPlayersGuest(true));
        }

        $this->playerNames = [
            $team => [
                [
                    'value' => 0,
                    'caption' => '',
                ],
            ],
        ];
        foreach ($players as $player) {
            $this->playerNames[$team][] = [
                'caption' => $player->getName(true),
                'value' => $player->getUid(),
            ];
        }
        if (count($this->playerNames[$team]) > 1) {
            usort($this->playerNames[$team], [LineUp::class, 'sortByCaption']);

            $this->playerNames[$team][] = [
                'value' => '',
                'caption' => '',
                'custom' => 'disabled',
            ];
        }
        $this->playerNames[$team][] = [
            'value' => -1,
            'caption' => $form->getConfigurations()->getLL('label_flw24_ticker_player_unknown'),
        ];

        return $this->playerNames[$team];
    }

    protected function isChange($type)
    {
        return 81 == $type;
    }

    protected function loadTickerTypes()
    {
        $srv = ServiceRegistry::getMatchService();

        return $srv->getMatchNoteTypes4TCA();
    }

    /**
     * called if watch is started initially or after pause.
     *
     * @param \tx_mkforms_forms_IForm $form
     */
    public function onMatchStarted(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);

        $minute = 1;
        if (!($match->isRunning() || $match->isFinished())) {
            $this->createMessage($match, $minute, 'Spiel gestartet');
            $ret[] = $form->getWidget('matchnotes')->majixRepaint();
        }

        $this->ensureTickerActive($match, $form, $minute);

        return $ret;
    }

    public function onMatchHalftime(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        // Spielstand setzen
        $lastNote = $this->persistScore($match, '1');
        // Tickermeldung schreiben
        $this->createMessageHalftime($match, $lastNote);
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchHalftime2(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        // Tickermeldung schreiben
        $this->createMessage($match, 46, '2. Halbzeit läuft');
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchExtraTime(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $match->setProperty('is_extratime', 1);
        $lastTicker = $this->persistScore($match);
        // Tickermeldung schreiben
        $this->createMessageExtraTime($match, $lastTicker);
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchExtraTimeHT1(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $lastTicker = $this->persistScore($match, 'et');
        // Tickermeldung schreiben
        $this->createMessage($match, 91, 'Verlängerung 1. Halbzeit läuft');
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchExtraTimeHT(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $lastTicker = $this->persistScore($match, 'et');
        // Tickermeldung schreiben
        $this->createMessageExtraTime($match, $lastTicker, true);
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchExtraTimeHT2(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $lastTicker = $this->persistScore($match, 'et');
        // Tickermeldung schreiben
        $this->createMessage($match, 106, 'Verlängerung 2. Halbzeit läuft');
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchPenalties(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $match->setProperty('is_penalty', 1);
        $lastTicker = $this->persistScore($match, 'ap');
        // Tickermeldung schreiben
        $this->createMessagePenalties($match, $lastTicker);
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    public function onMatchFinished(\tx_mkforms_forms_IForm $form)
    {
        $ret = [];
        $match = $this->getCurrentMatch($form);
        $match->setProperty('status', Match::MATCH_STATUS_FINISHED);
        $match->setProperty('link_report', 1);
        // Zur Sicherheit den Spielstand nochmal übernehmen
        $lastTicker = $this->persistScore($match, $this->getMatchPartFinal($match));
        $this->createMessageFinished($match, $lastTicker);
        $ret[] = $form->getWidget('matchnotes')->majixRepaint();

        return $ret;
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     * @param \tx_cfcleague_models_MatchNote $lastTicker
     */
    private function createMessageHalftime($match, $lastTicker)
    {
        $extraTime = 0;
        if ($lastTicker && $lastTicker->getMinute() > 45) {
            $extraTime = ((int) $lastTicker->getProperty('extra_time')) + 1;
        }
        $this->createMessage($match, 45, 'Halbzeit', $extraTime);
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     * @param \tx_cfcleague_models_MatchNote $lastTicker
     */
    private function createMessageExtraTime($match, $lastTicker, $halftime = false)
    {
        $baseMinute = $halftime ? 105 : 90;
        $extraTime = 0;
        if ($lastTicker && $lastTicker->getMinute() > $baseMinute) {
            $extraTime = ((int) $lastTicker->getProperty('extra_time')) + 1;
        }
        $this->createMessage($match, $baseMinute, $halftime ? 'Halbzeit der Verlängerung' : 'Verlängerung', $extraTime);
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     * @param \tx_cfcleague_models_MatchNote $lastTicker
     */
    private function createMessagePenalties($match, $lastTicker)
    {
        $minute = 120;
        if ($lastTicker && $lastTicker->getMinute() > $minute) {
            $minute = $lastTicker->getMinute() + 1;
        }
        $this->createMessage($match, $minute, 'Elfmeterschießen!');
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     * @param \tx_cfcleague_models_MatchNote $lastTicker
     */
    private function createMessageFinished($match, $lastTicker)
    {
        $minute = 90;
        $extraTime = 0;
        if ($match->isExtraTime()) {
            $minute = 120;
            if ($lastTicker && $lastTicker->getMinute() > $minute) {
                $minute = $lastTicker->getMinute() + 1;
            }
        } else {
            if ($lastTicker && $lastTicker->getMinute() > $minute) {
                $extraTime = ((int) $lastTicker->getProperty('extra_time')) + 1;
            }
        }
        $this->createMessage($match, $minute, 'Das Spiel ist beendet', $extraTime);
    }

    private function createMessage($match, $minute, $comment, $extraTime = 0, $type = MatchNote::TYPE_TICKER)
    {
        $model = $this->createNewMatchNote($match, $repo);
        $model->setProperty('type', $type);
        $model->setProperty('minute', $minute);
        $model->setProperty('comment', $comment);
        if ($extraTime) {
            $model->setProperty('extra_time', $extraTime);
        }

        $this->mnRepo->persist($model);
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     * @param $repo \Tx_Cfcleague_Model_Repository_MatchNote
     *
     * @return \tx_cfcleague_models_MatchNote
     */
    protected function createNewMatchNote($match, $repo)
    {
        $record = [
            'crfeuser' => \tx_t3users_models_feuser::getCurrent()->getUid(),
            'game' => $match->getUid(),
            'pid' => $match->getProperty('pid'),
        ];

        return $repo->createNewModel($record);
    }

    /**
     * @param \tx_cfcleague_models_Match $match
     *
     * @return string
     */
    private function getMatchPartFinal($match)
    {
        $part = '2';
        if ($match->isPenalty()) {
            $part = 'ap';
        } elseif ($match->isExtraTime()) {
            $part = 'et';
        }

        return $part;
    }
}
