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

		return array(
			$form->getWidget('box_base')->majixClearValue(),
			$form->getWidget('matchnotes')->majixRepaint(),
		);
	}

	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbUpdateMatchNote($params, $form) {

		$matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
		if(!$matchNote->isValid()) {
			return [$form->majixDebug('Sorry, update failed')];
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

		return [
			$form->getWidget('matchnotes')->majixRepaint(),
		];
	}
	/**
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbDeleteMatchNote($params, $form) {
		$matchNote = \tx_rnbase::makeInstance('tx_cfcleague_models_MatchNote', $params['uid']);
		if(!$matchNote->isValid()) {
			return [$form->majixDebug('Sorry, update failed')];
		}
		/* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
		$repo = \tx_rnbase::makeInstance('Tx_Cfcleague_Model_Repository_MatchNote');
		$repo->handleDelete($matchNote);
		return [
			$form->getWidget('matchnotes')->majixRepaint(),
		];
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

	public function getMatchNoteSql($params, $form) {
		$options = [
			'sqlonly' => 1,
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
		if($type == 100) {
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
