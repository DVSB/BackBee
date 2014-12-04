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

namespace BackBuilder\Security\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Default logout success handler will redirect users to a configured path.
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Logout
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    protected $httpUtils;
    protected $targetUrl;

    /**
     * Class constructor
     * @param HttpUtils $httpUtils
     * @param string    $targetUrl
     */
    public function __construct(HttpUtils $httpUtils, $targetUrl = '/')
    {
        $this->httpUtils = $httpUtils;
        $this->targetUrl = $targetUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}
