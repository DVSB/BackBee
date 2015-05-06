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

namespace BackBee\Security\Tests\Authorization\Voter;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentityRetrievalStrategy;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use BackBee\Security\Acl\Domain\SecurityIdentityRetrievalStrategy;
use BackBee\Security\Acl\Permission\MaskBuilder;
use BackBee\Security\Acl\Permission\PermissionMap;
use BackBee\Security\Authentication\TrustResolver;
use BackBee\Security\Authorization\Voter\BBAclVoter;
use BackBee\Security\Group;
use BackBee\Security\Role\RoleHierarchy;
use BackBee\Security\Token\UsernamePasswordToken;
use BackBee\Security\User;
use BackBee\Tests\TestCase;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBee\Security\Authorization\Voter\BBAclVoter
 */
class BBAclVoterTest extends TestCase
{
    protected $aclVoter;

    protected $user;
    protected $group;
    protected $token;

    protected function setUp()
    {
        $this->initAutoload();
        $this->bbapp = $this->getBBApp();
        $this->initDb($this->bbapp);
        $this->initAcl();
        $this->bbapp->start();

        $aclprovider = new MutableAclProvider(
            $this->getBBApp()->getEntityManager()->getConnection(),
            new PermissionGrantingStrategy(),
            [
                'class_table_name'         => 'acl_classes',
                'entry_table_name'         => 'acl_entries',
                'oid_table_name'           => 'acl_object_identities',
                'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
                'sid_table_name'           => 'acl_security_identities',
            ]
        );

        $this->aclVoter = new \BackBee\Security\Authorization\Voter\BBAclVoter(
            $aclprovider,
            new ObjectIdentityRetrievalStrategy(),
            new SecurityIdentityRetrievalStrategy(
                new RoleHierarchy(array()),
                new TrustResolver('BackBee\Security\Token\AnonymousToken', 'BackBee\Security\Token\RememberMeToken')
            ),
            new PermissionMap(),
            $this->getBBApp()->getLogging(),
            false,
            $this->getBBApp()
        );

        // save user
        $this->group = new Group();
        $this->group->setName('groupName');
        $this->getBBApp()->getEntityManager()->persist($this->group);

        // valid user
        $this->user = new User();
        $this->user->addGroup($this->group);
        $this->user->setLogin('user123');
        $this->user->setEmail('user123@provider.com');
        $this->user->setPassword('password123');
        $this->user->setActivated(true);
        $this->getBBApp()->getEntityManager()->persist($this->user);

        $this->getBBApp()->getEntityManager()->flush();

        $this->token = new UsernamePasswordToken($this->user, []);
    }

    public function test_vote_objectScope()
    {
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateClassAce(
            new ObjectIdentity($this->user->getId(), get_class($this->user)),
            new UserSecurityIdentity($this->group->getObjectIdentifier(), get_class($this->group)),
            MaskBuilder::MASK_EDIT
        );

        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, $this->user, ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_DENIED, $this->aclVoter->vote($this->token, new ObjectIdentity('all', get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_DENIED, $this->aclVoter->vote($this->token, new ObjectIdentity(23545866754, get_class($this->user)), ['EDIT']));
    }

    public function test_vote_classScope()
    {
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateClassAce(
            new ObjectIdentity('all', get_class($this->user)),
            new UserSecurityIdentity($this->group->getObjectIdentifier(), get_class($this->group)),
            MaskBuilder::MASK_EDIT
        );

        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity('all', get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, $this->user, ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity($this->user->getId(), get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity(23545866754, get_class($this->user)), ['EDIT']));
    }

    public function test_vote_nullObject()
    {
        $this->assertEquals(BBAclVoter::ACCESS_ABSTAIN, $this->aclVoter->vote($this->token, null, ['EDIT']));
    }
}
