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
     * Add query part to select on not-section page
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNotSection()
    {
        return $this->andWhere($this->getAlias() . '._section != ' . $this->getAlias());
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
     * Add query part to select not deleted pages
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsNotDeleted()
    {
        return $this->andWhere($this->getAlias() . '._state < ' . $this->expr()->literal(Page::STATE_DELETED));
    }

    /**
     * Add query part to select visible (ie online and not hidden) pages
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsVisible()
    {
        return $this->andWhere($this->getAlias() . '._state = ' . $this->expr()->literal(Page::STATE_ONLINE))
                        ->andWhere($this->getAlias() . '._publishing IS NULL OR ' . $this->getAlias() . '._publishing <= ' . $this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())))
                        ->andWhere($this->getAlias() . '._archiving IS NULL OR ' . $this->getAlias() . '._archiving > ' . $this->expr()->literal(date(self::$config['dateSchemeForPublishing'], time())));
    }

    /**
     * Add query part to select ancestors of $page
     * @param \BackBuilder\NestedNode\Page $page
     * @param boolean $strict   If TRUE, $node is excluded from the selection
     * @param int $at_level     Filter ancestors by their level
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsAncestorOf(Page $page, $strict = false, $at_level = null)
    {
        $suffix = $this->getSuffix();
        $this->andIsSection()
                ->andWhere($this->getSectionAlias() . '._root = :root' . $suffix)
                ->andWhere($this->getSectionAlias() . '._leftnode <= :leftnode' . $suffix)
                ->andWhere($this->getSectionAlias() . '._rightnode >= :rightnode' . $suffix)
                ->setParameter('root' . $suffix, $page->getSection()->getRoot())
                ->setParameter('leftnode' . $suffix, $page->getSection()->getLeftnode() - (true === $page->hasMainSection() && $strict ? 1 : 0))
                ->setParameter('rightnode' . $suffix, $page->getSection()->getRightnode() + (true === $page->hasMainSection() && $strict ? 1 : 0));

        if (null !== $at_level) {
            $this->andWhere($this->getSectionAlias() . '._level = :level' . $suffix)
                    ->setParameter('level' . $suffix, $at_level);
        }

        return $this;
    }

    /**
     * Add query part to select a specific subbranch of tree
     * @param \BackBuilder\NestedNode\Page $page  the parent page
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andParentIs(Page $page = null)
    {
        if (null === $page) {
            return $this->andWhere($this->getSectionAlias() . '._parent IS NULL');
        }

        if (false === $page->hasMainSection()) {
            return $this->andWhere('1 = 0');
        }

        $suffix = $this->getSuffix();
        return $this->andWhere($this->getSectionAlias() . '._parent = :parent' . $suffix)
                        ->andWhere($this->getAlias() . ' != :page' . $suffix)
                        ->setParameter('page' . $suffix, $page)
                        ->setParameter('parent' . $suffix, $page->getSection());
    }

    /**
     * Add query part to select siblings of page
     * @param \BackBuilder\NestedNode\Page $page
     * @param boolean $strict       if TRUE, $node is exclude
     * @param array $order          ordering spec ( array($field => $sort) )
     * @param int $limit            max number of results
     * @param int $start            first result index
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsSiblingsOf(Page $page, $strict = false, array $order = null, $limit = null, $start = 0)
    {
        if (true === $page->isRoot()) {
            $this->andParentIs(null);
        } else {
            $this->andIsDescendantOf($page->getParent(), false, $page->getLevel());
        }

        if (true === $strict) {
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias() . ' != :page' . $suffix)
                    ->setParameter('page' . $suffix, $page);
        }

        if (null !== $order) {
            $this->addMultipleOrderBy($order);
        }

        if (null !== $limit) {
            $this->setMaxResults($limit)
                    ->setFirstResult($start);
        }

        return $this;
    }

    /**
     * Add query part to select descendants of $page
     * @param \BackBuilder\NestedNode\Page $page
     * @param boolean $strict   If TRUE, $node is excluded from the selection
     * @param int $depth        Filter ancestors by their level
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andIsDescendantOf(Page $page, $strict = false, $depth = null)
    {
        $suffix = $this->getSuffix();
        $this->andWhere($this->getSectionAlias() . '._root = :root' . $suffix)
                ->andWhere($this->expr()->between($this->getSectionAlias() . '._leftnode', $page->getSection()->getLeftnode(), $page->getSection()->getRightnode()))
                ->setParameter('root' . $suffix, $page->getSection()->getRoot());

        if (true === $strict) {
            $this->andWhere($this->getAlias() . ' != :page' . $suffix)
                    ->setParameter('page' . $suffix, $page);
        }

        if (null !== $depth) {
            $this->andWhere($this->getAlias() . '._level <= :level' . $suffix)
                    ->setParameter('level' . $suffix, $depth);
        }

        return $this;
    }

    /**
     * Add query part to select page having specific states
     * @param mixed $states       one or several states to test
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsIn($states)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        $suffix = $this->getSuffix();
        return $this->andWhere($this->getAlias() . '._state IN(:states' . $suffix . ')')
                        ->setParameter('states' . $suffix, $states);
    }

    /**
     * Add query part to select page having not specific states
     * @param  mixed                                               $states one or several states to test
     * @param  string                                              $alias  optional, the alias to use
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function andStateIsNotIn($states)
    {
        if (false === is_array($states)) {
            $states = array($states);
        }

        $suffix = $this->getSuffix();
        return $this->andWhere($this->getAlias() . '._state NOT IN(:states' . $suffix . ')')
                        ->setParameter('states' . $suffix, $states);
    }

    /**
     * Add query part to select page matching provided criteria
     * @param array $restrictedStates   optional, limit to pages having provided states, empty by default
     * @param array $options            optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                                       'afterPubdateField' => timestamp against page._modified,
     *                                                                       'searchField' => string to search for title
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     * @Todo: more generic search function
     */
    public function andSearchCriteria($restrictedStates = array(), $options = array())
    {
        if (true === is_array($restrictedStates) && 0 < count($restrictedStates) && false === in_array('all', $restrictedStates)) {
            $this->andStateIsIn($restrictedStates);
        }

        if (false === is_array($options)) {
            $options = array();
        }

        if (true === array_key_exists('beforePubdateField', $options)) {
            $date = new \DateTime();
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias() . '._modified < :date' . $suffix)
                    ->setParameter('date' . $suffix, $date->setTimestamp($options['beforePubdateField']));
        }

        if (true === array_key_exists('afterPubdateField', $options)) {
            $date = new \DateTime();
            $suffix = $this->getSuffix();
            $this->andWhere($this->getAlias() . '._modified > :date' . $suffix)
                    ->setParameter('date' . $suffix, $date->setTimestamp($options['afterPubdateField']));
        }

        if (true === array_key_exists('searchField', $options)) {
            $this->andWhere($this->expr()->like($this->getAlias() . '._title', $this->expr()->literal('%' . $options['searchField'] . '%')));
        }

        return $this;
    }

    /**
     * Add query part to math provided criteria
     * @param array $criteria
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function addSearchCriteria(array $criteria)
    {
        $suffix = $this->getSuffix();
        foreach ($criteria as $crit => $value) {
            if (false === strpos($crit, '.')) {
                $crit = (true === in_array($crit, self::$join_criteria) ? $this->getSectionAlias() : $this->getAlias()) . '.' . $crit;
            }

            $param = str_replace('.', '_', $crit) . $suffix;
            $this->andWhere($crit . ' IN (:' . $param . ')')
                    ->setParameter($param, $value);
        }

        return $this;
    }

    /**
     * Adds an ordering to the query results.
     * @param string|Expr\OrderBy $sort  The ordering expression.
     * @param string              $order The ordering direction.
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function addOrderBy($sort, $order = null)
    {
        if (false !== strpos($sort, '.')) {
            return parent::addOrderBy($sort, $order);
        }

        if (true === in_array($sort, self::$join_criteria)) {
            $sort = $this->getSectionAlias() . '.' . $sort;
        } else if (0 !== strpos($this->getAlias() . '.', $sort)) {
            $sort = $this->getAlias() . '.' . $sort;
        }

        return parent::addOrderBy($sort, $order);
    }

    /**
     * Add several ordering criteria by array
     * @param array $criteria       optional, the ordering criteria ( array('_leftnode' => 'asc') by default )
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function addMultipleOrderBy(array $criteria = array('_position' => 'ASC'))
    {
        if (true === empty($criteria)) {
            $criteria = array('_position' => 'ASC');
        }

        foreach ($criteria as $sort => $order) {
            $this->addOrderBy($sort, $order);
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
