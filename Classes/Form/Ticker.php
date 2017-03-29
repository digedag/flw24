<?php
namespace System25\Flw24\Form;

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



class Ticker {
	private $playerNames = [];
	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbTickerSubmitClick($params, $form) {
		\tx_rnbase::load('tx_t3users_models_feuser');
		// Die Match-UID wird im DataHandler persistiert
		$uid = $form->getDataHandler()->getStoredData('uid');
		$record = [
			'crfeuser' => \tx_t3users_models_feuser::getCurrent()->getUid(),
			'game' => $uid,
		];
		$fields = ['minute', 'extra_time', 'type', 'player_home', 'player_guest', 'comment'];
		foreach ($fields As $fieldName) {
			$record[$fieldName] = $form->getWidget($fieldName)->getValue();
		}

		/* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
		$repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
		$model = $repo->createNewModel($record);
		$repo->persist($model);
		$ret = array(
				$form->getWidget('box_base')->majixClearValue(),
				$form->getWidget('box_players')->majixDisplayNone(),
				$form->getWidget('matchnotes')->majixRepaint(),
		);

		// Spielticker ggf. aktivieren, wenn das Spiel nicht in Vergangenheit liegt
		/* @var $match \tx_cfcleague_models_Match */
		$match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);
		if ($this->ensureTickerActive($match, $form, $model->getMinute()) ) {
			$ret[] = $form->getWidget('link_ticker')->majixSetValue($match->getProperty('link_ticker'));
			$ret[] = $form->getWidget('status')->majixSetValue($match->getProperty('status'));
		}
		if ($this->ensureScore($model, $match, $form) ) {
			$ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
			$ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
		}
		if ($this->ensureStatus($model, $match, $form) ) {
			$ret[] = $form->getWidget('status')->majixSetValue($match->getStatus());
		}

		return $ret;
	}

	/**
	 *
	 * @param tx_cfcleague_models_MatchNote $ticker
	 * @param tx_cfcleague_models_Match $match
	 * @param \tx_mkforms_forms_Base $form
	 */
	protected function ensureStatus($ticker, $match, \tx_mkforms_forms_Base $form) {
		if($ticker->getType() != 1000) {
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
	protected function ensureScore($ticker, $match, \tx_mkforms_forms_Base $form) {
		if(!$ticker->isGoal()) {
			return false;
		}

		\tx_rnbase::load('tx_cfcleaguefe_util_MatchTicker');
		$tickerArr = \tx_cfcleaguefe_util_MatchTicker::getTicker4Match($match);
		// im letzten Eintrag steht der aktuelle Spielstand
		$lastTicker = end($tickerArr);
		if($lastTicker) {
			$match->setProperty('goals_home_2', $lastTicker->getProperty('goals_home'));
			$match->setProperty('goals_guest_2', $lastTicker->getProperty('goals_guest'));
			\tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
		}

		return true;
	}
	/**
	 * Spiel- und Tickerstatus automatisch setzen
	 * @param tx_cfcleague_models_Match $match
	 */
	protected function ensureTickerActive($match, \tx_mkforms_forms_Base $form, $minute) {
		\tx_rnbase::load('tx_rnbase_util_Dates');
		if(($match->isTicker() && $match->isRunning()) || $match->isFinished()) {
			return false;
		}
		// Liegt das Spiel in der Vergangenheit
		$kickoff = \tx_rnbase_util_Dates::date_tstamp2mysql($match->getDate());
		$kickoff = \tx_rnbase_util_Dates::date_mysql2int($match->getDate());
		if (\tx_rnbase_util_Dates::getTodayDateString() > $kickoff) {
			return false;
		}
		$match->setProperty('link_ticker', 1);
		if(((int)$minute) > 0) {
			$match->setProperty('status', \tx_cfcleague_models_Match::MATCH_STATUS_RUNNING);
		}
		\tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
		return true;
	}
	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbUpdateMatchNote($params, $form) {

		$matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
		if(!$matchNote->isValid()) {
			return [];
		}
		$prefix = 'matchnotes__';
		$fields = ['minute', 'extra_time', 'type', 'player_home', 'player_guest', 'comment'];
		foreach ($fields As $fieldName) {
			if(isset($params[$prefix.$fieldName])) {
				$matchNote->setProperty($fieldName, $params[$prefix.$fieldName]);
			}
		}

		/* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
		$repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
		$repo->persist($matchNote);

		$ret = [
				$form->getWidget('matchnotes')->majixRepaint(),
		];

		/* @var $match \tx_cfcleague_models_Match */
		$match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchNote->getProperty('game'));
		if ($this->ensureScore($matchNote, $match, $form) ) {
			$ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
			$ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
		}

		return $ret;
	}
	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbDeleteMatchNote($params, $form) {
		/* @var $matchNote \tx_cfcleague_models_MatchNote */
		$matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
		if(!$matchNote->isValid()) {
			return [];
		}

		/* @var $match \tx_cfcleague_models_Match */
		$match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $matchNote->getProperty('game'));
		$matchNoteClone = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $matchNote->getProperty());

		/* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
		$repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
		$repo->handleDelete($matchNote);

		$ret = [
				$form->getWidget('matchnotes')->majixRepaint(),
		];

		if ($this->ensureScore($matchNoteClone, $match, $form) ) {
			$ret[] = $form->getWidget('goals_home_2')->majixSetValue($match->getGoalsHome(2));
			$ret[] = $form->getWidget('goals_guest_2')->majixSetValue($match->getGoalsGuest(2));
		}
		return $ret;
	}

	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbMatchSubmitClick($params, $form) {
		$uid = $form->getDataHandler()->getStoredData('uid');
		$match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

		$fields = ['goals_home_2', 'goals_home_1', 'goals_guest_2', 'goals_guest_1', 'visitors', 'status', 'link_ticker'];
		foreach ($fields As $fieldName) {
			$match->setProperty($fieldName, $form->getWidget($fieldName)->getValue());
		}

		\tx_cfcleague_util_ServiceRegistry::getMatchService()->persist($match);
		return array(
		);
	}
	/** Enthält die aktuelle Client-Zeit */
	const FIELD_TICKER_LOCALTIME = 'watch_localtime';
	/** Enthält den Zeitpunkt des Start-Klicks */
	const FIELD_TICKER_STARTTIME = 'watch_starttime';
	/** Enhält einen optionalen Offset */
	const FIELD_TICKER_OFFSET = 'watch_offset';
	/** Enhält den aktuellen Spielabschnitt */
	const FIELD_TICKER_MATCHPART = 'watch_matchpart';
	/**
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchToggleClick($params, $form) {
		// Startzeit auf dem Client wird gesichert
		$starttime = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_STARTTIME, 'flw24');
		$ret = [];
		if($starttime) {
			// Ausschalten
			$starttime = 0;
			\tx_t3users_util_ServiceRegistry::getFeUserService()->removeSessionValue(self::FIELD_TICKER_STARTTIME, 'flw24');
			$ret[] = $form->getWidget('btn_watch_start')->majixDisplayDefault();
			$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayNone();
			$ret[] = $form->getWidget('watch_minute')->majixSetValue('0');
			if($form->getWidget('watch') instanceof \tx_mkforms_widgets_box_Main) {
				$ret[] = $form->getWidget('watch')->majixSetHtml('');
			}
			else {
				$ret[] = $form->getWidget('watch')->majixSetValue('');
			}
		}
		else {
			$starttime = $form->getWidget(self::FIELD_TICKER_LOCALTIME)->getValue();;
			\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_STARTTIME, $starttime, 'flw24');
			$ret[] = $form->getWidget('btn_watch_start')->majixDisplayNone();
			$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayDefault();
		}
		$GLOBALS['TSFE']->storeSessionData();

		$ret[] = $form->getWidget(self::FIELD_TICKER_STARTTIME)->majixSetValue($starttime);
		return $ret;
	}

	/**
	 * Offset wurde geändert und muss gespeichert werden
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchOffset($params, $form) {
		$offset = $form->getWidget(self::FIELD_TICKER_OFFSET)->getValue();;
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_OFFSET, $offset, 'flw24');
		$GLOBALS['TSFE']->storeSessionData();
		return [];
	}
	/**
	 * Halbzeit wurde geändert und muss gespeichert werden
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchMatchPart($params, $form) {
		$offset = $form->getWidget(self::FIELD_TICKER_MATCHPART)->getValue();;
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_MATCHPART, $offset, 'flw24');
		$GLOBALS['TSFE']->storeSessionData();
		return [];
	}
	public function getMatchNoteSql($params, $form) {
		$uid = (int) $form->getDataHandler()->getStoredData('uid');
		$options = [
			'sqlonly' => 1,
			'where' => 'game='.$uid,
//			'orderby' => 'minute desc, extra_time desc',
		];


		return \Tx_Rnbase_Database_Connection::getInstance()->doSelect('*', 'tx_cfcleague_match_notes', $options);
	}

	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function validatePlayer($params, $form) {
		$home = $form->getWidget('player_home')->getValue();
		$guest = $form->getWidget('player_guest')->getValue();
		$type = $form->getWidget('type')->getValue();
		if($type == 100 || $type == 1000) {
			// Hier ist der Spieler egal
			return true;
		}
		// Jetzt muss genau ein Spieler gesetzt sein
		if($home != 0 && $guest != 0 || $home == 0 && $guest == 0) {
			return false;
//			return "LLL:EXT:flw24/Resources/Private/Language/locallang.xml:label_msg_player_not_set";
		}

		return true;
	}
	public function fillMatchForm($params, \tx_mkforms_forms_IForm $form) {
		$match = $form->getParent()->getItem();

		$starttime = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_STARTTIME, 'flw24');
		if($starttime) {
			$match->setProperty(self::FIELD_TICKER_STARTTIME, $starttime);
		}
		$offset = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_OFFSET, 'flw24');
		if($offset) {
			$match->setProperty(self::FIELD_TICKER_OFFSET, $offset);
		}
		$offset = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_MATCHPART, 'flw24');
		if($offset) {
			$match->setProperty(self::FIELD_TICKER_MATCHPART, $offset);
		}


		return $match->getProperty();
	}
	/**
	 * Liefert die Tickertypen ohne Ein- und Auswechslung
	 * @param array $params
	 * @param \tx_mkforms_forms_IForm $form
	 * @return []
	 */
	public function getTickerTypes($params, \tx_mkforms_forms_IForm $form) {
		$tcaTypes = $this->loadTickerTypes();
		$data = [];
		foreach($tcaTypes As $typeDef) {
			if(!$this->isChange($typeDef[1]))
				$data[] = ['caption' => $typeDef[0], 'value' => $typeDef[1] ];
		}
		return $data;
	}
	public function getPlayers($params, \tx_mkforms_forms_IForm $form) {
		/* @var $match \tx_cfcleague_models_Match */
		$uid = $form->getDataHandler()->getStoredData('uid');
		$match = \tx_rnbase::makeInstance('tx_cfcleague_models_Match', $uid);

		$data = $this->getPlayerNames($match, $params['team']);
		return $data;
	}


	/**
	 *
	 * @param tx_cfcleague_models_Match $match
	 * @param string $team
	 */
	protected function getPlayerNames($match, $team) {
		if(isset($this->playerNames[$team])) {
			return $this->playerNames[$team];
		}

		$profileSrv = \tx_cfcleague_util_ServiceRegistry::getProfileService();
		if($team == 'home') {
			$players = $profileSrv->loadProfiles($match->getPlayersHome(true));
		}
		else {
			$players = $profileSrv->loadProfiles($match->getPlayersGuest(true));
		}

		$this->playerNames = [ $team => [['value' => 0 , 'caption'=>'']] ];
		foreach ($players As $player) {
			$this->playerNames[$team][] = [
				'caption'=>$player->getName(true),
				'value' => $player->getUid(),
			];
		}
		$this->playerNames[$team][] = ['value' => -1 , 'caption'=>'###LABEL_FLW24_TICKER_PLAYER_UNKNOWN###'];

		return $this->playerNames[$team];
	}

	protected function isChange($type) {
		return $type == 80 || $type == 81;
	}
	protected function loadTickerTypes() {
		$srv = \tx_cfcleague_util_ServiceRegistry::getMatchService();
		return $srv->getMatchNoteTypes4TCA();
	}
}
