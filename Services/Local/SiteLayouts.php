<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal;

/**
 * RPC services for User management
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class SiteLayouts extends AbstractServiceLocal
{

    /**
     * @exposed(secured=true)
     */
    public function getSiteLayouts()
    {
        $sitesLayoutContainer = array();
        /* layout 1 */
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_".rand());
        $layoutItem->templateTitle = "This is my  first layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        /* layout 2 */
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_".rand());
        $layoutItem->templateTitle = "This is my second layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        /* layout 3 */
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_".rand());
        $layoutItem->templateTitle = "This is my theùird layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        /* layout 4 */
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_".rand());
        $layoutItem->templateTitle = "This is my fourth layout";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        /* layout 5 */
        $layoutItem = new \stdClass();
        $layoutItem->uid = uniqid("layout_".rand());
        $layoutItem->templateTitle = "This is é my fith";
        $layoutItem->templateLayouts = array();
        $layoutItem->picPath = "";
        $sitesLayoutContainer[] = $layoutItem;
        return $sitesLayoutContainer;
    }

    /**
     * @exposed(secured=true)
     */
    public function getCompleteName($firstname = "Harris", $lastname = "Baptiste")
    {
        return "completeName : " . $firstname . " " . $lastname;
    }

}

?>
