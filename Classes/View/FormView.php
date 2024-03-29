<?php

namespace System25\Flw24\View;

use Sys25\RnBase\Frontend\Marker\SimpleMarker;
use System25\T3sports\Frontend\Marker\MatchMarker;
use System25\T3sports\Model\Fixture;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2023 Rene Nitzsche (rene@system25.de)
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

class FormView extends \tx_mkforms_view_Form
{
    public function createOutput($template, &$viewData, &$configurations, &$formatter, $redirectToLogin = false)
    {
        $template = parent::createOutput($template, $viewData, $configurations, $formatter, $redirectToLogin);
        $items = $viewData->offsetGet('items');
        $confId = $this->getController()->getConfId();

        $out = $this->parseItems($items, $confId, $formatter, $template, $viewData);

        return $out;
    }

    protected function parseItems($items, $confId, $formatter, $template, $viewdata)
    {
        if (is_array($items)) {
            foreach ($items as $key => $item) {
                $markerClass = SimpleMarker::class;
                if ($item instanceof Fixture) {
                    $markerClass = MatchMarker::class;
                }
                $marker = tx_rnbase::makeInstance($markerClass);
                $template = $marker->parseTemplate($template, $item, $formatter, $confId.$key.'.', strtoupper($key));
            }
        }

        return $template;
    }
}
