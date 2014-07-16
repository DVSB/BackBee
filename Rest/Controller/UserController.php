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
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Validator\ConstraintViolationList,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent,
    Symfony\Component\Security\Http\SecurityEvents,
    Symfony\Component\HttpFoundation\JsonResponse;

use BackBuilder\Rest\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

use BackBuilder\Security\Token\UsernamePasswordToken;

/**
 * User Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class UserController extends ARestController 
{
   
    /**
     * Get all records
     * 
     * 
     * @Rest\QueryParam(name = "limit", default="100", description="Max results", requirements = {
     *  @Assert\Range(max=1000, min=1, minMessage="The value should be between 1 and 1000", maxMessage="The value should be between 1 and 1000"), 
     * })
     * 
     * @Rest\QueryParam(name = "start", default="0", description="Offset", requirements = {
     *  @Assert\Type(type="digit", message="The value should be a positive number"), 
     * })
     * 
     * 
     */
    public function getCollectionAction(Request $request, ConstraintViolationList $violations = null) 
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        
        // TODO
        
        return array();
    }
    
    /**
     * 
     * @Rest\RequestParam(name = "username", requirements = {
     *  @Assert\NotBlank(message="Username not provided")
     * })
     * 
     * @Rest\RequestParam(name = "password", requirements = {
     *  @Assert\NotBlank(message="Password not provided")
     * })
     * 
     * @Rest\RequestParam(name = "includeUserData", default=0, requirements = {
     *  @Assert\Choice(choices = {0, 1})
     * })
     * 
     * @Rest\RequestParam(name = "includePermissionsData", default=0, requirements = {
     *  @Assert\Choice(choices = {0, 1})
     * })
     * 
     */
    public function loginAction(Request $request, ConstraintViolationList $violations = null)
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        
        $authManager = $this->getContainer()->get('security.context')->getAuthenticationManager();
        /* @var $authManager \BackBuilder\Security\Authentication\AuthenticationManager */
        
        try {
            $token = new UsernamePasswordToken($request->request->get('username'), $request->request->get('password'));
            $token->setUser($request->request->get('username'), $request->request->get('password'));
            $token = $authManager->authenticate($token);
        } catch(\BackBuilder\Security\Exception\SecurityException $e) {
            // user not found or password is invalid
            $response = new Response(null);
            $response->setStatusCode(401, $e->getMessage());
            return $response;
        }
        
        if (!in_array('ROLE_ACTIVE_USER', $token->getUser()->getRoles())) {
            $response = new Response(null);
            $response->setStatusCode(403, 'Fobidden - user is not active');
            return $response;
        }

        
        $this->getContainer()->get('security.context')->setToken($token);
        $loginEvent = new InteractiveLoginEvent($request, $token);
        $this->getApplication()->getEventDispatcher()->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
        
        $data = array();
        
        if($request->request->get('includeUserData', false)) {
            $data['user'] = $token->getUser();
        }
        
        if($request->request->get('includePermissionsData', false)) {
            $data['permissions'] = array();
            foreach($token->getUser()->getGroups() as $group) {
                $data['permissions'][] = $group->getIdentifier();
            }
        }
        
        if(empty($data)) {
            return new Response(null, 204);
        }
        
        return new Response($this->formatItem($data), 200, array('Content-Type' => 'application/json'));
    }
    
    /**
     * Logout user action
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutAction(Request $request, ConstraintViolationList $violations = null)
    {
        $this->getApplication()->getSecurityContext()->setToken(null);
        $this->getApplication()->getSession()->invalidate();
        
        return new Response(null, 204);
    }
 
    
    /**
     * GET User 
     * 
     * @param int $id User ID
     */
    public function getAction($id) 
    {
        $user = $this->getEntityManager()->getRepository('BackBuilder\Security\User')->find($id);
        
        if(!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }
        
        return new Response($this->formatItem($user));
    }
    
    /**
     * DELETE User 
     * 
     * @param int $id User ID
     */
    public function deleteAction($id) 
    {
        $user = $this->getEntityManager()->getRepository('BackBuilder\Security\User')->find($id);

        if(!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }
        
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
        
        return new Response("", 204);
    }
    
    /**
     * UPDATE User 
     * 
     * @param int $id User ID
     */
    public function putAction($id, Request $request) 
    {
        $user = $this->getEntityManager()->getRepository('BackBuilder\Security\User')->find($id);

        if(!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        $userUpdated = $this->deserializeEntity($request->request->all(), $user);
        
        $this->getEntityManager()->persist($userUpdated);
        $this->getEntityManager()->flush();
        
        return new Response("", 204);
    }
    
    
    
    /**
     * GET User Permissions
     * 
     * @param int $id User ID
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @return type
     * @throws ValidationException
     */
    public function getPermissionsAction($id, ConstraintViolationList $violations = null) 
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        
        
        
        // TODO
        
        return new Response();
    }
}