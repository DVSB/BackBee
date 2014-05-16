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

namespace BackBuilder\Profiler\EventListener;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Profiler Toolbar listener
 *
 * @category    BackBuilder
 * @package     BackBuilder\Profiler
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ToolbarListener
{
    private $enabled = false;
    
    
    public function __construct($enabled = false) 
    {
        $this->enabled = $enabled;
    }
    
    /**
     * @param Request $request
     * @return boolean - true if the listener should be enabled for the $request
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    
    
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if(false === $this->isEnabled()) {
            return;
        }
        
        $response = $event->getResponse();
        $request = $event->getRequest();

        // do not capture redirects or modify XML HTTP Requests
        if ($request->isXmlHttpRequest()) {
            return;
        }
        
        if ($response->isRedirection()
            || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
            || 'html' !== $request->getRequestFormat()
        ) {
            return;
        }

        $profiler = $event->getKernel()->getApplication()->getContainer()->get('profiler');
        
        $profile = $profiler->collect($request, $response, null);
        
        $this->injectToolbar($response, $profiler);
    }
    
    
    /**
     * Injects the web debug toolbar into the given Response.
     *
     * @param Response $response A Response instance
     */
    protected function injectToolbar(Response $response, $profiler)
    {
        var_dump($profiler);exit;
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $toolbar = "\n".str_replace("\n", '', $this->twig->render(
                'BackBuilder/Resources/Profiler/panel.html.twig',
                array(
                    'profiler' => $profiler
                )
            ))."\n";
            
            $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
            $response->setContent($content);
        }
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse', -128),
        );
    }
}