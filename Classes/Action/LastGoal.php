<?php

namespace System25\Flw24\Action;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Filter\BaseFilter;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Frontend\View\Marker\ListView;
use System25\T3sports\Utility\ServiceRegistry;

/**
 * *************************************************************
 * Copyright notice.
 *
 * (c) 2018-2022 Rene Nitzsche (rene@system25.de)
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

/**
 * Show last tickered goals.
 */
class LastGoal extends AbstractAction
{
    /**
     * handle request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    public function handleRequest(RequestInterface $request)
    {
        $filter = BaseFilter::createFilter($request, $this->getConfId().'filter.');

        $fields = $options = [];
        $filter->init($fields, $options);
        $matchSrv = ServiceRegistry::getMatchService();
        $notes = $matchSrv->searchMatchNotes($fields, $options);
//        $items = $this->buildItems($notes->toArray());
        $items = $notes->toArray();

        $request->getViewContext()->offsetSet('items', $items);

        return '';
    }

    /**
     * @param array[\tx_cfcleague_models_MatchNote] $notes
     *
     * @return array[\tx_cfcleaguefe_models_match_note]
     */
    protected function buildItems(array $notes)
    {
        $items = [];
        foreach ($notes as $note) {
            // Fixme: Die MatchNote kennt das Spiel nicht mehr.
            $item = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_match_note', $note->getProperty());
            $matchUid = $note->getProperty('game');
            if ($matchUid) {
                $match = \tx_rnbase::makeInstance('tx_cfcleaguefe_models_match', $matchUid);
                $item->setMatch($match);
            }
            $items[] = $item;
        }

        return $items;
    }

    public function getTemplateName()
    {
        return 'lastgoal';
    }

    public function getViewClassName()
    {
        return ListView::class;
    }
}
