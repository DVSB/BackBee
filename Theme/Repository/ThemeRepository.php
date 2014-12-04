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

namespace BackBuilder\Theme\Repository;

use BackBuilder\Theme\PersonalThemeEntity;
use Doctrine\ORM\EntityRepository;

/**
 * ThemeRepository object in BackBuilder 5
 *
 * Theme persistence
 *
 * @category    BackBuilder
 * @package     BackBuilder\Theme
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ThemeRepository extends EntityRepository
{
    /**
     * Retrieve the theme by site uid.
     *
     * @param  string              $site_uid
     * @return PersonalThemeEntity object
     */
    public function retrieveBySiteUid($site_uid)
    {
        $q = $this->createQueryBuilder('t')
                ->andWhere('t._site_uid = :site_uid')
                ->setParameters(array('site_uid' => $site_uid));
        $theme = $q->getQuery()->getOneOrNullResult();

        return $theme;
    }

    /**
     * Retrieve the theme by site uid and save the current theme.
     *
     * @param  string              $site_uid
     * @param  PersonalThemeEntity $theme
     * @return Theme               object
     */
    public function setCurrentTheme($site_uid, PersonalThemeEntity $theme)
    {
        $current = $this->retrieveBySiteUid($site_uid);
        if ($current != null) {
            foreach ($theme->toArray() as $key => $value) {
                $current->{'set'.ucfirst($key)}($value);
            }
        } else {
            $current = $theme;
            $current->setSiteUid($site_uid);
        }

        $this->_em->persist($current);
        $this->_em->flush();

        return $current;
    }
}
