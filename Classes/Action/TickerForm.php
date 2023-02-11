<?php

namespace System25\Flw24\Action;

use Sys25\RnBase\Domain\Repository\FeUserRepository;
use System25\Flw24\Utility\Access;
use System25\Flw24\Utility\Errors;
use System25\Flw24\View\FormView;
use System25\T3sports\Model\Fixture;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2022 Rene Nitzsche (rene@system25.de)
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

class TickerForm extends \tx_mkforms_action_FormBase
{
    private $item;

    /**
     * handle request.
     *
     * @param \arrayobject $parameters
     * @param \Sys25\RnBase\Configuration\ConfigurationInterface $configurations
     * @param \arrayobject $viewData
     *
     * @return string
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $matchId = intval($parameters->offsetGet('matchId'));
        if (0 == $matchId) {
            return 'No matchId found!';
        }
        $feUserRepo = new FeUserRepository();

        $feuser = $feUserRepo->getCurrent();
        if (!$feuser || !$feuser->isValid()) {
            throw new \Exception('Login please!', Errors::CODE_NOT_LOGGED_IN);
        }
        // Ist der Zugriff erlaubt?
        if (!Access::isTickerAllowed($feuser, $matchId)) {
            throw new \Exception('You are not allowed to ticker this match!', Errors::CODE_NOT_ALLOWED);
        }

        // Das Spiel laden
        $item = tx_rnbase::makeInstance(Fixture::class, $matchId);
        $viewData->offsetSet('item', $item);
        //		$matchReport = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_matchreport', $matchId, $configurations);
        //		$viewData->offsetSet('matchReport', $matchReport); // Den Spielreport für den View bereitstellen

        $this->item = $item;

        $items = [];
        $items['match'] = $item;
        $viewData->offsetSet('items', $items);
        $data = ['item' => $item->getProperty()];
        $viewData->offsetSet('formData', $data);

        return parent::handleRequest($parameters, $configurations, $viewData);
    }

    /**
     * @return Fixture
     */
    public function getItem()
    {
        return $this->item;
    }

    public function getConfId()
    {
        return $this->getTemplateName().'.';
    }

    public function getTemplateName()
    {
        return 'tickerform';
    }

    public function getViewClassName()
    {
        return FormView::class;
    }
}
