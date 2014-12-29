<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Renderer\Helper;

/**
 * @category    BackBee
 * @package     BackBee\Renderer
 * @subpackage  Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class breadcrumb extends AHelper
{
    public function __invoke()
    {
        $application = $this->_renderer->getApplication();
        $ancestors = array();
        if (null !== $application) {
            $em = $application->getEntityManager();
            if (null !== $current = $this->_renderer->getCurrentPage()) {
                $ancestors = $em->getRepository('BackBee\NestedNode\Page')->getAncestors($current);
            } else {
                $ancestors = array($this->_renderer->getCurrentRoot());
            }
        }

        return $ancestors;
    }
}
