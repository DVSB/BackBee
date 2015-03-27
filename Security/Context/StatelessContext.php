<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Security\Context;

use BackBee\Security\Listeners\ContextListener;

/**
 * Description of AnonymousContext.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class StatelessContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (!array_key_exists('stateless', $config) || false === $config['stateless']) {
            $contextKey = array_key_exists('context', $config) ? $config['context'] : $config['firewall_name'];
            $listeners[] = new ContextListener($this->_context, $this->_context->getUserProviders(), $contextKey, $this->_context->getLogger(), $this->_context->getDispatcher());
        }

        return $listeners;
    }
}
