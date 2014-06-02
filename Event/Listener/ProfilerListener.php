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

namespace BackBuilder\Event\Listener;


use Symfony\Component\HttpKernel\EventListener\ProfilerListener as BaseProfilerListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Listener to metadata events
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ProfilerListener extends BaseProfilerListener
{
    protected $enabled = false;

    /**
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
    
    /**
     *
     * @inheritDoc
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if(false === $this->enabled) {
            return;
        }
        
        parent::onKernelException($event);
    }

    /**
     * 
     * @inheritDoc
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if(false === $this->enabled) {
            return;
        }
        
        parent::onKernelRequest($event);
    }

    /**
     * Handles the onKernelResponse event.
     *
     * @inheritDoc
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if(false === $this->enabled) {
            return;
        }
        
        parent::onKernelResponse($event);
    }
}
