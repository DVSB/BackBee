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

namespace BackBee\Rest\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Security\Token\AnonymousToken;
use BackBee\Security\Token\BBUserToken;

/**
 * Auth Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SecurityController extends AbstractRestController
{
    /**
     * @Rest\RequestParam(name="username", requirements={@Assert\NotBlank})
     * @Rest\RequestParam(name="password", requirements={@Assert\NotBlank})
     */
    public function authenticateAction(Request $request)
    {
        $created = date('Y-m-d H:i:s');
        $token = new BBUserToken();
        $token->setUser($request->request->get('username'));
        $token->setCreated($created);
        $token->setNonce(md5(uniqid('', true)));
        $token->setDigest(md5($token->getNonce().$created.md5($request->request->get('password'))));

        $tokenAuthenticated = $this->getApplication()->getSecurityContext()->getAuthenticationManager()
            ->authenticate($token)
        ;

        $this->getApplication()->getSecurityContext()->setToken($tokenAuthenticated);

        return $this->createJsonResponse(null, 201, array(
            'X-API-KEY'       => $tokenAuthenticated->getUser()->getApiKeyPublic(),
            'X-API-SIGNATURE' => $tokenAuthenticated->getNonce(),
        ));
    }

    /**
     * Authenticate against a specific firewall.
     *
     * Note: request attributes as well as the request format depend on the
     * specific implementation of the firewall and its provider
     *
     *
     * @Rest\RequestParam(name = "firewall", description="Firewall to authenticate against", requirements = {
     *  @Assert\Choice(choices = {"bb_area"}, message="The requested firewall is invalid"),
     * })
     */
    public function firewallAuthenticateAction($firewall, Request $request)
    {
        $contexts = $this->getSecurityContextConfig($firewall);

        if (0 === count($contexts)) {
            $response = new Response();
            $response->setStatusCode(400, sprintf('No supported security contexts found for firewall: %s', $firewall));

            return $response;
        }

        $securityContext = $this->getApplication()->getSecurityContext();

        if (null === $securityContext) {
            $response = new Response();
            $response->setStatusCode(400, sprintf('Firewall not configured: %s', $firewall));

            return $response;
        }

        $response = new JsonResponse();

        if (in_array('bb_auth', $contexts)) {
            $username = $request->request->get('username');
            $created = $request->request->get('created');
            $nonce = $request->request->get('nonce');
            $digest = $request->request->get('digest');

            $token = new BBUserToken();
            $token->setUser($username);
            $token->setCreated($created);
            $token->setNonce($nonce);
            $token->setDigest($digest);

            $authProvider = $securityContext->getAuthProvider('bb_auth');

            $tokenAuthenticated = $authProvider->authenticate($token);

            $response->setContent($this->formatItem([
                'nonce' => $nonce,
                'user' => [
                    'id' => $tokenAuthenticated->getUser()->getId(),
                ],
            ]), 'json');
        }

        return $response;
    }

    /**
     * @Rest\Security(expression="is_fully_authenticated()")
     */
    public function deleteSessionAction(Request $request)
    {
        if (null === $request->getSession()) {
            throw new NotFoundHttpException('Session doesn\'t exist');
        }

        $request->getSession()->invalidate();
        $this->getContainer()->get('security.context')->setToken(new AnonymousToken(uniqid(), 'anon.', []));

        return new Response('', 204);
    }

    /**
     * @param type $firewall
     *
     * @return array
     */
    private function getSecurityContextConfig($firewall)
    {
        $securityConfig = $this->getApplication()->getConfig()->getSection('security');

        if (!isset($securityConfig['firewalls'][$firewall])) {
            return;
        }

        $firewallConfig = $securityConfig['firewalls'][$firewall];

        $allowedContexts = ['bb_auth'];
        $contexts = array_intersect(array_keys($firewallConfig), $allowedContexts);

        return $contexts;
    }
}
