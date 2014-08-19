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

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\Page,
    BackBuilder\Site\Layout,
    BackBuilder\Site\Site;

/**
 * This class is responsible for building DQL query strings for Page
 * 
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageQueryBuilder extends NestedNodeQueryBuilder
{

    public static $config = array(
        // date scheme to use in order to test publishing and archiving, should be Y-m-d H:i:00 for get 1 minute query cache
        'dateSchemeForPublishing' => 'Y-m-d H:i:00'
    );

    /**
     * Add query part to select online pages
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsOnline($alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._state IN (:states' . $suffix . ')')
                        ->andWhere($alias . '._publishing IS NULL OR ' . $alias . '._publishing <= :now' . $suffix)
                        ->andWhere($alias . '._archiving IS NULL OR ' . $alias . '._archiving > :now' . $suffix)
                        ->setParameter('states' . $suffix, array(Page::STATE_ONLINE, Page::STATE_ONLINE + Page::STATE_HIDDEN))
                        ->setParameter('now' . $suffix, date(self::$config['dateSchemeForPublishing'], time()));
    }

    /**
     * Add query part to select visible (ie online and not hidden) pages
     * @param string $alias     optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsVisible($alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._state = :states' . $suffix)
                        ->andWhere($alias . '._publishing IS NULL OR ' . $alias . '._publishing <= :now' . $suffix)
                        ->andWhere($alias . '._archiving IS NULL OR ' . $alias . '._archiving > :now' . $suffix)
                        ->setParameter('states' . $suffix, Page::STATE_ONLINE)
                        ->setParameter('now' . $suffix, date(self::$config['dateSchemeForPublishing'], time()));
    }

    /**
     * Add query part to select pages by layout
     * @param \BackBuilder\Site\Layout $layout   the layout to look for
     * @param string $alias                      optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andLayoutIs(Layout $layout, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._layout = :layout' . $suffix)
                        ->setParameter('layout' . $suffix, $layout);
    }

    /**
     * Add query part to select online siblings of $page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param boolean $strict                      optional, if TRUE $page is excluded from results, FALSE by default
     * @param array $order                         optional, the ordering criteria ( array($field => $sort) )
     * @param int $limit                           optional, the maximum number of results
     * @param int $start                           optional, the first result index, 0 by default
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsOnlineSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0, $alias = null)
    {
        return $this->andIsSiblingsOf($page, $strict, $order, $limit, $start, $alias)
                        ->andIsOnline($alias);
    }

    /**
     * Add query part to select previous online sibling of page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsPreviousOnlineSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias();
        return $this->andIsPreviousSiblingsOf($page, $alias)
                        ->andIsOnline($alias)
                        ->orderBy($alias . '._leftnode', 'DESC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select next online sibling of page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNextOnlineSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias();
        return $this->andIsNextSiblingsOf($page, $alias)
                        ->andIsOnline($alias)
                        ->orderBy($alias . '._leftnode', 'ASC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select visible siblings of $page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param boolean $strict                      optional, if TRUE $page is excluded from results, FALSE by default
     * @param array $order                         optional, the ordering criteria ( array($field => $sort) )
     * @param int $limit                           optional, the maximum number of results
     * @param int $start                           optional, the first result index, 0 by default
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsVisibleSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0, $alias = null)
    {
        return $this->andIsSiblingsOf($page, $strict, $order, $limit, $start, $alias)
                        ->andIsVisible($alias);
    }

    /**
     * Add query part to select previous visible sibling of page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsPreviousVisibleSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias();
        return $this->andIsPreviousSiblingsOf($page, $alias)
                        ->andIsVisible($alias)
                        ->orderBy($alias . '._leftnode', 'DESC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select next online sibling of page
     * @param \BackBuilder\NestedNode\Page $page   the page to test
     * @param string $alias                        optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNextVisibleSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias();
        return $this->andIsNextSiblingsOf($page, $alias)
                        ->andIsVisible($alias)
                        ->orderBy($alias . '._leftnode', 'ASC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select page having specific states
     * @param mixed $states       one or several states to test
     * @param string $alias       optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsIn($states, $alias = null)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._state IN(:states' . $suffix . ')')
                        ->setParameter('states' . $suffix, $states);
    }

    /**
     * Add query part to select page having not specific states
     * @param mixed $states       one or several states to test
     * @param string $alias       optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsNotIn($states, $alias = null)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._state NOT IN(:states' . $suffix . ')')
                        ->setParameter('states' . $suffix, $states);
    }

    /**
     * Add query part to select page having state lower than $state
     * @param mixed $state        the state to test
     * @param string $alias       optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsLowerThan($state, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._state < :state' . $suffix)
                        ->setParameter('state' . $suffix, $state);
    }

    /**
     * Add query part to select page owned by $site
     * @param \BackBuilder\Site\Site $site   the site to test
     * @param type $alias                    optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andSiteIs(Site $site, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);
        return $this->andWhere($alias . '._site = :site' . $suffix)
                        ->setParameter('site' . $suffix, $site);
    }

}
