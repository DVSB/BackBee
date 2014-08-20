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
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\NestedNode\ANestedNode,
    BackBuilder\Security\Token\BBUserToken,
    BackBuilder\Site\Layout,
    BackBuilder\Site\Site,
    BackBuilder\Exception\InvalidArgumentException;

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
class PageRepository extends NestedNodeRepository
{

    /**
     * Creates a new Page QueryBuilder instance that is prepopulated for this entity name.
     * @param string $alias      the alias to use
     * @param string $indexBy    optional, the index to use for the query
     * @return \BackBuilder\NestedNode\Repository\PageQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $qb = new PageQueryBuilder($this->_em);
        return $qb->select($alias)->from($this->_entityName, $alias, $indexBy);
    }

    /**
     * @deprecated since version 0.10.0
     */
    private function _andOnline(\Doctrine\ORM\QueryBuilder $q)
    {
        return $q->andWhere('n._state >= ' . Page::STATE_ONLINE)
            ->andWhere('n._state <' . Page::STATE_DELETED)
			->andWhere('n._state <>' . Page::STATE_HIDDEN)
            ->andWhere('n._publishing IS NULL OR n._publishing <= :now')
            ->andWhere('n._archiving IS NULL OR n._archiving > :now')
            ->setParameter('now', date('Y-m-d H:i:00', time()));
    }

    /**
     * Returns the online descendants of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param int $depth                            optional, limit to $depth number of generation
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getOnlineDescendants(Page $page, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, !$includeNode)
                ->andIsOnline()
                ->orderBy('p._leftnode', 'asc');

        if (null !== $depth) {
            $q->andLevelIsLowerThan($page->getLevel() + $depth);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns the previous online sibling of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getOnlinePrevSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
                        ->andIsPreviousOnlineSiblingOf($page)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the online siblings of the provided page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @param array $order                          optional, the ordering criteria ( array($field => $sort) )
     * @param int $limit                            optional, the maximum number of results
     * @param int $start                            optional, the first result index (0 by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getOnlineSiblings(Page $page, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
                        ->andIsOnlineSiblingsOf($page, !$includeNode, $order, $limit, $start)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the onlne siblings having layout $layout of the provided page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param \BackBuilder\Site\Layout $layout      the layout to look for
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @param array $order                          optional, the ordering criteria ( array($field => $sort) )
     * @param int $limit                            optional, the maximum number of results
     * @param int $start                            optional, the first result index (0 by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getOnlineSiblingsByLayout(Page $page, Layout $layout, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
                        ->andIsOnlineSiblingsOf($page, !$includeNode, $order, $limit, $start)
                        ->andLayoutIs($layout)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the next online sibling of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getOnlineNextSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
                        ->andIsNextOnlineSiblingOf($page)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Inserts a leaf page in a tree as first child of the provided parent page
     * @param \BackBuilder\NestedNode\Page $page     the page to be inserted
     * @param \BackBuilder\NestedNode\Page $parent   the parent page
     * @return \BackBuilder\NestedNode\Page          the inserted page
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the page is not a leaf or $parent is not flushed yet
     *                                                         or if $page or $parent are not an instance of Page
     */
    public function insertNodeAsFirstChildOf(ANestedNode $page, ANestedNode $parent)
    {
        if (false === ($page instanceof Page)) {
            throw new InvalidArgumentException(sprintf('Waiting for \BackBuilder\NestedNode\Page get %s', get_class($page)));
        }

        if (false === ($parent instanceof Page)) {
            throw new InvalidArgumentException(sprintf('Waiting for \BackBuilder\NestedNode\Page get %s', get_class($parent)));
        }

        $page = parent::insertNodeAsFirstChildOf($page, $parent);

        return $page->setSite($parent->getSite());
    }

    /**
     * Inserts a leaf page in a tree as last child of the provided parent node
     * @param \BackBuilder\NestedNode\Page $page     the page to be inserted
     * @param \BackBuilder\NestedNode\Page $parent   the parent page
     * @return \BackBuilder\NestedNode\Page          the inserted page
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the page is not a leaf or $parent is not flushed yet
     *                                                         or if $page or $parent are not an instance of Page
     */
    public function insertNodeAsLastChildOf(ANestedNode $page, ANestedNode $parent)
    {
        if (false === ($page instanceof Page)) {
            throw new InvalidArgumentException(sprintf('Waiting for \BackBuilder\NestedNode\Page get %s', get_class($page)));
        }

        if (false === ($parent instanceof Page)) {
            throw new InvalidArgumentException(sprintf('Waiting for \BackBuilder\NestedNode\Page get %s', get_class($parent)));
        }

        $page = parent::insertNodeAsLastChildOf($page, $parent);

        return $page->setSite($parent->getSite());
    }

    /**
     * Returns the visible (ie online and not hidden) descendants of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param int $depth                            optional, limit to $depth number of generation
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getVisibleDescendants(Page $page, $depth = null, $includeNode = false)
    {
        $q = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, !$includeNode)
                ->andIsVisible()
                ->orderBy('p._leftnode', 'asc');

        if (null !== $depth) {
            $q->andLevelIsLowerThan($page->getLevel() + $depth);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns the visible (ie online and not hidden) siblings of the provided page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param boolean $includeNode                  optional, include $page in results if TRUE (false by default)
     * @param array $order                          optional, the ordering criteria ( array($field => $sort) )
     * @param int $limit                            optional, the maximum number of results
     * @param int $start                            optional, the first result index (0 by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getVisibleSiblings(Page $page, $includeNode = false, $order = null, $limit = null, $start = 0)
    {
        return $this->createQueryBuilder('p')
                        ->andIsVisibleSiblingsOf($page, !$includeNode, $order, $limit, $start)
                        ->getQuery()
                        ->getResult();
    }

    /**
     * Returns the previous visible (ie online and not hidden) sibling of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getVisiblePrevSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
                        ->andIsPreviousVisibleSiblingOf($page)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Moves $page as child of $parent by default at last position or, optionaly, before node having uid = $next_uid
     * @param \BackBuilder\NestedNode\Page $page      the page to move
     * @param \BackBuilder\NestedNode\Page $parent    the page parent to move in
     * @param string $next_uid                        optional, the uid of the next sibling
     * @return \BackBuilder\NestedNode\Page           the moved page
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
     * @param \BackBuilder\NestedNode\Page $page                       the page to change
     * @param \BackBuilder\ClassContent\ContentSet $oldContentSet      the contentset to replace
     * @param \BackBuilder\ClassContent\ContentSet $newContentSet      the new contentset
     * @return \BackBuilder\ClassContent\ContentSet                    the inserted contentset
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
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getVisibleNextSibling(Page $page)
    {
        return $this->createQueryBuilder('p')
                        ->andIsNextVisibleSiblingOf($page)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     * Returns the not deleted descendants of $page
     * @param \BackBuilder\NestedNode\Page $page    the page to look for
     * @param type $depth                           optional, limit to $depth number of generation
     * @param type $includeNode                     optional, include $page in results if TRUE (false by default)
     * @param type $order                           optional, the ordering criteria ( array('_leftnode' => 'asc') by default )
     * @param type $paginate                        optional, if TRUE return a paginator rather than an array (false by default)
     * @param type $firstresult                     optional, if paginated set the first result index (0 by default)
     * @param type $maxresults                      optional, if paginated set the maxmum number of results (25 by default)
     * @param type $having_child                    optional, limit to descendants having child (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getNotDeletedDescendants(Page $page, $depth = null, $includeNode = false, array $order = array('_leftnode' => 'asc'), $paginate = false, $firstresult = 0, $maxresults = 25, $having_child = false)
    {
        // @Todo: search for calls with wrong ordering criteria format and solve them
        if (true === array_key_exists('field', $order)) {
            $order = array($order['field'] => (true === array_key_exists('sort', $order) ? $order['sort'] : 'asc'));
        }

        $q = $this->createQueryBuilder('p')
                ->andIsDescendantOf($page, !$includeNode)
                ->andStateIsLowerThan(Page::STATE_DELETED)
                ->orderByMultiple($order);

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
                ->setMaxResults($maxresults);

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
                ->andSiteIs($site)
                ->andParentIs(null)
                ->setMaxResults(1);
        
        if (0 < count($restrictedStates)) {
            $q->andStateIsIn($restrictedStates);
        }

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns an array of online children of $page
     * @param \BackBuilder\NestedNode\Page $page  the parent page
     * @param int $maxResults                     optional, the maximum number of results
     * @param array $order                        optional, the ordering criteria (array('_leftnode', 'asc') by default)
     * @return \BackBuilder\NestedNode\Page[]
     */
    public function getOnlineChildren(Page $page, $maxResults = null, array $order = array('_leftnode', 'asc'))
    {
        $order = array_replace(array('_leftnode', 'asc'), $order);

        $q = $this->createQueryBuilder('p')
                ->andParentIs($page)
                ->andIsOnline()
                ->orderBy('p.' . $order[0], $order[1]);

        if (null !== $maxResults) {
            $q->setMaxResults($maxResults);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns an array of children of $page
     * @param \BackBuilder\NestedNode\Page $page    the parent page
     * @param string $order_sort                    optional, the sort field, title by default
     * @param string $order_dir                     optional, the sort direction, asc by default
     * @param string $paging                        optional, the paging criteria: array('start' => xx, 'limit' => xx), empty by default
     * @param array $restrictedStates               optional, limit to pages having provided states, empty by default
     * @param array $options                        optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                                                   'afterPubdateField' => timestamp against page._modified,
     *                                                                                   'searchField' => string to search for title
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator     Returns Paginaor is paging criteria provided, array otherwise
     */
    public function getChildren(Page $page, $order_sort = '_title', $order_dir = 'asc', $paging = array(), $restrictedStates = array(), $options = array())
    {
        $q = $this->createQueryBuilder('p')
                ->andParentIs($page)
                ->andSearchCriteria($restrictedStates, $options)
                ->orderBy('p.' . $order_sort, $order_dir);

        if (
                true === is_array($paging) &&
                true === array_key_exists("start", $paging) &&
                true === array_key_exists("limit", $paging)
        ) {
            $q->setFirstResult($paging["start"])
                    ->setMaxResults($paging["limit"]);

            return new Paginator($q);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Returns count of children of $page
     * @param \BackBuilder\NestedNode\Page $page    the parent page
     * @param array $restrictedStates               optional, limit to pages having provided states, empty by default
     * @param array $options                        optional, the search criteria: array('beforePubdateField' => timestamp against page._modified,
     *                                                                                   'afterPubdateField' => timestamp against page._modified,
     *                                                                                   'searchField' => string to search for title
     * @return int                                  the children count
     */
    public function countChildren(Page $page, $restrictedStates = array(), $options = array())
    {
        return $this->createQueryBuilder('p')
                        ->select("COUNT(p)")
                        ->andParentIs($page)
                        ->andSearchCriteria($restrictedStates, $options)
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    /**
     * Set state of $page and is descendant to STATE_DELETED
     * @param \BackBuilder\NestedNode\Page $page    the page to delete
     * @return integer                              the number of page having their state changed
     */
    public function toTrash(Page $page)
    {
        return $this->createQueryBuilder('p')
                        ->update()
                        ->set('p._state', Page::STATE_DELETED)
                        ->andIsDescendantOf($page)
                        ->getQuery()
                        ->execute();
    }

    /**
     * Returns an array of pages having title like $wordSearch
     * @param string $wordsSearch   the string to test against title page
     * @param array $limit          optional, the query limit restriction, array(0, 10) by default
     * @return array|null
     */
    public function likeAPage($wordsSearch = "", array $limit = array(0, 10))
    {
        $limit = array_replace(array(0, 10), $limit);
        
        if ("" === $wordsSearch) {
            return null;
        }
        
        return $this->createQueryBuilder('p')
                ->andTitleIsLike($wordsSearch)
                ->setFirstResult($limit[0])
                ->setMaxResults($limit[1])
                ->getQuery()
                ->getResult();
    }

    /**
     * Copy a page to a new one
     * @param \BackBuilder\NestedNode\Page $page                 the page to copy
     * @param string $title                                      optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page $parent               optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                      the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException   occures if the page is deleted
     */
    private function _copy(Page $page, $title = null, Page $parent = null)
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
     * @param \BackBuilder\NestedNode\Page $page                 the page to copy
     * @param string $title                                      optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page $parent               optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page                      the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException   occures if the page is deleted or if the page is recursively duplicated in itself
     */
    private function _copy_recursively(Page $page, $title = null, Page $parent = null)
    {
        if (null !== $parent && true === $parent->isDescendantOf($page)) {
            throw new InvalidArgumentException('Cannot recursively duplicate a page in itself');
        }

        // Cloning the page
        $new_page = $this->_copy($page, $title, $parent);

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
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param \BackBuilder\Security\Token\BBUserToken $token
     * @param array $cloning_datas
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function _updateRelatedPostCloning(AClassContent $content, BBUserToken $token, array $cloning_datas)
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
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param \BackBuilder\Security\Token\BBUserToken $token
     * @param array $cloning_pages
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function _updateMainNodePostCloning(AClassContent $content, BBUserToken $token, array $cloning_pages)
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

    /**
     * Duplicate a page and optionnaly its descendants
     * @param \BackBuilder\NestedNode\Page $page                the page to duplicate
     * @param string $title                                     optional, the title of the copy, by default the title of the copied page
     * @param \BackBuilder\NestedNode\Page $parent              optional, the parent of the copy, by default the parent of the copied page
     * @param boolean $recursive                                if true (default) duplicate recursively the descendants of the page
     * @param \BackBuilder\Security\Token\BBUserToken           the BBuser token to allow the update of revisions
     * @return \BackBuilder\NestedNode\Page                     the copy of the page
     * @throws \BackBuilder\Exception\InvalidArgumentException  occures if the page is deleted or if the page is recursively duplicated in itself
     */
    public function duplicate(Page $page, $title = null, Page $parent = null, $recursive = true, BBUserToken $token = null)
    {
        $new_page = (true === $recursive) ? $this->_copy_recursively($page, $title, $parent) : $this->_copy($page, $title, $parent);

        // Finally updating contentset and mainnode
        if (null !== $token) {
            foreach ($new_page->cloning_datas['contents'] as $content) {
                $this->_updateRelatedPostCloning($content, $token, $new_page->cloning_datas)
                        ->_updateMainNodePostCloning($content, $token, $new_page->cloning_datas['pages']);
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
                ->setParameter('site', $site);
        foreach ($q->getQuery()->execute() as $page) {
            $this->delete($page);
        }
    }

}
