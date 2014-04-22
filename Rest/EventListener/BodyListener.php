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

use BackBuilder\Rest\Encoder\IEncoderProvider;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Body listener/encoder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class BodyListener
{
    /**
     * @var IEncoderProvider
     */
    private $encoderProvider;

    /**
     * @var boolean
     */
    private $throwExceptionOnUnsupportedContentType;
    
    /**
     * @var string
     */
    private $listenOnRoute;

    /**
     * Constructor.
     *
     * @param IEncoderProvider $encoderProvider Provider for encoders
     * @param boolean $throwExceptionOnUnsupportedContentType
     * @param string $listenOnRoute
     */
    public function __construct(IEncoderProvider $encoderProvider, $throwExceptionOnUnsupportedContentType = false, $listenOnRoute = '/')
    {
        $this->encoderProvider = $encoderProvider;
        $this->throwExceptionOnUnsupportedContentType = $throwExceptionOnUnsupportedContentType;
        $this->listenOnRoute = $listenOnRoute;
    }

    /**
     * Core request handler
     *
     * @param GetResponseEvent $event The event
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        
        // skip if route does not match
        if(0 !== strpos($request->getPathInfo(), $this->listenOnRoute) ) {
            return;
        }
        
        if (!count($request->request->all())
            && in_array($request->getMethod(), array('POST', 'PUT', 'PATCH', 'DELETE'))
        ) {
            $contentType = $request->headers->get('Content-Type');

            $format = null === $contentType
                ? $request->getRequestFormat()
                : $request->getFormat($contentType);

            if (!$this->encoderProvider->supports($format)) {
                if ($this->throwExceptionOnUnsupportedContentType) {
                    throw new UnsupportedMediaTypeHttpException("Request body format '$format' not supported");
                }

                return;
            }

            $decoder = $this->encoderProvider->getEncoder($format);
            $content = $request->getContent();

            if (!empty($content)) {
                $data = $decoder->decode($content, $format);

                if (is_array($data)) {
                    $request->request = new ParameterBag($data);
                } else {
                    throw new BadRequestHttpException('Invalid ' . $format . ' message received');
                }
            }
        }
    }
}
