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
class Site extends AbstractServiceLocal
{

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorList()
    {
        $sites = array();
        $em = $this->bbapp->getEntityManager();

        $q = $em->getRepository('\BackBuilder\Site\Site')->createQueryBuilder('s')
                ->orderBy('s._label', 'ASC')
                ->getQuery();

        foreach ($q->getResult() as $site) {
            $sites[$site->getUid()] = $site->getLabel();
        }

        return $sites;
    }

}
