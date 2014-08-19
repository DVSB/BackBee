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
     * @param type $maxresult                       optional, if paginated set the maxmum number of results (25 by default)
     * @param type $having_child                    optional, limit to descendants having child (false by default)
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|\BackBuilder\NestedNode\Page[]
     */
    public function getNotDeletedDescendants(Page $page, $depth = null, $includeNode = false, array $order = array('_leftnode' => 'asc'), $paginate = false, $firstresult = 0, $maxresult = 25, $having_child = false)
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
            $q->andLevelIsLowerThan($page->getLevel() + $depth, true);
        }

        if (true === $having_child) {
            $q->andWhere('p._rightnode > (p._leftnode + 1)');
        }

        if (false === $paginate) {
            return $q->getQuery()->getResult();
        }

        $q->setFirstResult($firstresult)
                ->setMaxResults($maxresult);

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
        $order = array_merge(array('_leftnode', 'asc'), $order);

        $q = $this->createQueryBuilder('p')
                ->andParentIs($page)
                ->andIsOnline()
                ->orderBy('p.' . $order[0], $order[1]);

        if (null !== $maxResults) {
            $q->setMaxResults($maxResults);
        }

        return $q->getQuery()->getResult();
    }

    public function getChildren(Page $page, $order_sort = '_title', $order_dir = 'asc', $paging = array(), $restrictedStates = array(), $options = array())
    {
        $q = $this->createQueryBuilder('p')
                ->andParentIs($page)
                ->orderBy('p.' . $order_sort, $order_dir);

        if (true === is_array($restrictedStates) && 0 < count($restrictedStates)) {
            $q->andStateIsIn($restrictedStates);
        }
        
//        $result = null;
//        $q = $this->createQueryBuilder('p')
//                ->andWhere('p._parent = :page')
//                ->orderBy('p.' . $order_sort, $order_dir)
//                ->setParameters(array(
//            'page' => $page
//        ));
//        $restrictedStates = (array) $restrictedStates;
//        if (!in_array('all', $restrictedStates) && 0 < count($restrictedStates)) {
//            $q = $q->andWhere('p._state IN (:states)')
//                    ->setParameter('states', implode(',', $restrictedStates));
//        }
        if (array_key_exists('beforePubdateField', $options)) {
            $q->andWhere('p._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $options['beforePubdateField']));
        }
        if (array_key_exists('afterPubdateField', $options)) {
            $q->andWhere('p._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $options['afterPubdateField']));
        }
        if (array_key_exists('searchField', $options)) {
            $q->andWhere($q->expr()->like('p._title', $q->expr()->literal('%' . $options['searchField'] . '%')));
        }
        if (is_array($paging)) {
            if (array_key_exists("start", $paging) && array_key_exists("limit", $paging)) {
                $q->setFirstResult($paging["start"])
                        ->setMaxResults($paging["limit"]);
                $result = new \Doctrine\ORM\Tools\Pagination\Paginator($q);
            }
        } else {
            $result = $q->getQuery()->getResult();
        }
        return $result;
    }

    public function countChildren(Page $page, $restrictedStates = array(), $options = array())
    {
        $q = $this->createQueryBuilder("p")
                ->select("COUNT(p)")
                ->andWhere("p._parent = :page")
                ->setParameters(array('page' => $page));
        $restrictedStates = (array) $restrictedStates;
        if (!in_array('all', $restrictedStates) && 0 < count($restrictedStates)) {
            $q = $q->andWhere('p._state IN (:states)')
                    ->setParameter('states', implode(',', $restrictedStates));
        }
        if (array_key_exists('beforePubdateField', $options)) {
            $q->andWhere('p._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $options['beforePubdateField']));
        }
        if (array_key_exists('afterPubdateField', $options)) {
            $q->andWhere('p._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $options['afterPubdateField']));
        }
        if (array_key_exists('searchField', $options)) {
            $q->andWhere($q->expr()->like('p._title', $q->expr()->literal('%' . $options['searchField'] . '%')));
        }
        return $q->getQuery()->getSingleScalarResult();
    }

    public function toTrash(Page $page)
    {
        $q = $this->createQueryBuilder('p')
                ->update()
                ->set('p._state', Page::STATE_DELETED)
                ->andIsDescendantOf($page);
//                ->andWhere('p._root = :root')
//                ->andWhere('p._leftnode >= :leftnode')
//                ->andWhere('p._rightnode <= :rightnode')
//                ->setParameters(array(
//            'root' => $page->getRoot(),
//            'leftnode' => $page->getLeftnode(),
//            'rightnode' => $page->getRightnode()
//        ));
        return $q->getQuery()->execute();
    }

    public function likeAPage($wordsSearch = "", array $limit = array(0, 10))
    {
        if ("" === $wordsSearch)
            return null;
        $q = $this->createQueryBuilder('p');
        $q->andWhere($q->expr()->like('p._title', $q->expr()->literal('%' . $wordsSearch . '%')));
        $q->setFirstResult($limit[0])->setMaxResults($limit[1]);
        try {
            $result = $q->getQuery()->getResult();
            return $result;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Copy a page to a new one
     * @param \BackBuilder\NestedNode\Page $page
     * @param string $title Optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page $parent Optional, the parent of the copy, by default the parent of the page
     * @return \BackBuilder\NestedNode\Page The copy of the page
     * @throws \BackBuilder\Exception\BBException Occure if the page is deleted
     */
    private function _copy(Page $page, $title = null, Page $parent = null)
    {
        if (Page::STATE_DELETED & $page->getState()) {
            throw new \BackBuilder\Exception\BBException('Cannot duplicate a deleted page');
        }
        // Setting default values if not provided
        $title = (null === $title) ? $page->getTitle() : $title;
        $parent = (null === $parent) ? $page->getParent() : $parent;
        // Cloning the page
        $new_page = clone $page;
        $new_page->setTitle($title)
                ->setLayout($page->getLayout());
        // Setting the clone as first child of the parent
        if (null !== $parent) {
            $new_page = $this->insertNodeAsFirstChildOf($new_page, $parent);
        }
        // Persisting entities
        $this->_em->persist($new_page);
        $this->_em->flush();

        return $new_page;
    }

    /**
     * Replace subcontent of ContentSet by their clone if exist
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param \BackBuilder\Security\Token\BBUserToken $token
     * @param array $cloning_datas
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function _updateRelatedPostCloning(AClassContent $content, BBUserToken $token, array $cloning_datas)
    {
        if (($content instanceof ContentSet) && true === array_key_exists('pages', $cloning_datas) && true === array_key_exists('contents', $cloning_datas) && 0 < count($cloning_datas['pages']) && 0 < count($cloning_datas['contents'])) {
            // reading copied elements
            $copied_pages = array_keys($cloning_datas['pages']);
            $copied_contents = array_keys($cloning_datas['contents']);
            // Updating subcontent if needed
            foreach ($content as $subcontent) {
                if (false === $this->_em->contains($subcontent)) {
                    $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
                }
                if (null !== $subcontent->getMainNode() && true === in_array($subcontent->getMainNode()->getUid(), $copied_pages) && true === in_array($subcontent->getUid(), $copied_contents)) {
                    // Loading draft for content
                    if (NULL !== $draft = $this->_em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $token, true)) {
                        $content->setDraft($draft);
                    }
                    $content->replaceChildBy($subcontent, $cloning_datas['contents'][$subcontent->getUid()]);
                }
            }
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
        if (null !== $mainnode && 0 < count($cloning_pages) && true === in_array($mainnode->getUid(), array_keys($cloning_pages))) {

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
     * @param \BackBuilder\NestedNode\Page $page The page to duplicate
     * @param string $title Optional, the title of the copy, by default the title of the page
     * @param \BackBuilder\NestedNode\Page $parent Optional, the parent of the copy, by default the parent of the page
     * @param boolean $recursive If true duplicate recursively the descendants of the page
     * @param \BackBuilder\Security\Token\BBUserToken The BBuser token to allow te updte of revisions
     * @return \BackBuilder\NestedNode\Page The copy of the page
     * @throws \BackBuilder\Exception\BBException Occure if the page is recursively duplicated in itself
     */
    public function duplicate(Page $page, $title = null, Page $parent = null, $recursive = true, BBUserToken $token = null)
    {
        if (true === $recursive && true === $parent->isDescendantOf($page)) {
            throw new \BackBuilder\Exception\BBException('Cannot recursively duplicate a page in itself');
        }
        // Storing current children before clonage
        $children = array();
        if (false === $page->isLeaf()) {
            $children = $this->getDescendants($page, 1);
        }
        // Cloning the page
        $new_page = $this->_copy($page, $title, $parent);
        // Cloning children if needed
        if (true === $recursive) {
            foreach (array_reverse($children) as $child) {
                if (!(Page::STATE_DELETED & $child->getState())) {
                    $this->_em->refresh($new_page);
                    $new_child = $this->duplicate($child, null, $new_page, $recursive, null);
                    $new_page->getChildren()->add($new_child);
                    $new_page->cloning_datas = array_merge_recursive($new_page->cloning_datas, $new_child->cloning_datas);
                }
            }
            $this->_em->flush();
        }
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
