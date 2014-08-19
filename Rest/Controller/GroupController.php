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
    Symfony\Component\Validator\ConstraintViolation;

use BackBuilder\Rest\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;

use BackBuilder\Security\Group;
use BackBuilder\Rest\Exception\ValidationException;

/**
 * User Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class GroupController extends ARestController 
{
   
    /**
     * Get all records
     * 
     * @Rest\QueryParam(name = "site_uid", description="Site")
     * 
     */
    public function getCollectionAction(Request $request, ConstraintViolationList $violations = null) 
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from('BackBuilder\Security\Group', 'g')
        ;

        if($request->request->get('site_uid')) {
            $site = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\Site\Site')->find($request->request->get('site_uid'));
            
            if(!$site) {
                throw $this->createValidationException('site_uid', $request->request->get('site_uid'), 'Site is not valid: ' . $request->request->get('site_uid'));
            }
            
            $qb->leftJoin('g._site', 's')
                ->andWhere('s._uid = :site_uid')
                ->setParameter('site_uid', $site->getUid())
             ;
        }
        
        $groups = $qb->getQuery()->getResult();
        
        return new Response($this->formatCollection($groups));
    }
    
    /**
     * GET Group
     * 
     * @param int $id Group ID
     */
    public function getAction($id) 
    {
        $group = $this->getEntityManager()->getRepository('BackBuilder\Security\Group')->find($id);
        
        if(!$group) {
            return $this->create404Response(sprintf('Group not found with id %d', $id));
        }
        
        return new Response($this->formatItem($group));
    }
    
    /**
     * DELETE
     * 
     * @param int $id Group ID
     */
    public function deleteAction($id) 
    {
        $group = $this->getEntityManager()->getRepository('BackBuilder\Security\Group')->find($id);

        if(!$group) {
            return $this->create404Response(sprintf('Group not found with id %d', $id));
        }
        
        $this->getEntityManager()->remove($group);
        $this->getEntityManager()->flush();
        
        return new Response("", 204);
    }
    
    /**
     * UPDATE 
     * 
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     * @Rest\RequestParam(name = "identifier", requirements = {
     *  @Assert\NotBlank(message="Identifier is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of identifier is 50 characters")
     * })
     * @Rest\RequestParam(name = "site_uid", requirements = {
     *  @Assert\Length(max=50)
     * })
     * 
     * @param int $id
     */
    public function putAction($id, Request $request, ConstraintViolationList $violations = null) 
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        
        $group = $this->getEntityManager()->getRepository('BackBuilder\Security\Group')->find($id);
        
        if(!$group) {
            return $this->create404Response(sprintf('User not found with id %d', $id));
        }

        $this->deserializeEntity($request->request->all(), $group);
        
        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();
        
        return new Response("", 204);
    }
    
    /**
     * Create
     * 
     * 
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of name is 50 characters")
     * })
     * @Rest\RequestParam(name = "identifier", requirements = {
     *  @Assert\NotBlank(message="Identifier is required"),
     *  @Assert\Length(max=50, minMessage="Maximum length of identifier is 50 characters")
     * })
     * @Rest\RequestParam(name = "site_uid", requirements = {
     *  @Assert\Length(max=50)
     * })
     * 
     */
    public function postAction(Request $request, ConstraintViolationList $violations = null)
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        
        $groupExists = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\Security\Group')->findBy(array('_identifier' => $request->request->get('identifier')));
        
        if($groupExists) {
            throw new ValidationException(new ConstraintViolationList(array(
                new ConstraintViolation('Group with that identifier already exists', 'Group with that identifier already exists', array(), 'identifier', 'identifier', $request->request->get('identifier'))
            )));
        }
        
        $group = new Group();
        
        if($request->request->get('site_uid')) {
            $site = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\Site\Site')->find($request->request->get('site_uid'));
            
            if(!$site) {
                throw $this->createValidationException('site_uid', $request->request->get('site_uid'), 'Site is not valid: ' . $request->request->get('site_uid'));
            }
            
            $group->setSite($site);
        }
        
        $group = $this->deserializeEntity($request->request->all(), $group);
        
        $this->getEntityManager()->persist($group);
        $this->getEntityManager()->flush();
        
        return new Response($this->formatItem($group), 200, array('Content-Type' => 'application/json'));
    }
    
    
}