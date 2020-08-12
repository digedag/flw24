<?php
namespace System25\Flw24\Action;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2018 Rene Nitzsche (rene@system25.de)
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
 * Show last tickered goals
 */
class LastGoal extends \tx_rnbase_action_BaseIOC
{

    /**
     * handle request
     *
     * @param \ArrayObject $parameters
     * @param \Tx_Rnbase_Configuration_ProcessorInterface $configurations
     * @param \ArrayObject $viewData
     * @return string
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {

        $filter = \tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewData, $this->getConfId().'filter.');

        $fields = $options = [];
        $filter->init($fields, $options);
        /* @var $repo \Tx_Cfcleague_Model_Repository_MatchNote */
        $matchSrv = \tx_cfcleague_util_ServiceRegistry::getMatchService();
        $notes = $matchSrv->searchMatchNotes($fields, $options);
        $items = $this->buildItems($notes);

        $viewData->offsetSet('items', $items);

        return '';
    }

    /**
     *
     * @param array[\tx_cfcleague_models_MatchNote] $notes
     * @return array[\tx_cfcleaguefe_models_match_note]
     */
    protected function buildItems(array $notes)
    {
        $items = [];
        foreach ($notes as $note) {
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
    public function getConfId()
    {
        return $this->getTemplateName() . '.';
    }

    public function getTemplateName()
    {
        return 'lastgoal';
    }

    public function getViewClassName()
    {
        return 'tx_rnbase_view_List';
    }
}
