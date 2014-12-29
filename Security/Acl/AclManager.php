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

namespace BackBee\Security\Acl;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Security\Acl\Permission\InvalidPermissionException;
use BackBee\Security\Acl\Domain\IObjectIdentifiable;

class AclManager
{
    /**
     *
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;

    /**
     *
     * @var Symfony\Component\Security\Acl\Permission\PermissionMapInterface
     */
    protected $permissionMap;
    /**
     *
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext, PermissionMapInterface $permissionMap)
    {
        $this->securityContext = $securityContext;
        $this->permissionMap = $permissionMap;
    }

    /**
     * Get ACL for the given domain object
     *
     * @param  \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @return \Symfony\Component\Security\Acl\Domain\Acl
     */
    public function getAcl($objectIdentity)
    {
        $this->enforceObjectIdentity($objectIdentity);

        try {
            $acl = $this->securityContext->getACLProvider()->createAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException $e) {
            $acl = $this->securityContext->getACLProvider()->findAcl($objectIdentity);
        }

        return $acl;
    }

    /**
     * Updates an existing object ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     * @param int                                                                                                            $mask
     * @param type                                                                                                           $strategy
     */
    public function updateObjectAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $mask = $this->resolveMask($mask, $objectIdentity);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $mask, $strategy);
                break;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);
    }

    /**
     * Updates an existing object ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     * @param int                                                                                                            $mask
     * @param string|null                                                                                                    $strategy
     */
    public function updateClassAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $mask = $this->resolveMask($mask, $objectIdentity);

        $acl = $this->getAcl($objectIdentity);

        $found = false;
        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateClassAce($index, $mask, $strategy);
                $found = true;
                break;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);
    }

    /**
     * Updates an existing Object ACE, Inserts if it doesnt exist
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     * @param int                                                                                                            $mask
     * @param string|null                                                                                                    $strategy
     */
    public function insertOrUpdateObjectAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $mask = $this->resolveMask($mask, $objectIdentity);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $mask, $strategy);
                break;
            }
        }

        if (false === $found) {
            $acl->insertObjectAce($sid, $mask, 0, true, $strategy);
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * Updates an existing Class ACE, Inserts if it doesnt exist
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     * @param int                                                                                                            $mask
     * @param string|null                                                                                                    $strategy
     */
    public function insertOrUpdateClassAce($objectIdentity, $sid, $mask, $strategy = null)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);
        $mask = $this->resolveMask($mask, $objectIdentity);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateClassAce($index, $mask, $strategy);
                $found = true;
                break;
            }
        }

        if (false === $found) {
            $acl->insertClassAce($sid, $mask, 0, true, $strategy);
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);

        return $this;
    }

    /**
     * Deletes a class-scope ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     */
    public function deleteClassAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->deleteClassAce($index);
                $found = true;
                break;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);
    }

    /**
     * Deletes an object-scope ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     */
    public function deleteObjectAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getObjectAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->deleteObjectAce($index);
                $found = true;
                break;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
        }

        $this->securityContext->getACLProvider()->updateAcl($acl);
    }

    /**
     * Get a class-scope ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     */
    public function getClassAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getClassAces() as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                return $ace;
            }
        }

        throw new \InvalidArgumentException('ACE not found for the supplied combination of ObjectIdentity and SecurityIdentity');
    }

    /**
     * Get an object-scope ACE
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @param \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity     $sid
     */
    public function getObjectAce($objectIdentity, $sid)
    {
        $this->enforceObjectIdentity($objectIdentity);
        $this->enforceSecurityIdentity($sid);

        $acl = $this->getAcl($objectIdentity);

        $found = false;

        foreach ($acl->getObjectAces() as $index => $ace) {
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
     * @param  array $permissions
     * @return int
     */
    public function getMask(array $permissions)
    {
        $maskBuilder = new MaskBuilder();

        foreach ($permissions as $permission) {
            try {
                $maskBuilder->add($permission);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidPermissionException('Invalid permission mask: '.$permission, $permission, $e);
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
            'publish' => MaskBuilder::MASK_PUBLISH,
        ];

        return $permissions;
    }

    /**
     *
     * @param  \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface|\BackBee\Security\Acl\Domain\AObjectIdentifiable $objectIdentity
     * @throws \InvalidArgumentException
     */
    private function enforceObjectIdentity(&$objectIdentity)
    {
        if (
            ($objectIdentity instanceof IObjectIdentifiable)
        ) {
            $objectIdentity = new ObjectIdentity($objectIdentity->getObjectIdentifier(), get_class($objectIdentity));
        } elseif (! ($objectIdentity instanceof ObjectIdentityInterface)) {
            throw new \InvalidArgumentException('Object must implement IObjectIdentifiable');
        }
    }

    /**
     *
     * @param  \BackBee\Security\Acl\SecurityIdentityInterface|\Symfony\Component\Security\Acl\Model\UserSecurityIdentity $sid
     * @throws \InvalidArgumentException
     */
    private function enforceSecurityIdentity(&$sid)
    {
        if (
            ($sid instanceof DomainObjectInterface)
        ) {
            $sid = new UserSecurityIdentity($sid->getObjectIdentifier(), get_class($sid));
        } elseif (! ($sid instanceof SecurityIdentityInterface)) {
            throw new \InvalidArgumentException('Object must implement IObjectIdentifiable');
        }
    }

    /**
     * Resolves any variation of masks/permissions to an integer
     *
     * @param  string|int|array $masks
     * @return type
     */
    private function resolveMask($masks, $object)
    {
        $integerMask = 0;

        if (is_integer($masks)) {
            $integerMask = $masks;
        } elseif (is_string($masks)) {
            $permission = $this->permissionMap->getMasks($masks, $object);
            $integerMask = $this->resolveMask($permission, $object);
        } elseif (is_array($masks)) {
            foreach ($masks as $mask) {
                $integerMask += $this->resolveMask($mask, $object);
            }
        } else {
            throw new \RuntimeException('Not a valid mask type');
        }

        return $integerMask;
    }
}
