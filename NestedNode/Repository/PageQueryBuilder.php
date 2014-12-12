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

use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Layout;
use BackBuilder\Site\Site;
use Doctrine\ORM\QueryBuilder;

/**
 * This class is responsible for building DQL query strings for Page
 *
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageQueryBuilder extends QueryBuilder
{

    /**
     * The root alias of this query
     * @var string
     */
    private $alias;

    /**
     * The join alias to section of this query
     * @var string
     */
    private $section_alias;

    /**
     * Joined field of section
     * @var array
     */
    private static $join_criteria = array(
        '_root',
        '_parent',
        '_leftnode',
        '_rightnode',
        '_site'
    );

    /**
     * Options
     * @var array 
     */
    public static $config = array(
        // date scheme to use in order to test publishing and archiving, should be Y-m-d H:i:00 for get 1 minute query cache
        'dateSchemeForPublishing' => 'Y-m-d H:i:00',
    );

    /**
     * Are some criteria joined fields of section?
     * @param array $criteria
     * @return boolean
     */
    public static function hasJoinCriteria(array $criteria = null)
    {
        if (null === $criteria) {
            return false;
        }

        return (0 < count(array_intersect(self::$join_criteria, array_keys($criteria))));
    }

    /**
     * Add query part to select on section page
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsSection()
    {
        return $this->andWhere($this->getAlias() . '._section = ' . $this->getAlias());
    }

    /**
     * Add query part to select online pages
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsOnline()
    {
        return $this->andWhere($this->getAlias() . '._state IN (' . $this->expr()->literal(Page::STATE_ONLINE) . ',' . $this->expr()->literal(Page::STATE_ONLINE + Page::STATE_HIDDEN) . ')')
                        ->andWhere($this->getAlias() . '._publishing IS NULL OR ' . $this->getAlias() . '._publishing <= ' . $this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())))
                        ->andWhere($this->getAlias() . '._archiving IS NULL OR ' . $this->getAlias() . '._archiving > ' . $this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())));
    }

    /**
     * Add query part to select visible (ie online and not hidden) pages
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsVisible()
    {
        $suffix = $this->getSuffix();
        return $this->andWhere($this->getAlias() . '._state = :states' . $suffix)
                        ->andWhere($this->getAlias() . '._publishing IS NULL OR ' . $this->getAlias() . '._publishing <= :now' . $suffix)
                        ->andWhere($this->getAlias() . '._archiving IS NULL OR ' . $this->getAlias() . '._archiving > :now' . $suffix)
                        ->setParameter('states' . $suffix, Page::STATE_ONLINE)
                        ->setParameter('now' . $suffix, date(self::$config['dateSchemeForPublishing'], time()));
    }

    /**
     * Add query part to select pages by layout
     * @param  \BackBuilder\Site\Layout                            $layout the layout to look for
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andLayoutIs(Layout $layout, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._layout = :layout'.$suffix)
                        ->setParameter('layout'.$suffix, $layout);
    }

    /**
     * Add query part to select online siblings of $page
     * @param  \BackBuilder\NestedNode\Page                        $page   the page to test
     * @param  boolean                                             $strict optional, if TRUE $page is excluded from results, FALSE by default
     * @param  array                                               $order  optional, the ordering criteria ( array($field => $sort) )
     * @param  int                                                 $limit  optional, the maximum number of results
     * @param  int                                                 $start  optional, the first result index, 0 by default
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsOnlineSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0, $alias = null)
    {
        return $this->andIsSiblingsOf($page, $strict, $order, $limit, $start, $alias)
                        ->andIsOnline($alias);
    }

    /**
     * Add query part to select previous online sibling of page
     * @param  \BackBuilder\NestedNode\Page                        $page  the page to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsPreviousOnlineSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias($alias);

        return $this->andIsPreviousSiblingsOf($page, $alias)
                        ->andIsOnline($alias)
                        ->orderBy($alias.'._leftnode', 'DESC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select next online sibling of page
     * @param  \BackBuilder\NestedNode\Page                        $page  the page to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNextOnlineSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias($alias);

        return $this->andIsNextSiblingsOf($page, $alias)
                        ->andIsOnline($alias)
                        ->orderBy($alias.'._leftnode', 'ASC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select visible siblings of $page
     * @param  \BackBuilder\NestedNode\Page                        $page   the page to test
     * @param  boolean                                             $strict optional, if TRUE $page is excluded from results, FALSE by default
     * @param  array                                               $order  optional, the ordering criteria ( array($field => $sort) )
     * @param  int                                                 $limit  optional, the maximum number of results
     * @param  int                                                 $start  optional, the first result index, 0 by default
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsVisibleSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0, $alias = null)
    {
        return $this->andIsSiblingsOf($page, $strict, $order, $limit, $start, $alias)
                        ->andIsVisible($alias);
    }

    /**
     * Add query part to select previous visible sibling of page
     * @param  \BackBuilder\NestedNode\Page                        $page  the page to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsPreviousVisibleSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias($alias);

        return $this->andIsPreviousSiblingsOf($page, $alias)
                        ->andIsVisible($alias)
                        ->orderBy($alias.'._leftnode', 'DESC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select next online sibling of page
     * @param  \BackBuilder\NestedNode\Page                        $page  the page to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNextVisibleSiblingOf(Page $page, $alias = null)
    {
        $alias = $this->getFirstAlias($alias);

        return $this->andIsNextSiblingsOf($page, $alias)
                        ->andIsVisible($alias)
                        ->orderBy($alias.'._leftnode', 'ASC')
                        ->setMaxResults(1);
    }

    /**
     * Add query part to select page having specific states
     * @param  mixed                                               $states one or several states to test
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsIn($states, $alias = null)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._state IN(:states'.$suffix.')')
                        ->setParameter('states'.$suffix, $states);
    }

    /**
     * Add query part to select page having not specific states
     * @param  mixed                                               $states one or several states to test
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsNotIn($states, $alias = null)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._state NOT IN(:states'.$suffix.')')
                        ->setParameter('states'.$suffix, $states);
    }

    /**
     * Add query part to select page having state lower than $state
     * @param  mixed                                               $state the state to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsLowerThan($state, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._state < :state'.$suffix)
                        ->setParameter('state'.$suffix, $state);
    }

    /**
     * Add query part to select page owned by $site
     * @param  \BackBuilder\Site\Site                              $site  the site to test
     * @param  string                                              $alias optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andSiteIs(Site $site, $alias = null)
    {
        list($alias, $suffix) = $this->getAliasAndSuffix($alias);

        return $this->andWhere($alias.'._site = :site'.$suffix)
                        ->setParameter('site'.$suffix, $site);
    }

    /**
     * Add query part to select page having title like $query
     * @param  string                                              $query
     * @param  string                                              $alias
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andTitleIsLike($query, $alias = null)
    {
        $alias = $this->getFirstAlias($alias);

        return $this->andWhere($this->expr()->like($alias.'._title', $this->expr()->literal('%'.$query.'%')));
    }

    /**
     * Add query part to select page matching provided criteria
     * @param  array                                               $restrictedStates optional, limit to pages having provided states, empty by default
     * @param  array                                               $options          optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                                               'afterPubdateField' => timestamp against page._modified,
     *                                                                               'searchField' => string to search for title
     * @param  string                                              $alias
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     * @Todo: more generic search function
     */
    public function andSearchCriteria($restrictedStates = array(), $options = array(), $alias = null)
    {
        $alias = $this->getFirstAlias($alias);
        if (true === is_array($restrictedStates) && 0 < count($restrictedStates) && false === in_array('all', $restrictedStates)) {
            $this->andStateIsIn($restrictedStates, $alias);
        }

        if (false === is_array($options)) {
            $options = array();
        }

        if (true === array_key_exists('beforePubdateField', $options)) {
            $date = new \DateTime();
            $this->andModifiedIsLowerThan($date->setTimestamp($options['beforePubdateField']), $alias);
        }

        if (true === array_key_exists('afterPubdateField', $options)) {
            $date = new \DateTime();
            $this->andModifiedIsGreaterThan($date->setTimestamp($options['afterPubdateField']), $alias);
        }

        if (true === array_key_exists('searchField', $options)) {
            $this->andTitleIsLike($options['searchField'], $alias);
        }

        return $this;
    }

    /**
     * Try to retreive the root alias for this builder
     * @return string
     * @throws \BackBuilder\Exception\BBException
     */
    public function getAlias()
    {
        if (null === $this->alias) {
            $aliases = $this->getRootAliases();
            if (0 === count($aliases)) {
                throw new \BackBuilder\Exception\BBException('Cannot access to root alias');
            }

            $this->alias = $aliases[0];
        }

        return $this->alias;
    }

    /**
     * Returns the join alias to section
     * @return string
     */
    public function getSectionAlias()
    {
        if (null === $this->section_alias) {
            $this->section_alias = $this->getAlias() . '_s';
            $this->join($this->getAlias() . '._section', $this->section_alias);
        }

        return $this->section_alias;
    }

    /**
     * Return new suffix for parameters
     * @return string
     * @codeCoverageIgnore
     */
    protected function getSuffix()
    {
        return '' . count($this->getParameters());
    }

}
