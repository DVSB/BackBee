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
            $query->andWhere('p._position < :position')
                    ->setParameter('position', $page->getPosition());
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
     */
    public function insertNode(Page $page, Page $parent, $position, $section = false)
    {
        if (false === $parent->hasMainSection()) {
            throw new InvalidArgumentException('Parent page is not a section');
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
     * Returns the visible (ie online and not hidden) descendants of $page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  int                            $depth       optional, limit to $depth number of generation
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getVisibleDescendants(Page $page, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('p')
            ->andIsDescendantOf($page, !$includeNode)
            ->andIsVisible()
            ->orderBy('p._leftnode', 'asc')
        ;

        if (null !== $depth) {
            $q->andLevelIsLowerThan($page->getLevel() + $depth);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns the visible (ie online and not hidden) siblings of the provided page
     * @param  \BackBuilder\NestedNode\Page   $page        the page to look for
     * @param  boolean                        $includeNode optional, include $page in results if TRUE (false by default)
     * @param  array                          $order       optional, the ordering criteria ( array($field => $sort) )
     * @param  int                            $limit       optional, the maximum number of results
     * @param  int                            $start       optional, the first result index (0 by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getVisibleSiblings(Page $page, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
            ->andIsVisibleSiblingsOf($page, !$includeNode, $order, $limit, $start)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns the previous visible (ie online and not hidden) sibling of $page
     * @param  \BackBuilder\NestedNode\Page      $page the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getVisiblePrevSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
            ->andIsPreviousVisibleSiblingOf($page)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Moves $page as child of $parent by default at last position or, optionaly, before node having uid = $next_uid
     * @param  \BackBuilder\NestedNode\Page $page     the page to move
     * @param  \BackBuilder\NestedNode\Page $parent   the page parent to move in
     * @param  string                       $next_uid optional, the uid of the next sibling
     * @return \BackBuilder\NestedNode\Page the moved page
     */
    public function movePageInTree(Page $page, Page $parent, $next_uid = null)
    {
        $next = ($next_uid !== null) ? $this->find($next_uid) : null;

        if (null !== $next && $next->getParent() === $parent) {
            return $this->moveAsPrevSiblingOf($page, $next);
        }

        return $this->moveAsLastChildOf($page, $parent);
    }

    /**
     * Replaces the ContentSet of $page
     * @param  \BackBuilder\NestedNode\Page         $page          the page to change
     * @param  \BackBuilder\ClassContent\ContentSet $oldContentSet the contentset to replace
     * @param  \BackBuilder\ClassContent\ContentSet $newContentSet the new contentset
     * @return \BackBuilder\ClassContent\ContentSet the inserted contentset
     */
    public function replaceRootContentSet(Page $page, ContentSet $oldContentSet, ContentSet $newContentSet)
    {
        try {
            $result = $page->replaceRootContentSet($oldContentSet, $newContentSet);

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the previous visible sibling of $page
     * @param  \BackBuilder\NestedNode\Page      $page the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getVisibleNextSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
            ->andIsNextVisibleSiblingOf($page)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Returns the not deleted descendants of $page
     * @param  \BackBuilder\NestedNode\Page                                            $page         the page to look for
     * @param  type                                                                    $depth        optional, limit to $depth number of generation
     * @param  type                                                                    $includeNode  optional, include $page in results if TRUE (false by default)
     * @param  type                                                                    $order        optional, the ordering criteria ( array('_leftnode' => 'asc') by default )
     * @param  type                                                                    $paginate     optional, if TRUE return a paginator rather than an array (false by default)
     * @param  type                                                                    $firstresult  optional, if paginated set the first result index (0 by default)
     * @param  type                                                                    $maxresults   optional, if paginated set the maxmum number of results (25 by default)
     * @param  type                                                                    $having_child optional, limit to descendants having child (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getNotDeletedDescendants(Page $page, $depth = null, $includeNode = false, array $order = array('_leftnode' => 'asc'), $paginate = false, $firstresult = 0, $maxresults = 25, $having_child = false)
    {
        // @Todo: search for calls with wrong ordering criteria format and solve them
        if (true === array_key_exists('field', $order)) {
            if ('_' !== substr($order['field'], 0, 1)) {
                $order['field'] = '_'.$order['field'];
            }

            $order = array($order['field'] => (true === array_key_exists('sort', $order) ? $order['sort'] : 'asc'));
        }

        $q = $this->createQueryBuilder('p')
            ->andIsDescendantOf($page, !$includeNode)
            ->andStateIsLowerThan(Page::STATE_DELETED)
            ->orderByMultiple($order)
        ;

        if (null !== $depth) {
            $q->andLevelIsLowerThan($page->getLevel() + $depth);
        }

        if (true === $having_child) {
            $q->andWhere('p._rightnode > (p._leftnode + 1)');
        }

        if (false === $paginate) {
            return $q->getQuery()->getResult();
        }

        //@Todo: allow use of $firstresult or $maxresults without paginator
        $q->setFirstResult($firstresult)
          ->setMaxResults($maxresults)
        ;

        return new Paginator($q);
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
     */
    public function getOnlineChildren(Page $page, $maxResults = null, array $order = array('_leftnode', 'asc'))
    {
        $order = array_replace(array('_leftnode', 'asc'), $order);

        $q = $this->createQueryBuilder('p')
            ->andParentIs($page)
            ->andIsOnline()
            ->orderBy('p.'.$order[0], $order[1])
        ;

        if (null !== $maxResults) {
            $q->setMaxResults($maxResults);
        }

        return $q->getQuery()->getResult();
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
     */
    public function getChildren(Page $page, $order_sort = '_title', $order_dir = 'asc', $paging = array(), $restrictedStates = array(), $options = array())
    {
        $q = $this->createQueryBuilder('p')
            ->andParentIs($page)
            ->andSearchCriteria($restrictedStates, $options)
            ->orderBy('p.'.$order_sort, $order_dir)
        ;

        if (is_array($paging) && array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
            $q->setFirstResult($paging['start'])
              ->setMaxResults($paging['limit'])
            ;

            return new Paginator($q);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns count of children of $page
     * @param  \BackBuilder\NestedNode\Page $page             the parent page
     * @param  array                        $restrictedStates optional, limit to pages having provided states, empty by default
     * @param  array                        $options          optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                        'afterPubdateField' => timestamp against page._modified,
     *                                                        'searchField' => string to search for title
     * @return int                          the children count
     */
    public function countChildren(Page $page, $restrictedStates = array(), $options = array())
    {
        return $this->createQueryBuilder('p')
            ->select("COUNT(p)")
            ->andParentIs($page)
            ->andSearchCriteria($restrictedStates, $options)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Set state of $page and is descendant to STATE_DELETED
     * @param  \BackBuilder\NestedNode\Page $page the page to delete
     * @return integer                      the number of page having their state changed
     */
    public function toTrash(Page $page)
    {
        return $this->createQueryBuilder('p')
            ->update()
            ->set('p._state', Page::STATE_DELETED)
            ->andIsDescendantOf($page)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Returns an array of pages having title like $wordSearch
     * @param  string     $wordsSearch the string to test against title page
     * @param  array      $limit       optional, the query limit restriction, array(0, 10) by default
     * @return array|null
     */
    public function likeAPage($wordsSearch = "", array $limit = array(0, 10))
    {
        $limit = array_replace(array(0, 10), $limit);

        if ('' === $wordsSearch) {
            return;
        }

        return $this->createQueryBuilder('p')
            ->andTitleIsLike($wordsSearch)
            ->setFirstResult($limit[0])
            ->setMaxResults($limit[1])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Duplicate a page and optionnaly its descendants
     * @param  \BackBuilder\NestedNode\Page                    $page      the page to duplicate
     * @param  string                                          $title     optional, the title of the copy, by default the title of the copied page
     * @param  \BackBuilder\NestedNode\Page                    $parent    optional, the parent of the copy, by default the parent of the copied page
     * @param  boolean                                         $recursive if true (default) duplicate recursively the descendants of the page
     * @param \BackBuilder\Security\Token\BBUserToken           the BBuser token to allow the update of revisions
     * @return \BackBuilder\NestedNode\Page                    the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException occures if the page is deleted or if the page is recursively duplicated in itself
     */
    public function duplicate(Page $page, $title = null, Page $parent = null, $recursive = true, BBUserToken $token = null)
    {
        $new_page = (true === $recursive) ? $this->copyRecursively($page, $title, $parent) : $this->copy($page, $title, $parent);

        // Finally updating contentset and mainnode
        if (null !== $token) {
            foreach ($new_page->cloning_datas['contents'] as $content) {
                $this->updateRelatedPostCloning($content, $token, $new_page->cloning_datas)
                     ->updateMainNodePostCloning($content, $token, $new_page->cloning_datas['pages'])
                ;
            }

            $this->_em->flush();
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
    public function shiftPosition(Page $page, $delta, $strict = false)
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
                ->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return (null === $max) ? 0 : $max;
    }

    /**
     * Copy a page to a new one
     * @param  \BackBuilder\NestedNode\Page                    $page   the page to copy
     * @param  string                                          $title  optional, the title of the copy, by default the title of the page
     * @param  \BackBuilder\NestedNode\Page                    $parent optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                    the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException occures if the page is deleted
     */
    private function copy(Page $page, $title = null, Page $parent = null)
    {
        if (Page::STATE_DELETED & $page->getState()) {
            throw new InvalidArgumentException('Cannot duplicate a deleted page');
        }

        // Cloning the page
        $new_page = clone $page;
        $new_page->setTitle(null === $title ? $page->getTitle() : $title);

        // Setting the layout if exists
        if (null !== $page->getLayout()) {
            $new_page->setLayout($page->getLayout());
        }

        // Setting the clone as first child of the parent if exists
        if (null !== $parent || null !== $page->getParent()) {
            $parent = (null === $parent) ? $page->getParent() : $parent;
            $new_page = $this->insertNodeAsFirstChildOf($new_page, $parent);
        }

        // Persisting entities
        $this->_em->persist($new_page);
        $this->_em->flush();

        return $new_page;
    }

    /**
     * Copy recursively a page to a new one
     * @param  \BackBuilder\NestedNode\Page                    $page   the page to copy
     * @param  string                                          $title  optional, the title of the copy, by default the title of the page
     * @param  \BackBuilder\NestedNode\Page                    $parent optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                    the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException occures if the page is deleted or if the page is recursively duplicated in itself
     */
    private function copyRecursively(Page $page, $title = null, Page $parent = null)
    {
        if (null !== $parent && true === $parent->isDescendantOf($page)) {
            throw new InvalidArgumentException('Cannot recursively duplicate a page in itself');
        }

        // Cloning the page
        $new_page = $this->copy($page, $title, $parent);

        // Storing current children before clonage
        $children = array();
        if (false === $page->isLeaf()) {
            $children = $this->getDescendants($page, 1);
        }
        foreach (array_reverse($children) as $child) {
            if (!(Page::STATE_DELETED & $child->getState())) {
                $this->_em->refresh($new_page);
                $new_child = $this->duplicate($child, null, $new_page, true, null);
                $new_page->getChildren()->add($new_child);
                $new_page->cloning_datas = array_merge_recursive($new_page->cloning_datas, $new_child->cloning_datas);
            }
        }
        $this->_em->flush();

        return $new_page;
    }

    /**
     * Replace subcontents of ContentSet by their clones if exist
     * @param  \BackBuilder\ClassContent\AClassContent           $content
     * @param  \BackBuilder\Security\Token\BBUserToken           $token
     * @param  array                                             $cloning_datas
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function updateRelatedPostCloning(AClassContent $content, BBUserToken $token, array $cloning_datas)
    {
        if (
                false === ($content instanceof ContentSet) ||
                false === array_key_exists('pages', $cloning_datas) ||
                false === array_key_exists('contents', $cloning_datas) ||
                0 === count($cloning_datas['pages']) ||
                0 === count($cloning_datas['contents'])
        ) {
            // Nothing to do
            return $this;
        }

        // Reading copied elements
        $copied_pages = array_keys($cloning_datas['pages']);
        $copied_contents = array_keys($cloning_datas['contents']);

        // Updating subcontent if needed
        foreach ($content as $subcontent) {
            if (false === $this->_em->contains($subcontent)) {
                $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
            }

            if (
                    null === $subcontent->getMainNode() ||
                    false === in_array($subcontent->getMainNode()->getUid(), $copied_pages) ||
                    false === in_array($subcontent->getUid(), $copied_contents)
            ) {
                continue;
            }

            // Loading draft for content
            if (null !== $draft = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true)) {
                $content->setDraft($draft);
            }

            $content->replaceChildBy($subcontent, $cloning_datas['contents'][$subcontent->getUid()]);
        }

        return $this;
    }

    /**
     * Update mainnode of the content if need
     * @param  \BackBuilder\ClassContent\AClassContent           $content
     * @param  \BackBuilder\Security\Token\BBUserToken           $token
     * @param  array                                             $cloning_pages
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function updateMainNodePostCloning(AClassContent $content, BBUserToken $token, array $cloning_pages)
    {
        $mainnode = $content->getMainNode();
        if (null !== $mainnode && true === in_array($mainnode->getUid(), array_keys($cloning_pages))) {
            // Loading draft for content
            if (NULL !== $draft = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true)) {
                $content->setDraft($draft);
            }

            $content->setMainNode($cloning_pages[$mainnode->getUid()]);
        }

        return $this;
    }
}
