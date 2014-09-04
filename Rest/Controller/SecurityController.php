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

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Validator\ConstraintViolationList;

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

use BackBuilder\Rest\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

use BackBuilder\Security\Token\BBUserToken,
    BackBuilder\Security\Exception\SecurityException;

use BackBuilder\Security\Token\AnonymousToken;

use BackBuilder\Rest\Exception\ValidationException;

/**
 * Auth Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class SecurityController extends ARestController 
{
   
    /**
     * Authenticate against a specific firewall
     * 
     * Note: request attributes as well as the request format depend on the 
     * specific implementation of the firewall and its provider
     * 
     * 
     * @Rest\RequestParam(name = "firewall", description="Firewall to authenticate against", requirements = {
     *  @Assert\Choice(choices = {"bb_area"}, message="The requested firewall is invalid"), 
     * })
     * 
     */
    public function firewallAuthenticateAction($firewall, Request $request) 
    {
        $contexts = $this->getSecurityContextConfig($firewall);
        
        if(0 === count($contexts)) {
            $response = new Response();
            $response->setStatusCode(400, sprintf('No supported security contexts found for firewall: %s', $firewall));
            return $response;
        }
        
        $securityContext = $this->_application->getSecurityContext();
        
        if(null === $securityContext) {
            $response = new Response();
            $response->setStatusCode(400, sprintf('Firewall not configured: %s', $firewall));
            return $response;
        }
        
        $response = new JsonResponse();
        
        if(in_array('bb_auth', $contexts)) {
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

            $response->setContent(json_encode([
                'nonce' => $nonce,
                'user' => [
                    'id' => $tokenAuthenticated->getUser()->getId()
                ]
            ]));
            
        }
        
        return $response;
    }
    
    /**
     * 
     * @param type $firewall
     * @return array
     */
    private function getSecurityContextConfig($firewall)
    {
        $securityConfig = $this->getApplication()->getConfig()->getSection('security');
        
        if(!isset($securityConfig['firewalls'][$firewall])) {
            return null;
        }
        
        $firewallConfig = $securityConfig['firewalls'][$firewall];
        
        $allowedContexts = ['bb_auth'];
        $contexts = array_intersect(array_keys($firewallConfig), $allowedContexts);
        
        return $contexts;
    }
 
    /**
     * 
     */
    public function deleteSessionAction(Request $request)
    {
        try {
            if(false === $this->isGranted('IS_AUTHENTICATED_FULLY') ){
                return Response::create()->setStatusCode(401, "Session doesn't exist");
            }
        } catch(AuthenticationCredentialsNotFoundException $e) {
            return Response::create()->setStatusCode(401, "Session doesn't exist");
        }
        
        $this->getContainer()->get('security.context')->setToken(new AnonymousToken(uniqid(), 'anon.', []));
        $request->getSession()->invalidate();
        
        return new Response('', 204);
    }
    
}