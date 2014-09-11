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

namespace BackBuilder\Security\Authorization\Voter;

use BackBuilder\BBApplication;
use BackBuilder\Security\Token\BBUserToken;
use BackBuilder\Security\Token\PublicKeyToken;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Authorization\Voter
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>, Eric Chau <eric.chau@lp-digital.fr>
 */
class SudoVoter implements VoterInterface
{

    private $_application;
    private $_sudoers;

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\BBApplication $application
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_sudoers = $this->_application->getConfig()->getSecurityConfig('sudoers') ?: array();
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return true;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === 'BackBuilder\Security\Token\BBUserToken'
            || $class === 'BackBuilder\Security\Token\PublicKeyToken'
        ;
    }

    /**
     * {@inheritdoc}
     */
    function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;

        if (true === $this->supportsClass(get_class($token))) {
            if (
                true === array_key_exists($token->getUser()->getUsername(), $this->_sudoers)
                && $token->getUser()->getId() === $this->_sudoers[$token->getUser()->getUsername()]
            ) {
                $result = VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }

}
