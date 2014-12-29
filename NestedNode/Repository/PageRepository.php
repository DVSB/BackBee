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

use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\Exception\InvalidArgumentException;
use BackBuilder\NestedNode\Section;
use BackBuilder\NestedNode\Page;
use BackBuilder\Security\Token\BBUserToken;
use BackBuilder\Site\Layout;
use BackBuilder\Site\Site;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Page repository
 *
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageRepository extends EntityRepository
{

    /**
     * Creates a new Page QueryBuilder instance that is prepopulated for this entity name.
     * @param  string                                              $alias   the alias to use
     * @param  string                                              $indexBy optional, the index to use for the query
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $qb = new PageQueryBuilder($this->_em);
        return $qb->select($alias)->from($this->_entityName, $alias, $indexBy);
    }

    /**
     * Finds entities by a set of criteria with automatic join on section if need due to retro-compatibility
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if (
                false === PageQueryBuilder::hasJoinCriteria($criteria) &&
                false === PageQueryBuilder::hasJoinCriteria($orderBy)
        ) {
            return parent::findBy($criteria, $orderBy, $limit, $offset);
        }

        $query = $this->createQueryBuilder('p')
                        ->addSearchCriteria($criteria);

        if (false === empty($orderBy)) {
            $query->addMultipleOrderBy($orderBy);
        }

        return $query->setMaxResults($limit)
                        ->setFirstResult($offset)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Finds a single entity by a set of criteria with automatic join on section if need due to retro-compatibility
     * @param array $criteria
     * @param array|null $orderBy
     * @return \BackBuilder\NestedNode\Page|null The page instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if (
                false === PageQueryBuilder::hasJoinCriteria($criteria) &&
                false === PageQueryBuilder::hasJoinCriteria($orderBy)
        ) {
            return parent::findOneBy($criteria, $orderBy);
        }

        $query = $this->createQueryBuilder('p')
                ->addSearchCriteria($criteria);

        if (false === empty($orderBy)) {
            $query->addMultipleOrderBy($orderBy);
        }

        return $query->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the ancestor at level $level of the provided page
     * @param \BackBuilder\NestedNode\Page $page
     * @param int $level
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getAncestor(Page $page, $level = 0)
    {
        if ($page->getLevel() < $level) {
            return null;
        }

        if ($page->getLevel() === $level) {
            return $page;
        }

        return $this->createQueryBuilder('p')
                        ->andIsAncestorOf($page, false, $level)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the ancestors of the provided page
     * @param \BackBuilder\NestedNode\Page $page
     * @param int     $depth        Returns only ancestors from $depth number of generation
     * @param boolean $includeNode  Returns also the node itsef if TRUE
     * @return array
     */
    public function getAncestors(Page $page, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('p')
                ->andIsAncestorOf($page, !$includeNode, null === $depth ? null : $page->getLevel() - $depth);

        $results = $q->orderBy($q->getSectionAlias() . '._leftnode', 'asc')
                ->getQuery()
                ->getResult();

        if (true === $includeNode && false === $page->hasMainSection()) {
            $results[] = $page;
        }

        return $results;
    }

    /**
     * Returns the previous online sibling of $page
     * @param  \BackBuilder\NestedNode\Page      $page the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getOnlinePrevSibling(Page $page)
    {
        $query = $this->createQueryBuilder('p')
                ->andIsSiblingsOf($page, true, array('_leftnode' => 'DESC', '_position' => 'DESC'), 1, 0)
                ->andIsOnline();

        if (true === $page->hasMainSection()) {
            $query->andIsSection()
                    ->andWhere($query->getSectionAlias() . '._leftnode < :leftnode')
                    ->setParameter('leftnode', $page->getLeftnode());
        } else {
            $qOR = $query->expr()->orX();
            $qOR->add('p._section != p')
                    ->add($query->getSectionAlias() . '._parent = :parent');

            $query->andWhere('p._position < :position')
                    ->andWhere($qOR)
                    ->setParameter('position', $page->getPosition())
                    ->setParameter('parent', $page->getParent()->getSection());
        }

        return $query->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the online siblings having layout $layout of the provided page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  \BackBuilder\Site\Layout       $layout      the layout to look for
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @param  array                          $order       optional, the ordering criteria ( array($field => $sort) )
     * @param  int                            $limit       optional, the maximum number of results
     * @param  int                            $start       optional, the first result index (0 by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getOnlineSiblingsByLayout(Page $page, Layout $layout, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
                        ->andIsSiblingsOf($page, !$includeNode, $order, $limit, $start)
                        ->andIsOnline()
                        ->andWhere('p._layout = :layout')
                        ->setParameter('layout', $layout)
                        ->andWhere('p._level = :level')
                        ->setParameter('level', $page->getLevel())
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the next online sibling of $page
     * @param  \BackBuilder\NestedNode\Page      $page the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getOnlineNextSibling(Page $page)
    {
        $query = $this->createQueryBuilder('p');

        if (true === $page->hasMainSection()) {
            $query->andWhere($query->getSectionAlias() . '._leftnode >= :leftnode')
                    ->orWhere('p._section IN (:sections)')
                    ->setParameter('leftnode', $page->getLeftnode())
                    ->setParameter('sections', array($page->getSection(), $page->getSection()->getParent()));
        } else {
            $query->andWhere('p._position > :position')
                    ->setParameter('position', $page->getPosition());
        }

        return $query->andIsSiblingsOf($page, true, array('_position' => 'ASC', '_leftnode' => 'ASC'), 1, 0)
                        ->andIsOnline()
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Inserts a page in a tree at first position
     * @param \BackBuilder\NestedNode\Page $page     The page to be inserted
     * @param \BackBuilder\NestedNode\Page $parent   The parent node
     * @param boolean $section                       If TRUE, the page is inserted with a section
     * @return \BackBuilder\NestedNode\Page          The inserted page
     */
    public function insertNodeAsFirstChildOf(Page $page, Page $parent, $section = false)
    {
        return $this->insertNode($page, $parent, 1, $section);
    }

    /**
     * Inserts a page in a tree at last position
     * @param \BackBuilder\NestedNode\Page $page     The page to be inserted
     * @param \BackBuilder\NestedNode\Page $parent   The parent node
     * @param boolean $section                       If TRUE, the page is inserted with a section
     * @return \BackBuilder\NestedNode\Page          The inserted page
     */
    public function insertNodeAsLastChildOf(Page $page, Page $parent, $section = false)
    {
        return $this->insertNode($page, $parent, $this->getMaxPosition($parent) + 1, $section);
    }

    /**
     * Inserts a page in a tree
     * @param \BackBuilder\NestedNode\Page $page     The page to be inserted
     * @param \BackBuilder\NestedNode\Page $parent   The parent node
     * @param int $position                          The position of the inserted page
     * @param boolean $section                       If TRUE, the page is inserted with a section
     * @return \BackBuilder\NestedNode\Page          The inserted page
     * @throws BackBuilder\Exception\InvalidArgumentException  Occures if parent page is not a section
     */
    public function insertNode(Page $page, Page $parent, $position, $section = false)
    {
        if (false === $parent->hasMainSection()) {
            throw new InvalidArgumentException('Parent page is not a section.');
        }

        $page->setSection($parent->getSection())
                ->setPosition($position)
                ->setLevel($parent->getSection()->getLevel() + 1);

        if (true === $section) {
            $page = $this->saveWithSection($page);
        } else {
            $this->shiftPosition($page, 1, true);
        }

        return $page;
    }

    /**
     * Returns default ordering criteria for descendants if none provided
     * @param int $depth    Optional, limit to $depth number of generation
     * @param array $order  Optional, the ordering criteria ( array() by default )
     * @return array        If none ordering criteria provided and only one descendant generation is requested
     *                      the result will be array('_position' => 'ASC', '_leftnode' => 'ASC')
     *                      If none ordering criteria provided and several generations requested the result
     *                      will be array('_leftnode' => 'ASC', '_level' => 'ASC', '_position' => 'ASC')
     *                      Elsewhere $order
     */
    private function getOrderingDescendants($depth = null, $order = array())
    {
        if (1 === $depth && true === empty($order)) {
            $order = array(
                '_position' => 'ASC',
                '_leftnode' => 'ASC',
            );
        } elseif (true === empty($order)) {
            $order = array(
                '_leftnode' => 'ASC',
                '_level' => 'ASC',
                '_position' => 'ASC',
            );
        }

        return $order;
    }

    /**
     * Returns the not deleted descendants of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param type $depth                           optional, limit to $depth number of generation
     * @param type $includeNode                     optional, include $page in results if TRUE (false by default)
     * @param type $order                           optional, the ordering criteria ( array() by default )
     * @param type $paginate                        optional, if TRUE return a paginator rather than an array (false by default)
     * @param type $start                           optional, if paginated set the first result index (0 by default)
     * @param type $limit                           optional, if paginated set the maxmum number of results (25 by default)
     * @param type $limitToSection                  optional, limit to descendants having child (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getDescendants(Page $page, $depth = null, $includeNode = false, $order = array(), $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $query = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, !$includeNode, $depth, $this->getOrderingDescendants($depth, $order), (true === $paginate) ? $limit : null, $start, $limitToSection);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns the online descendants of $page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  int                            $depth       optional, limit to $depth number of generation
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getOnlineDescendants(Page $page, $depth = null, $includeNode = false, $order = array(), $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $query = $this->createQueryBuilder('p')
                ->andIsOnline()
                ->andIsDescendantOf($page, !$includeNode, $depth, $this->getOrderingDescendants($depth, $order), (true === $paginate) ? $limit : null, $start, $limitToSection);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns the visible (ie online and not hidden) descendants of $page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  int                            $depth       optional, limit to $depth number of generation
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getVisibleDescendants(Page $page, $depth = null, $includeNode = false, $order = array(), $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $query = $this->createQueryBuilder('p')
                ->andIsVisible()
                ->andIsDescendantOf($page, !$includeNode, $depth, $this->getOrderingDescendants($depth, $order), (true === $paginate) ? $limit : null, $start, $limitToSection);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns the visible (ie online and not hidden) children of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param int $depth                            optional, limit to $depth number of generation
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @return \BackBuilder\NestedNode\Page[]
     * @deprecated since version 0.11
     */
    public function getVisibleDescendantsFromParent(Page $page, $depth = null, $includeNode = false)
    {
        return $this->getVisibleDescendants($page, 1, $includeNode);
    }

    /**
     * Returns the visible (ie online and not hidden) descendants of $page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  int                            $depth       optional, limit to $depth number of generation
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getNotDeletedDescendants(Page $page, $depth = null, $includeNode = false, $order = array(), $paginate = false, $start = 0, $limit = 25, $limitToSection = false)
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $query = $this->createQueryBuilder('p')
                ->andIsNotDeleted()
                ->andIsDescendantOf($page, !$includeNode, $depth, $this->getOrderingDescendants($depth, $order), (true === $paginate) ? $limit : null, $start, $limitToSection);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Move page as first child of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function moveAsFirstChildOf(Page $page, Page $target)
    {
        return $this->moveAsChildOf($page, $target, true);
    }

    /**
     * Move page as last child of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function moveAsLastChildOf(Page $page, Page $target)
    {
        return $this->moveAsChildOf($page, $target, false);
    }

    /**
     * Move page as child of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_first Move page as first child of $target if TRUE, last child elsewhere
     * @return \BackBuilder\NestedNode\Page
     * @throws BackBuilder\Exception\InvalidArgumentException  Occures if target page is not a section
     */
    private function moveAsChildOf(Page $page, Page $target, $as_first = true)
    {
        if (false === $target->hasMainSection()) {
            throw new InvalidArgumentException('Cannot move page into a non-section page.');
        }

        if (false === $page->hasMainSection()) {
            $this->movePageAsChildOf($page, $target, $as_first);
        } else {
            $this->moveSectionAsChildOf($page, $target, $as_first);
        }

        return $page->setParent($target)
                        ->setLevel($target->getLevel() + 1);
    }

    /**
     * Move a non-section page as child of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_first Move page as first child of $target if TRUE, last child elsewhere
     * @return \BackBuilder\NestedNode\Page
     */
    private function movePageAsChildOf(Page $page, Page $target, $as_first = true)
    {
        if (true === $as_first) {
            return $this->shiftPosition($page, -1, true)
                            ->insertNodeAsFirstChildOf($page, $target);
        } else {
            return $this->shiftPosition($page, -1, true)
                            ->insertNodeAsLastChildOf($page, $target);
        }
    }

    /**
     * Move a section page as child of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_first Move page as first child of $target if TRUE, last child elsewhere
     * @return \BackBuilder\NestedNode\Page
     */
    private function moveSectionAsChildOf(Page $page, Page $target, $as_first = true)
    {
        $delta = $target->getLevel() - $page->getLevel() + 1;
        $this->shiftLevel($page, $delta);

        if (true === $as_first) {
            $this->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Section')
                    ->moveAsFirstChildOf($page->getSection(), $target->getSection());
        } else {
            $this->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Section')
                    ->moveAsLastChildOf($page->getSection(), $target->getSection());
        }

        return $page;
    }

    /**
     * Move a page as previous sibling of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function moveAsPrevSiblingOf(Page $page, Page $target)
    {
        return $this->moveAsSiblingOf($page, $target, true);
    }

    /**
     * Move a page as next sibling of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function moveAsNextSiblingOf(Page $page, Page $target)
    {
        return $this->moveAsSiblingOf($page, $target, false);
    }

    /**
     * Move a page as sibling of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_previous Move page as previous sibling of $target if TRUE, next sibling elsewhere
     * @return \BackBuilder\NestedNode\Page
     * @throws BackBuilder\Exception\InvalidArgumentException  Occures if $target is a root
     */
    private function moveAsSiblingOf(Page $page, Page $target, $as_previous = true)
    {
        if (true === $target->isRoot()) {
            throw new InvalidArgumentException('Cannot move a page as sibling of a root.');
        }

        if (false === $page->hasMainSection()) {
            $this->movePageAsSiblingOf($page, $target, $as_previous);
        } else {
            $this->moveSectionAsSiblingOf($page, $target, $as_previous);
        }

        return $page->setParent($target->getParent());
    }

    /**
     * Move a non-section page as sibling of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_previous Move page as previous sibling of $target if TRUE, next sibling elsewhere
     * @return \BackBuilder\NestedNode\Page
     * @throws BackBuilder\Exception\InvalidArgumentException  Occures if target page is a section
     */
    private function movePageAsSiblingOf(Page $page, Page $target, $as_previous = true)
    {
        if (true === $target->hasMainSection()) {
            throw new InvalidArgumentException('Cannot move a non-section page as sibling of a section page.');
        }

        $this->shiftPosition($page, -1, true);
        $this->_em->refresh($target);

        if (true === $as_previous) {
            $page->setPosition($target->getPosition());
            $this->shiftPosition($target, 1);
        } else {
            $page->setPosition($target->getPosition() + 1);
            $this->shiftPosition($target, 1, true);
        }

        return $page;
    }

    /**
     * Move a section page as sibling of $target
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\NestedNode\Page $target
     * @param boolean $as_previous Move page as previous sibling of $target if TRUE, next sibling elsewhere
     * @return \BackBuilder\NestedNode\Page
     * @throws BackBuilder\Exception\InvalidArgumentException  Occures if target page is not a section
     */
    private function moveSectionAsSiblingOf(Page $page, Page $target, $as_previous = true)
    {
        if (false === $target->hasMainSection()) {
            throw new InvalidArgumentException('Cannot move a section page as sibling of a non-section page.');
        }

        $delta = $page->getLevel() - $target->getLevel();
        $this->shiftLevel($page, $delta);

        if (true === $as_previous) {
            $this->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Section')
                    ->moveAsPrevSiblingOf($page->getSection(), $target->getSection());
        } else {
            $this->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Section')
                    ->moveAsNextSiblingOf($page->getSection(), $target->getSection());
        }

        return $page;
    }

    /**
     * Returns the root page for $site
     * @param \BackBuilder\Site\Site $site   the site to test
     * @param array $restrictedStates        optional, limit to pages having provided states
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getRoot(Site $site, array $restrictedStates = array())
    {
        $q = $this->createQueryBuilder('p')
                ->andParentIs(null)
                ->orderby('p._position', 'asc')
                ->setMaxResults(1);

        if (0 < count($restrictedStates)) {
            $q->andStateIsIn($restrictedStates);
        }

        return $q->andWhere($q->getSectionAlias() . '._site = :site')
                        ->setParameter('site', $site)
                        ->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns an array of online children of $page
     * @param  \BackBuilder\NestedNode\Page   $page       the parent page
     * @param  int                            $maxResults optional, the maximum number of results
     * @param  array                          $order      optional, the ordering criteria (array('_leftnode', 'asc') by default)
     * @return \BackBuilder\NestedNode\Page[]
     * @deprecated since version 0.11
     */
    public function getOnlineChildren(Page $page, $maxResults = null, array $order = array('_leftnode', 'asc'))
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $query = $this->createQueryBuilder('p')
                ->andIsOnline()
                ->andIsDescendantOf($page, true, 1, $this->getOrderingDescendants(1, null), $maxResults, 0, false);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns an array of children of $page
     * @param  \BackBuilder\NestedNode\Page                   $page             the parent page
     * @param  string                                         $order_sort       optional, the sort field, title by default
     * @param  string                                         $order_dir        optional, the sort direction, asc by default
     * @param  string                                         $paging           optional, the paging criteria: array('start' => xx, 'limit' => xx), empty by default
     * @param  array                                          $restrictedStates optional, limit to pages having provided states, empty by default
     * @param  array                                          $options          optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                                          'afterPubdateField' => timestamp against page._modified,
     *                                                                          'searchField' => string to search for title
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator Returns Paginaor is paging criteria provided, array otherwise
     * @deprecated since version 0.11
     */
    public function getChildren(Page $page, $order_sort = '_title', $order_dir = 'asc', $paging = array(), $restrictedStates = array(), $options = array())
    {
        if (true === $page->isLeaf()) {
            return array();
        }

        $paginate = (is_array($paging) && array_key_exists('start', $paging) && array_key_exists('limit', $paging));
        $query = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, true, 1, array($order_sort => $order_dir), $paginate ? $paging['limit'] : null, $paginate ? $paging['start'] : 0, false)
                ->andSearchCriteria($restrictedStates, $options);

        if (true === $paginate) {
            return new Paginator($query);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Returns count of children of $page
     * @param  \BackBuilder\NestedNode\Page $page             the parent page
     * @param  array                        $restrictedStates optional, limit to pages having provided states, empty by default
     * @param  array                        $options          optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                        'afterPubdateField' => timestamp against page._modified,
     *                                                        'searchField' => string to search for title
     * @return int                          the children count
     * @deprecated since version 0.11
     */
    public function countChildren(Page $page, $restrictedStates = array(), $options = array())
    {
        if (true === $page->isLeaf()) {
            return 0;
        }

        return $this->createQueryBuilder('p')
                        ->select("COUNT(p)")
                        ->andIsDescendantOf($page, true, 1, array($order_sort => $order_dir), null, 0, false)
                        ->andSearchCriteria($restrictedStates, $options)
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    /**
     * Sets state of $page and is descendant to STATE_DELETED
     * @param  \BackBuilder\NestedNode\Page $page the page to delete
     * @return integer                      the number of page having their state changed
     */
    public function toTrash(Page $page)
    {
        if (true === $page->isLeaf()) {
            $page->setState(Page::STATE_DELETED);
            $this->getEntityManager()->flush($page);
            return 1;
        }

        $subquery = $this->getEntityManager()
                ->getRepository('BackBuilder\NestedNode\Section')
                ->createQueryBuilder('n')
                ->select('n._uid')
                ->andIsDescendantOf($page->getSection());

        return $this->createQueryBuilder('p')
                        ->update()
                        ->set('p._state', Page::STATE_DELETED)
                        ->andWhere('p._section IN (' . $subquery->getDQL() . ')')
                        ->setParameters($subquery->getParameters())
                        ->getQuery()
                        ->execute();
    }

    /**
     * Copy a page to a new one
     * @param \BackBuilder\NestedNode\Page      $page           The page to be copied
     * @param string                            $title          Optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page      $parent         Optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                     The copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException  Occures if the page is deleted
     */
    private function copy(Page $page, $title = null, Page $parent = null)
    {
        if (Page::STATE_DELETED & $page->getState()) {
            throw new InvalidArgumentException('Cannot duplicate a deleted page.');
        }

        // Cloning the page
        $new_page = clone $page;
        $new_page->setTitle((null === $title) ? $page->getTitle() : $title)
                ->setLayout($page->getLayout());

        // Setting the clone as first child of the parent
        if (null !== $parent) {
            $new_page = $this->insertNodeAsFirstChildOf($new_page, $parent, $new_page->hasMainSection());
        }

        // Persisting entities
        $this->_em->persist($new_page);

        return $new_page;
    }

    /**
     * Replace subcontent of ContentSet by their clone if exist
     * @param \BackBuilder\ClassContent\AClassContent   $content        The cloned content
     * @param array                                     $cloning_datas  The cloned data array
     * @param \BackBuilder\Security\Token\BBUserToken   $token          Optional, the BBuser token to allow the update of revisions
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function updateRelatedPostCloning(AClassContent $content, array $cloning_datas, BBUserToken $token = null)
    {
        if (
                $content instanceof ContentSet &&
                true === array_key_exists('pages', $cloning_datas) &&
                true === array_key_exists('contents', $cloning_datas) &&
                0 < count($cloning_datas['pages']) &&
                0 < count($cloning_datas['contents'])
        ) {
            // reading copied elements
            $copied_pages = array_keys($cloning_datas['pages']);
            $copied_contents = array_keys($cloning_datas['contents']);

            // Updating subcontent if needed
            foreach ($content as $subcontent) {
                if (false === $this->_em->contains($subcontent)) {
                    $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
                }

                if (
                        null !== $subcontent->getMainNode() && 
                        true === in_array($subcontent->getMainNode()->getUid(), $copied_pages) && 
                        true === in_array($subcontent->getUid(), $copied_contents)
                ) {
                    // Loading draft for content
                    if (
                            null !== $token &&
                            (null !== $draft = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true))
                        ) {
                        $content->setDraft($draft);
                    }
                    $content->replaceChildBy($subcontent, $cloning_datas['contents'][$subcontent->getUid()]);
                }
            }
        }
        return $this;
    }

    /**
     * Update mainnode of the content if need during clonage
     * @param \BackBuilder\ClassContent\AClassContent   $content        The cloned content
     * @param array                                     $cloning_pages  The cloned pages array
     * @param \BackBuilder\Security\Token\BBUserToken   $token          Optional, the BBuser token to allow the update of revisions
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function updateMainNodePostCloning(AClassContent $content, array $cloning_pages, BBUserToken $token = null)
    {
        $mainnode = $content->getMainNode();

        if (
                null !== $mainnode && 
                0 < count($cloning_pages) && 
                true === in_array($mainnode->getUid(), array_keys($cloning_pages))
            ) {
            // Loading draft for content
            if (
                    null !== $token &&
                    (null !== $draft = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true))
                ) {
                $content->setDraft($draft);
            }
            $content->setMainNode($cloning_pages[$mainnode->getUid()]);
        }

        return $this;
    }

    /**
     * Duplicate a page and its descendants
     * @param \BackBuilder\NestedNode\Page  $page               The page to be duplicated
     * @param string                        $title              Optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page  $parent             Optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                     The copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException  Occures if the page is recursively duplicated in itself
     */
    private function duplicateRecursively(Page $page, $title = null, Page $parent = null)
    {
        if (null !== $parent && true === $parent->isDescendantOf($page)) {
            throw new InvalidArgumentException('Cannot recursively duplicate a page in itself');
        }

        // Storing current children before clonage
        $children = $this->getDescendants($page, 1);

        // Cloning the page
        $new_page = $this->copy($page, $title, $parent);
        $this->_em->flush($new_page);

        // Cloning children
        foreach (array_reverse($children) as $child) {
            if (!(Page::STATE_DELETED & $child->getState())) {
                $new_child = $this->duplicateRecursively($child, null, $new_page);
                $new_page->cloning_datas = array_merge_recursive($new_page->cloning_datas, $new_child->cloning_datas);
            }
        }

        return $new_page;
    }

    /**
     * Duplicate a page and optionnaly its descendants
     * @param \BackBuilder\NestedNode\Page              $page        The page to be duplicated
     * @param string                                    $title       Optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page              $parent      Optional, the parent of the copy, by default the parent of the page
     * @param boolean                                   $recursive   Optional, if true (by default) duplicates recursively the descendants of the page
     * @param \BackBuilder\Security\Token\BBUserToken   $token       Optional, the BBuser token to allow the update of revisions
     * @return \BackBuilder\NestedNode\Page                          The copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException       Occures if the page is recursively duplicated in itself
     */
    public function duplicate(Page $page, $title = null, Page $parent = null, $recursive = true, BBUserToken $token = null)
    {
        if (false === $recursive || false === $page->hasMainSection()) {
            $new_page = $this->copy($page, $title, $parent);
        } else {
            // Recursive cloning
            $new_page = $this->duplicateRecursively($page, $title, $parent, $token);
        }

        // Finally updating contentset and mainnode
        foreach ($new_page->cloning_datas['contents'] as $content) {
            $this->updateRelatedPostCloning($content, $new_page->cloning_datas, $token)
                    ->updateMainNodePostCloning($content, $new_page->cloning_datas['pages'], $token);
        }

        return $new_page;
    }

    /**
     * Removes page with no contentset for $site
     * @param \BackBuilder\Site\Site $site
     * @codeCoverageIgnore
     * @Todo: what if the deleted page has chldren ?
     */
    public function removeEmptyPages(Site $site)
    {
        $q = $this->createQueryBuilder('p')
            ->select()
            ->andWhere('p._contentset IS NULL')
            ->andWhere('p._site = :site')
            ->orderBy('p._leftnode', 'desc')
            ->setParameter('site', $site)
        ;
        foreach ($q->getQuery()->execute() as $page) {
            $this->delete($page);
        }
    }

    /**
     * Saves a page with a section and returns it
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\NestedNode\Page
     */
    public function saveWithSection(Page $page)
    {
        if (true === $page->hasMainSection()) {
            return $page;
        }

        if (false === $this->_em->contains($page)) {
            $this->_em->persist($page);
        }

        $parent = $page->getSection();
        $section = new Section($page->getUid(), array('page' => $page, 'site' => $page->getSite()));

        $this->getEntityManager()
                ->getRepository('BackBuilder\NestedNode\Section')
                ->insertNodeAsFirstChildOf($section, $parent);

        return $page->setPosition(0)
                    ->setLevel($section->getLevel());
    }

    /**
     * Shift position values for pages siblings of and after $page by $delta
     * @param Page $page
     * @param int $delta        The shift value of position
     * @param boolean $strict   Does $page is include (TRUE) or not (FALSE)
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function shiftPosition(Page $page, $delta, $strict = false)
    {
        if (true === $page->hasMainSection()) {
            return $this;
        }

        $query = $this->createQueryBuilder('p')
                ->set('p._position', 'p._position + :delta_node')
                ->andWhere('p._section = :section')
                ->andWhere('p._position >= :position')
                ->setParameters(array(
            'delta_node' => $delta,
            'section' => $page->getSection(),
            'position' => $page->getPosition()
        ));

        if (true === $strict) {
            $query->andWhere('p != :page')
                    ->setParameter('page', $page);
        } else {
            $page->setPosition($page->getPosition() + $delta);
        }

        $query->update()
                ->getQuery()
                ->execute();

        return $this;
    }

    /**
     * Shift level values for pages descendants of $page by $delta
     * @param Page $page
     * @param int $delta        The shift value of level
     * @param boolean $strict   Does $page is include (TRUE) or not (FALSE)
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function shiftLevel(Page $page, $delta, $strict = false)
    {
        if (false === $page->hasMainSection() && true === $strict) {
            return $this;
        }

        $query = $this->createQueryBuilder('p')
                ->update()
                ->set('p._level', 'p._level + :delta');

        if (true === $page->hasMainSection()) {
            $subquery = $this->getEntityManager()
                    ->getRepository('BackBuilder\NestedNode\Section')
                    ->createQueryBuilder('n')
                    ->select('n._uid')
                    ->andIsDescendantOf($page->getSection());

            $query->andWhere('p._section IN (' . $subquery->getDQL() . ')')
                    ->setParameters($subquery->getParameters());

            if (true === $strict) {
                $query->andWhere('p <> :page')
                        ->setParameter('page', $page);
            }
        } else {
            $query->andWhere('p = :page')
                    ->setParameter('page', $page);
        }

        $query->setParameter('delta', $delta)->getQuery()
                ->execute();

        return $this;
    }

    /**
     * Returns the maximum position of children of $page
     * @param Page $page
     * @return int
     */
    private function getMaxPosition(Page $page)
    {
        if (false === $page->hasMainSection()) {
            return 0;
        }

        $query = $this->createQueryBuilder('p');
        $max = $query->select($query->expr()->max('p._position'))
                ->andParentIs($page)
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return (null === $max) ? 0 : $max;
    }

}
