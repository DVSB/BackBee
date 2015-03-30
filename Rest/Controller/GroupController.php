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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Rest\Exception\ValidationException;
use BackBee\Security\Group;

/**
 * User Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class GroupController extends AbstractRestController
{
    /**
     * Get all records.
     */
    public function getCollectionAction(Request $request)
    {
        if ($request->request->has('site_uid')) {
            $site_uid = $request->request->get('site_uid');
            $this->checkSiteUid($site_uid);
        } else {
            $site_uid = $this->getApplication()->getSite()->getUid();
        }

        $groups = $this->getEntityManager()->getRepository('BackBee\Security\Group')->findBy(['_site' => $site_uid]);

        return new Response($this->formatCollection($groups), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * GET Group.
     *
     * @Rest\ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     */
    public function getAction(Group $group)
    {
        return new Response($this->formatItem($group), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * DELETE.
     *
     * @Rest\ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     */
    public function deleteAction(Group $group)
    {
        $this->getEntityManager()->remove($group);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    /**
     * UPDATE.
     *
     * @Rest\RequestParam(name = "name", requirements = {
     *   @Assert\NotBlank(message="Name is required"),
     *   @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     *
     * @Rest\ParamConverter(name="group", id_name = "id", class="BackBee\Security\Group")
     */
    public function putAction(Group $group, Request $request)
    {
        $this->deserializeEntity($request->request->all(), $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        return new Response("", 204);
    }

    /**
     * Create.
     *
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     */
    public function postAction(Request $request)
    {
        $group = new Group();

        if ($request->request->has('site_uid')) {
            $this->checkSiteUid($request->request->get('site_uid'));

            $site = $this->getEntityManager()->find('BackBee\Site\Site', $request->request->get('site_uid'));
        } else {
            $site = $this->getApplication()->getSite();
        }

        $duplicate = $this->getEntityManager()->getRepository('BackBee\Security\Group')->findOneBy([
            '_name' => $request->request->get('name'),
            '_site' => $site,
        ]);

        if ($duplicate !== null) {
            return new Response('', 409);
        }

        $group->setName($request->request->get('name'));
        $group->setSite($site);

        $group = $this->deserializeEntity($request->request->all(), $group);

        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();

        return new Response($this->formatItem($group), 200, ['Content-Type' => 'application/json']);
    }

    private function checkSiteUid($site_uid)
    {
        $site = $this->getEntityManager()->find('BackBee\Site\Site', $site_uid);
        $validator = Validation::createValidator();
        $constraint = new Assert\NotNull(['message' => 'Invalid site identifier']);
        $violations = $validator->validateValue($site, $constraint);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }
}
