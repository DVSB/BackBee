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

namespace BackBuilder\Site\Repository;

use BackBuilder\Site\UserPreferences;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * UserPreferences object in BackBuilder 5
 *
 * User preferences persistence
 *
 * @category    BackBuilder
 * @package     BackBuilder\Site
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class UserPreferencesRepository extends EntityRepository
{

    /**
     * Retrieve the user prefernces by security token.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return UserPreferences object
     */
    public function loadPreferences($token)
    {
        if ($token instanceof TokenInterface) {
            $uid = $this->retrieveUserPreferencesUid($token);
        } else {
            $uid = $token;
        }
        return $this->retrieveByUid($uid, $token->getUser());
    }

    /**
     * Calculate the unique user preferences key.
     *
     * @codeCoverageIgnore
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return String
     */
    public function retrieveUserPreferencesUid(TokenInterface $token)
    {
        return md5((string) $token->getUser());
    }

    /**
     * Set the user preferences.
     *
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param string $preferences
     */
    public function setPreferences(TokenInterface $token, $preferences)
    {
        $user_preferences = $this->loadPreferences($token);
        return $user_preferences->setPreferences($preferences);
    }

    /**
     * Find the UserPreferences object by uid.
     *
     * @param string $uid
     * @param BackBuilder\Security\User $user
     * @return \BackBuilder\Site\UserPreferences
     */
    private function retrieveByUid($uid, $user)
    {
        try {
            $q = $this->createQueryBuilder('up')
                    ->andWhere('up._uid = :uid')
                    ->setParameters(array(
                'uid' => $uid
                    ));
            return $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            unset($e);
            $user_preference = new UserPreferences();
            $user_preference->setUid($uid)->setOwner($user)->setPreferences('{"updated_at": 0}');
            $this->_em->persist($user_preference);
            $this->_em->flush();

            return $user_preference;
        }
    }

}