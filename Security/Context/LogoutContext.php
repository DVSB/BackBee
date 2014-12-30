<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

use Symfony\Component\Security\Http\HttpUtils;

use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\Logout\LogoutSuccessHandler;

/**
 * Description of AnonymousContext
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class LogoutContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        if (array_key_exists('logout', $config)) {
            if (array_key_exists('handlers', $config['logout']) && is_array($handlers = $config['logout']['handlers'])) {
                $this->initLogoutListener();
                $this->setHandlers($handlers);
            }
        }

        return array();
    }

    public function initLogoutListener()
    {
        if (null === $this->_context->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $this->_context->setLogoutListener(new LogoutListener($this->_context, $httpUtils, new LogoutSuccessHandler($httpUtils)));
        }
    }

    public function setHandlers($handlers)
    {
        foreach ($handlers as $handler) {
            $this->_context->getLogoutListener()->addHandler(new $handler());
        }
    }
}
