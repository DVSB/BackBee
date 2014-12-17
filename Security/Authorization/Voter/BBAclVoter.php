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

namespace BackBee\Security\Authorization\Voter;

use BackBee\Bundle\ABundle;
use BackBee\NestedNode\ANestedNode;
use BackBee\ClassContent\AClassContent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\AclVoter;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Authorization\Voter
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBAclVoter extends AclVoter
{
    /**
     * The current BackBee application
     * @var \BackBee\BBApplication
     */
    private $_application;

    public function __construct(AclProviderInterface $aclProvider, ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy, SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy, PermissionMapInterface $permissionMap, LoggerInterface $logger = null, $allowIfObjectIdentityUnavailable = true, \BackBee\BBApplication $application = null)
    {
        parent::__construct($aclProvider, $oidRetrievalStrategy, $sidRetrievalStrategy, $permissionMap, $logger, $allowIfObjectIdentityUnavailable);
        $this->_application = $application;
    }

    /**
     * Returns the vote for the given parameters.
     * @param  \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token      A TokenInterface instance
     * @param  object|ObjectIdentityInterface                                       $object     The object to secure
     * @param  array                                                                $attributes An array of attributes associated with the method being invoked
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (null === $object) {
            return self::ACCESS_ABSTAIN;
        }

        if ($object instanceof ANestedNode) {
            return $this->_voteForNestedNode($token, $object, $attributes);
        } elseif ($object instanceof AClassContent) {
            return $this->_voteForClassContent($token, $object, $attributes);
        } elseif ($object instanceof ABundle) {
            return parent::vote($token, $object, $attributes);
        }

        return $this->_vote($token, $object, $attributes);
    }

    /**
     * Returns the vote for the cuurent object, if denied try the vote for the general object
     * @param  \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param  object|ObjectIdentityInterface                                       $object
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function _vote(TokenInterface $token, $object, array $attributes)
    {
        if (self::ACCESS_GRANTED !== $result = parent::vote($token, $object, $attributes)) {
            // try class-scope ace
            $objectIdentity = null;

            if ($object instanceof ObjectIdentityInterface) {
                $objectIdentity = new ObjectIdentity('class', $object->getType());
            } else {
                $objectIdentity = new ObjectIdentity('class', ClassUtils::getRealClass($object));
            }

            $result = parent::vote($token, $objectIdentity, $attributes);
        }

        return $result;
    }

    /**
     * Returns the vote for nested node object, recursively till root
     * @param  \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param  \BackBee\NestedNode\ANestedNode                                  $node
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function _voteForNestedNode(TokenInterface $token, ANestedNode $node, array $attributes)
    {
        if (self::ACCESS_DENIED === $result = $this->_vote($token, $node, $attributes)) {
            if (null !== $node->getParent()) {
                $result = $this->_voteForNestedNode($token, $node->getParent(), $attributes);
            }
        }

        return $result;
    }

    /**
     * Returns the vote for class content object, recursively till AClassContent
     * @param  \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param  \BackBee\ClassContent\AClassContent                              $content
     * @param  array                                                                $attributes
     * @return integer                                                              either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    private function _voteForClassContent(TokenInterface $token, AClassContent $content, array $attributes)
    {
        if (null === $content->getProperty('category')) {
            return self::ACCESS_GRANTED;
        }

        if (self::ACCESS_DENIED === $result = $this->_vote($token, $content, $attributes)) {
            if (false !== $parent_class = get_parent_class($content)) {
                if ('BackBee\ClassContent\AClassContent' !== $parent_class) {
                    $parent_class = NAMESPACE_SEPARATOR.$parent_class;
                    $result = $this->_voteForClassContent($token, new $parent_class('*'), $attributes);
                }
            }
        }

        return $result;
    }
}
