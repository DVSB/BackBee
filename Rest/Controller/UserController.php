<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Rest\Controller;

use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Security\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Controller
 *
 * @category    BackBee
 * @package     BackBee\Rest
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
    public function getCollectionAction(Request $request)
    {
        // TODO


        if (!$this->isGranted('VIEW', new ObjectIdentity('class', 'BackBee\Security\User'))) {
            throw new AccessDeniedException(sprintf('You are not authorized to view users'));
        }

        return array();
    }

    /**
     * GET User
     *
     * @param int $id User ID
     */
    public function getAction($id)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('You must be authenticated to delete users');
        }

        $user = $this->getEntityManager()->getRepository('BackBee\Security\User')->find($id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('VIEW', $user)) {
            throw new AccessDeniedException(sprintf('You are not authorized to view user with id %s', $id));
        }

        return new Response($this->formatItem($user), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * DELETE User
     *
     * @param int $id User ID
     */
    public function deleteAction($id)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('You must be authenticated to delete users');
        }

        $user = $this->getEntityManager()->getRepository('BackBee\Security\User')->find($id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('DELETE', $user)) {
            throw new AccessDeniedException(sprintf('You are not authorized to delete user with id %s', $id));
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
    public function putAction($id, Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('You must be authenticated to view users');
        }

        $user = $this->getEntityManager()->getRepository('BackBee\Security\User')->find($id);

        if (!$user) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        if (!$this->isGranted('EDIT', $user)) {
            throw new AccessDeniedException(sprintf('You are not authorized to view user with id %s', $id));
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
     *
     */
    public function postAction(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('You must be authenticated to view users');
        }

        $userExists = $this->getApplication()->getEntityManager()->getRepository('BackBee\Security\User')->findBy(array('_login' => $request->request->get('login')));

        if ($userExists) {
            throw new ConflictHttpException(sprintf('User with that login already exists: %s', $request->request->get('login')));
        }

        $user = new User();

        if (!$this->isGranted('CREATE', new ObjectIdentity('class', get_class($user)))) {
            throw new AccessDeniedException(sprintf('You are not authorized to create users'));
        }

        $user = $this->deserializeEntity($request->request->all(), $user);

        // handle the password
        if ($request->request->has('password')) {
            $encoderFactory = $this->getContainer()->get('security.context')->getEncoderFactory();
            $password = $request->request->get('password');

            if ($encoderFactory && $encoder = $encoderFactory->getEncoder($user)) {
                $password = $encoder->encodePassword($password, "");
            }

            $user->setPassword($password);
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return new Response($this->formatItem($user), 200, array('Content-Type' => 'application/json'));
    }
}
