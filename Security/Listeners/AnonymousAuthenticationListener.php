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

namespace BackBuilder\Security\Listeners;

use BackBuilder\Security\Token\AnonymousToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\Security\Core\SecurityContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Listeners
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class AnonymousAuthenticationListener
{

    private $context;
    private $key;
    private $logger;

    public function __construct(SecurityContextInterface $context, $key, LoggerInterface $logger = null)
    {
        $this->context = $context;
        $this->key = $key;
        $this->logger = $logger;
    }

    /**
     * Handles anonymous authentication.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->context->getToken()) {
            return;
        }


        $this->context->setToken(new AnonymousToken($this->key, 'anon.', array()));

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Populated SecurityContext with an anonymous Token'));
        }
    }

}