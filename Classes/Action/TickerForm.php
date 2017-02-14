<?php
namespace System25\Flw24\Action;

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

\tx_rnbase::load('tx_rnbase_action_BaseIOC');


class TickerForm extends \tx_rnbase_action_BaseIOC {

	/**
	 * handle request
	 *
	 * @param arrayobject $parameters
	 * @param Tx_Rnbase_Configuration_ProcessorInterface $configurations
	 * @param arrayobject $viewData
	 * @return string
	 */
	public function handleRequest(&$parameters, &$configurations, &$viewData) {
		$matchId = intval($parameters->offsetGet('matchId'));
		if($matchId == 0)
			return 'No matchId found!';
		// Das Spiel laden
		$match = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $matchId);
		$viewData->offsetSet('item', $match);
		$matchReport = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_matchreport', $matchId, $configurations);
		$viewData->offsetSet('matchReport', $matchReport); // Den Spielreport für den View bereitstellen

		return '';
	}

	public function getTemplateName() {return 'tickerform';}

	public function getViewClassName() { return 'System25\Flw24\View\TickerForm'; }
}
