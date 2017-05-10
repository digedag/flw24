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


/**
 * Steuerung der Spieluhr
 */
class Watch {

	/** Enthält die aktuelle Client-Zeit */
	const FIELD_TICKER_LOCALTIME = 'watch_localtime';
	/** Enthält den Zeitpunkt des Start-Klicks */
	const FIELD_TICKER_STARTTIME = 'watch_starttime';
	/** Enthält den Zeitpunkt des Pause-Klicks */
	const FIELD_TICKER_PAUSETIME = 'watch_pausetime';
	/** Enhält einen optionalen Offset */
	const FIELD_TICKER_OFFSET = 'watch_offset';
	/** Enhält den aktuellen Spielabschnitt als Spielminute 0,45,90,115 */
	const FIELD_TICKER_MATCHPART = 'watch_matchpart';

	/**
	 * Startet die Uhr, egal ob initial oder aus der Pause
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchStartClick($params, $form) {
		// Startzeit auf dem Client wird gesichert
//		$starttime = \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_STARTTIME, 'flw24');
		$matchpart = (int) \tx_t3users_util_ServiceRegistry::getFeUserService()->getSessionValue(self::FIELD_TICKER_MATCHPART, 'flw24');
		$ret = [];
		// Uhr starten
		$this->pauseOff($ret, $form);

		$ret[] = $form->getWidget('btn_watch_start')->majixDisplayNone();
		if($matchpart == 45) {
			$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayDefault();
			$ret[] = $form->getWidget('btn_watch_halftime')->majixDisplayNone();
		}
		else {
			$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayNone();
			$ret[] = $form->getWidget('btn_watch_halftime')->majixDisplayDefault();
		}
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayDefault();
		$GLOBALS['TSFE']->storeSessionData();

		return $ret;
	}

	/**
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchHalftimeClick($params, $form) {
		$halftime = 45;
		$ret = [];
		// Halbzeit einstellen
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_MATCHPART, $halftime, 'flw24');
		$ret[] = $form->getWidget(self::FIELD_TICKER_MATCHPART)->majixSetValue($halftime);
		$ret[] = $form->getWidget('watch_minute')->majixSetValue($halftime);

		// Pause ausblenden
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayNone();
		$ret[] = $form->getWidget(self::FIELD_TICKER_OFFSET)->majixSetValue('0');
		$ret[] = $form->getWidget('watch')->majixSetHtml($halftime.':00');

		// Pause starten
		$this->pauseOn($ret, $form);
		// Zeit des Spielstarts reseten
		$localtime = $form->getWidget(self::FIELD_TICKER_LOCALTIME)->getValue();
		$ret[] = $form->getWidget(self::FIELD_TICKER_STARTTIME)->majixSetValue($localtime);
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_STARTTIME, $localtime, 'flw24');


		// Start 2. Halbzeit einblenden
		$ret[] = $form->getWidget('btn_watch_halftime')->majixDisplayNone();
		$ret[] = $form->getWidget('btn_watch_secondht')->majixDisplayDefault();

		$GLOBALS['TSFE']->storeSessionData();

		return $ret;
	}
	/**
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchSecondHTClick($params, $form) {
		$ret = [];
		// Pause einblenden
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayDefault();
		// Uhr starten
		$this->pauseOff($ret, $form);

		// Stop einblenden
		$ret[] = $form->getWidget('btn_watch_halftime')->majixDisplayNone();
		$ret[] = $form->getWidget('btn_watch_secondht')->majixDisplayNone();
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayDefault();
		$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayDefault();

		$GLOBALS['TSFE']->storeSessionData();

		return $ret;
	}

	/**
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchStopClick($params, $form) {
		$ret = [];
		// Ausschalten alles auf 0 setzen
		\tx_t3users_util_ServiceRegistry::getFeUserService()->removeSessionValue(self::FIELD_TICKER_STARTTIME, 'flw24');
		\tx_t3users_util_ServiceRegistry::getFeUserService()->removeSessionValue(self::FIELD_TICKER_PAUSETIME, 'flw24');
		\tx_t3users_util_ServiceRegistry::getFeUserService()->removeSessionValue(self::FIELD_TICKER_MATCHPART, 'flw24');
		$ret[] = $form->getWidget('btn_watch_start')->majixDisplayDefault();
		$ret[] = $form->getWidget('btn_watch_stop')->majixDisplayNone();
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayNone();
		$ret[] = $form->getWidget(self::FIELD_TICKER_OFFSET)->majixSetValue('0');
		$ret[] = $form->getWidget(self::FIELD_TICKER_MATCHPART)->majixSetValue('0');
		$ret[] = $form->getWidget('watch_minute')->majixSetValue('0');
		if($form->getWidget('watch') instanceof \tx_mkforms_widgets_box_Main) {
			$ret[] = $form->getWidget('watch')->majixSetHtml('00:00');
		}
		else {
			$ret[] = $form->getWidget('watch')->majixSetValue('');
		}
		$GLOBALS['TSFE']->storeSessionData();

		$starttime = 0;
		$ret[] = $form->getWidget(self::FIELD_TICKER_STARTTIME)->majixSetValue($starttime);
		$ret[] = $form->getWidget(self::FIELD_TICKER_PAUSETIME)->majixSetValue($starttime);

		$GLOBALS['TSFE']->storeSessionData();
		return $ret;
	}

	/**
	 *
	 * @param array $params
	 * @param \tx_mkforms_forms_Base $form
	 * @return []
	 */
	public function cbWatchPauseClick($params, $form) {
		$ret = [];
		$ret[] = $form->getWidget('btn_watch_start')->majixDisplayDefault();
		$ret[] = $form->getWidget('btn_watch_pause')->majixDisplayNone();
		$this->pauseOn($ret, $form);
		$GLOBALS['TSFE']->storeSessionData();

		return $ret;
	}
	private function pauseOn(&$ret, $form) {
		// Zeitpunkt der Pause merken
		$localtime = $form->getWidget(self::FIELD_TICKER_LOCALTIME)->getValue();
		$ret[] = $form->getWidget(self::FIELD_TICKER_PAUSETIME)->majixSetValue($localtime);
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_PAUSETIME, $localtime, 'flw24');
	}
	private function pauseOff(&$ret, $form) {
		// Damit geht es wieder bei 0 los. Es muss aber bei der letzten Zeit weiterlaufen.
		// Jetzt die Differenz zur aktuellen Zeit ermitteln
		$starttime = $form->getWidget(self::FIELD_TICKER_STARTTIME)->getValue();
		$localtime = $form->getWidget(self::FIELD_TICKER_LOCALTIME)->getValue();
		$pausetime = $form->getWidget(self::FIELD_TICKER_PAUSETIME)->getValue();
		$starttime = $starttime + $localtime - $pausetime;
		$ret[] = $form->getWidget(self::FIELD_TICKER_STARTTIME)->majixSetValue($starttime);
		$ret[] = $form->getWidget(self::FIELD_TICKER_PAUSETIME)->majixSetValue(0);

		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_STARTTIME, $starttime, 'flw24');
		\tx_t3users_util_ServiceRegistry::getFeUserService()->setSessionValue(self::FIELD_TICKER_PAUSETIME, 0, 'flw24');
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
	 * Halbzeit wurde geändert und muss gespeichert werden. Derzeit nicht verwendet.
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

}
