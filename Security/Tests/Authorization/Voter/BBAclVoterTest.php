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

namespace BackBuilder\Security\Tests\Authorization\Voter;

use BackBuilder\Tests\TestCase;
use BackBuilder\Security\Role\RoleHierarchy;
use BackBuilder\Security\Acl\Domain\SecurityIdentityRetrievalStrategy;
use BackBuilder\Security\Authentication\TrustResolver;
use BackBuilder\Security\Acl\Permission\PermissionMap;
use BackBuilder\Security\Acl\Permission\MaskBuilder;
use BackBuilder\Security\Token\UsernamePasswordToken;
use BackBuilder\Security\Authorization\Voter\BBAclVoter;
use BackBuilder\Security\User;
use BackBuilder\Security\Group;
use Symfony\Component\Security\Acl\Domain\ObjectIdentityRetrievalStrategy;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authorization\Voter
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @coversDefaultClass \BackBuilder\Security\Authorization\Voter\BBAclVoter
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
                'sid_table_name'           => 'acl_security_identities'
            ]
        );

        $this->aclVoter = new \BackBuilder\Security\Authorization\Voter\BBAclVoter(
            $aclprovider,
            new ObjectIdentityRetrievalStrategy(),
            new SecurityIdentityRetrievalStrategy(
                new RoleHierarchy(array()),
                new TrustResolver('BackBuilder\Security\Token\AnonymousToken', 'BackBuilder\Security\Token\RememberMeToken')
            ),
            new PermissionMap(),
            $this->getBBApp()->getLogging(),
            false,
            $this->getBBApp()
        );

        // save user
        $this->group = new Group();
        $this->group->setName('groupName');
        $this->group->setIdentifier('GROUP_ID');
        $this->getBBApp()->getEntityManager()->persist($this->group);

        // valid user
        $this->user = new User();
        $this->user->addGroup($this->group);
        $this->user->setLogin('user123');
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
            new UserSecurityIdentity($this->group->getIdentifier(), get_class($this->group)),
            MaskBuilder::MASK_EDIT
        );

        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, $this->user, ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_DENIED, $this->aclVoter->vote($this->token, new ObjectIdentity('class', get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_DENIED, $this->aclVoter->vote($this->token, new ObjectIdentity(23545866754, get_class($this->user)), ['EDIT']));
    }

    public function test_vote_classScope()
    {
        $aclManager = $this->getBBApp()->getContainer()->get('security.acl_manager');
        $aclManager->insertOrUpdateClassAce(
            new ObjectIdentity('class', get_class($this->user)),
            new UserSecurityIdentity($this->group->getIdentifier(), get_class($this->group)),
            MaskBuilder::MASK_EDIT
        );

        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity('class', get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, $this->user, ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity($this->user->getId(), get_class($this->user)), ['EDIT']));
        $this->assertEquals(BBAclVoter::ACCESS_GRANTED, $this->aclVoter->vote($this->token, new ObjectIdentity(23545866754, get_class($this->user)), ['EDIT']));
    }

    public function test_vote_nullObject()
    {
        $this->assertEquals(BBAclVoter::ACCESS_ABSTAIN, $this->aclVoter->vote($this->token, null, ['EDIT']));
    }
}
