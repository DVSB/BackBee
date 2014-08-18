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
class AclController extends ARestController 
{
   
    /**
     * Get all records
     * 
     * @Rest\QueryParam(name = "group_id", description="Security Group ID", requirements = {
     *  @Assert\NotBlank(message="Group ID cannot be empty") 
     * })
     * @Rest\QueryParam(name = "object_id", description="Object ID", requirements = {
     *  @Assert\NotBlank(message="Object ID cannot be empty")
     * })
     * @Rest\QueryParam(name = "object_class", description="Object Class name", requirements = {
     *  @Assert\NotBlank(message="Object Class cannot be empty")
     * })
     * @Rest\QueryParam(name = "mask", description="Permission Mask", requirements = {
     *  @Assert\NotBlank(message="Mask must be provided"), 
     *  @Assert\Type(type="integer", message="Mask must be an integer"), 
     * })
     * 
     */
    public function getEntryCollectionAction(Request $request, ConstraintViolationList $violations = null) 
    {
        if(null !== $violations && count($violations) > 0) {
            throw new ValidationException($violations);
        }
        return new Response();

        $aclProvider = $this->getBBapp()->getSecurityContext()->getACLProvider();
        
        /* @var $aclProvider \Symfony\Component\Security\Acl\Dbal\AclProvider */
        $aclProvider->findAcls();
        //
        
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
    
    
    public function getClassCollectionAction(Request $request) 
    {
        $sql = 'SELECT * FROM acl_classes';
        
        $results = $this->getEntityManager()->getConnection()->fetchAll($sql);
        
        return new Response(json_encode($results));
    }
    
}