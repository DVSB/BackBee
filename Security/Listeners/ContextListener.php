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

namespace BackBee\Security\Listeners;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ContextListener as sfContextListener;

/**
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Listeners
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ContextListener extends sfContextListener
{
    /**
     * Initiate session if not available then reads the SecurityContext from it.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $application = $event->getKernel()->getApplication();
        if (null !== $application && false === $request->hasSession()) {
            // Don't need to check if container has service with id `bb_session` cause we declared it as synthetic
            if (null === $application->getContainer()->get('bb_session')) {
                $application->getContainer()->set('bb_session', $application->getSession());
            }

            $application->getContainer()->get('bb_session')->start();
            $application->info("Session started");

            $event->getRequest()->setSession($application->getContainer()->get('bb_session'));
        }

        parent::handle($event);
    }
}
