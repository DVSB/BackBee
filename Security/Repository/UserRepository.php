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

namespace BackBuilder\Security\Repository;

use Symfony\Component\Security\Core\User\UserCheckerInterface,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\User\UserProviderInterface,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException;
;

use BackBuilder\Security\ApiUserProviderInterface;

use Doctrine\ORM\EntityRepository;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UserRepository extends EntityRepository implements UserProviderInterface, UserCheckerInterface, ApiUserProviderInterface
{

    public function checkPreAuth(UserInterface $user)
    {
        
    }

    public function checkPostAuth(UserInterface $user)
    {
        
    }
    
    public function loadUserByPublicKey($publicApiKey)
    {
        return $this->findOneBy(array('_api_key_public' => $publicApiKey));
    }

    public function loadUserByUsername($username)
    {
        return $this->findOneBy(array('_login' => $username));
    }

    public function refreshUser(UserInterface $user)
    {
        if (false === $this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Unsupported User class `%s`.', get_class($user)));
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return ($class == 'BackBuilder\Security\User');
    }

}