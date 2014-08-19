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
    Symfony\Component\Validator\ConstraintViolation,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent,
    Symfony\Component\Security\Http\SecurityEvents,
    Symfony\Component\HttpFoundation\JsonResponse;

use BackBuilder\Rest\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

use BackBuilder\Security\Token\UsernamePasswordToken;

use BackBuilder\Security\User;
use BackBuilder\Rest\Exception\ValidationException;

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
     * @Rest\RequestParam(name = "login", requirements = {
     *  @Assert\NotBlank(message="Login is required"),
     *  @Assert\Length(min=6, minMessage="Minimum length of the login is 6 characters")
     * })
     * @Rest\RequestParam(name = "firstname", requirements = {
     *  @Assert\NotBlank(message="First Name is required")
     * })
     * @Rest\RequestParam(name = "lastname", requirements = {
     *  @Assert\NotBlank(message="Last Name is required")
     * })
     *
     * @param int $id User ID
     */
    public function putAction($id, Request $request, ConstraintViolationList $violations = null)
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $user = $this->getEntityManager()->getRepository('BackBuilder\Security\User')->find($id);

        if(!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        $this->deserializeEntity($request->request->all(), $user);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    /**
     * Create User
     *
     *
     * @Rest\RequestParam(name = "password", requirements = {
     *  @Assert\NotBlank(message="Password not provided"),
     *  @Assert\Length(min=6, minMessage="Password minimum length is 6 characters")
     * })
     * @Rest\RequestParam(name = "login", requirements = {
     *  @Assert\NotBlank(message="Login is required"),
     *  @Assert\Length(min=6, minMessage="Minimum length of the login is 6 characters")
     * })
     * @Rest\RequestParam(name = "firstname", requirements = {
     *  @Assert\NotBlank(message="First Name is required")
     * })
     * @Rest\RequestParam(name = "lastname", requirements = {
     *  @Assert\NotBlank(message="Last Name is required")
     * })
     *
     */
    public function postAction(Request $request, ConstraintViolationList $violations = null)
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $userExists = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\Security\User')->findBy(array('_login' => $request->request->get('login')));

        if($userExists) {
            throw new ValidationException(new ConstraintViolationList(array(
                new ConstraintViolation('User with that login already exists', 'User with that login already exists', array(), 'login', 'login', $request->request->get('login'))
            )));
        }

        $user = new User();
        $user = $this->deserializeEntity($request->request->all(), $user);

        // handle the password
        if($request->request->has('password')) {
            $encoderFactory = $this->getContainer()->get('security.context')->getEncoderFactory();
            $password = $request->request->get('password');

            if($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
                $password = $encoder->encodePassword($password, "");
            }

            $user->setPassword($password);
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return new Response($this->formatItem($user), 200, array('Content-Type' => 'application/json'));
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