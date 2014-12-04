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

namespace BackBuilder\Rest\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackBuilder\Controller\Controller;
use BackBuilder\Security\Encoder\RequestSignatureEncoder;

/**
 * Test api key controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class TestController extends Controller
{
    /**
     *
     *
     * @access public
     */
    public function generateKeyAction(Request $request)
    {
        $response = new Response();

        $values = $request->request->get('generator');

        $signature = null;

        if ('POST' === $request->getMethod()) {
            $encoder = new RequestSignatureEncoder();
            $requestToBeSigned = Request::create($values['url'], $values['method']);
            $signature = $encoder->createSignature($requestToBeSigned, $values['private_key']);
        }

        return $this->render('Rest/test.html.twig', array(
            'form' => $values,
            'signature' => $signature,
        ));
    }
}
