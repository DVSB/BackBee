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

namespace BackBuilder\Security\Acl;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use BackBuilder\Security\Acl\Permission\MaskBuilder,
    BackBuilder\Security\Acl\Permission\InvalidPermissionException;

class AclManager
{
    
    /**
     *
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;
    
    /**
     * 
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }
    
    /**
     * Get ACL for the given domain object
     * 
     * @param ObjectIdentityInterface $objectIdentity
     * @return \Symfony\Component\Security\Acl\Domain\Acl
     */
    public function getAcl(ObjectIdentityInterface $objectIdentity)
    {
        try {
            $acl = $this->securityContext->getACLProvider()->createAcl($objectIdentity);
        } catch(\Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException $e) {
            $acl = $this->securityContext->getACLProvider()->findAcl($objectIdentity);
        }

        return $acl;
    }
    
    /**
     * Updates an existing object ACE 
     * 
     * @param \BackBuilder\Security\Acl\SecurityIdentityInterface $sid
     * @param int $mask
     * @param type $strategy
     */
    public function updateObjectAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid, $mask, $strategy = null)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $mask, $strategy);
                break;
            }
        }
        
        if(false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    
    
    /**
     * Updates an existing object ACE 
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     * @param int $mask
     * @param mixed $strategy
     */
    public function updateClassAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid, $mask, $strategy = null)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        foreach($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateClassAce($index, $mask, $strategy);
                $found = true;
                break;
            }
        }
        
        if(false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    
    /**
     * Updates an existing Object ACE, Inserts if it doesnt exist
     * 
     * @param \BackBuilder\Security\Acl\SecurityIdentityInterface $sid
     * @param int $mask
     * @param type $strategy
     */
    public function insertOrUpdateObjectAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid, $mask, $strategy = null)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $mask, $strategy);
                break;
            }
        }
        
        if(false === $found) {
            $acl->insertObjectAce($sid, $mask, 0, true, $strategy);
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    /**
     * Updates an existing Class ACE, Inserts if it doesnt exist
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     * @param int $mask
     * @param mixed $strategy
     */
    public function insertOrUpdateClassAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid, $mask, $strategy = null)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateClassAce($index, $mask, $strategy);
                $found = true;
                break;
            }
        }
        
        if(false === $found) {
            $acl->insertClassAce($sid, $mask, 0, true, $strategy);
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    
    /**
     * Deletes a class-scope ACE
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     */
    public function deleteClassAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                
                $acl->deleteClassAce($index);
                $found = true;
                break;
            }
        }
        
        if(false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    /**
     * Deletes an object-scope ACE
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     */
    public function deleteObjectAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                
                $acl->deleteObjectAce($index);
                $found = true;
                break;
            }
        }
        
        if(false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }
        
        $this->securityContext->getACLProvider()->updateAcl($acl);
    }
    
    
    /**
     * Get a class-scope ACE
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     */
    public function getClassAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;
        
        foreach($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                return $ace;
            }
        }
        
        throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
    }
    
    /**
     * Get an object-scope ACE
     * 
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $objectIdentity
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $sid
     */
    public function getObjectAce(ObjectIdentityInterface $objectIdentity, SecurityIdentityInterface $sid)
    {
        $acl = $this->getAcl($objectIdentity);
        
        $found = false;

        foreach($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                return $ace;
            }
        }
        throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
    }
    
    /**
     * Calculate mask for a list of permissions
     * 
     * ['view', 'edit'] => (int) 5
     * 
     * @param array $permissions
     * @return int
     */
    public function getMask(array $permissions)
    {
        $maskBuilder = new MaskBuilder();
        
        foreach($permissions as $permission) {
            try {
                $maskBuilder->add($permission);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidPermissionException('Invalid permission mask: ' . $permission, $permission, $e);
            }
            
        }
        
        return $maskBuilder->get();
    }
    
    /**
     * Get a list of all available permission codes
     * 
     * @return array
     */
    public function getPermissionCodes()
    {
        $permissions = [
            'view' => MaskBuilder::MASK_VIEW,
            'create' => MaskBuilder::MASK_CREATE,
            'edit' => MaskBuilder::MASK_EDIT,
            'delete' => MaskBuilder::MASK_DELETE,
            'undelete' => MaskBuilder::MASK_UNDELETE,
            'operator' => MaskBuilder::MASK_OPERATOR,
            'master' => MaskBuilder::MASK_MASTER,
            'owner' => MaskBuilder::MASK_OWNER,
            'iddqd' => MaskBuilder::MASK_IDDQD,
            'commit' => MaskBuilder::MASK_COMMIT,
            'publish' => MaskBuilder::MASK_PUBLISH
        ];
        
        return $permissions;
    }
    
}