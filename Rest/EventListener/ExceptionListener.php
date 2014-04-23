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

namespace BackBuilder\Rest\EventListener;


use BackBuilder\Event\Listener\APathEnabledListener;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;


/**
 * Body listener/encoder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ExceptionListener extends APathEnabledListener
{
    /**
     * @var array
     */
    private $mapping;

    
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if(!$this->isEnabled($event->getRequest())) {
            return;
        }
        
        $exception = $event->getException();
        
        $exceptionClass = get_class($exception);
        
        if(isset($this->mapping[$exceptionClass])) {
            $code = isset($this->mapping[$exceptionClass]['code']) ? $this->mapping[$exceptionClass]['code'] : 500;
            $message = isset($this->mapping[$exceptionClass]['message']) ? $this->mapping[$exceptionClass]['message'] : Response::$statusTexts[$code];
            
            if(!$event->getResponse()) {
                $event->setResponse(new Response());
            }

            $event->getResponse()->setStatusCode($code, $message);
        }
    }
}
