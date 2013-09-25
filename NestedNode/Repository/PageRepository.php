<?php

namespace BackBuilder\NestedNode\Repository;

use BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\NestedNode\ANestedNode,
    BackBuilder\Security\Token\BBUserToken;

/**
 */
class PageRepository extends NestedNodeRepository
{

    private function _andOnline(\Doctrine\ORM\QueryBuilder $q)
    {
        return $q->andWhere('n._state >= ' . Page::STATE_ONLINE)
                        ->andWhere('n._state <' . Page::STATE_DELETED)
                        ->andWhere('n._publishing IS NULL OR n._publishing <= CURRENT_TIMESTAMP()')
                        ->andWhere('n._archiving IS NULL OR n._archiving > CURRENT_TIMESTAMP()');
    }

    public function getOnlinePrevSibling(ANestedNode $node)
    {
        $q = $this->_getPrevSiblingsQuery($node)
                ->orderBy('n._leftnode', 'desc')
                ->setMaxResults(1);
        $q = $this->_andOnline($q);

        return $q->getQuery()->getOneOrNullResult();
    }

    public function getOnlineNextSibling(ANestedNode $node)
    {
        $q = $this->_getNextSiblingsQuery($node)
                ->orderBy('n._leftnode', 'asc')
                ->setMaxResults(1);
        $q = $this->_andOnline($q);

        return $q->getQuery()->getOneOrNullResult();
    }

    public function insertNodeAsFirstChildOf(ANestedNode $node, ANestedNode $parent)
    {
        $node = parent::insertNodeAsFirstChildOf($node, $parent);
        $node->setSite($parent->getSite());

        return $node;
    }

    public function insertNodeAsLastChildOf(ANestedNode $node, ANestedNode $parent)
    {
        $node = parent::insertNodeAsLastChildOf($node, $parent);
        $node->setSite($parent->getSite());

        return $node;
    }

    public function getVisibleDescendants(ANestedNode $node, $depth = NULL, $includeNode = FALSE)
    {
        $q = $this->_getDescendantsQuery($node, $depth, $includeNode)
                ->andWhere('n._state = :online')
                ->andWhere('n._publishing IS NULL OR n._publishing <= CURRENT_TIMESTAMP()')
                ->andWhere('n._archiving IS NULL OR n._archiving > CURRENT_TIMESTAMP()')
                ->setParameter('online', Page::STATE_ONLINE);

        return $q->getQuery()->getResult();
    }

    public function getVisiblePrevSibling(ANestedNode $node)
    {
        if (is_null($node))
            throw new \Exception(__METHOD__ . " node can't be null");
        $q = $this->_getPrevSiblingQuery($node);
        //->andWhere("n._state = :online")
        //->setParameter('online', Page::STATE_ONLINE);
        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \BackBuilder\NestedNode\Page $page page to move.
     * @param \BackBuilder\NestedNode\Page $parent the new page parent.
     * @param string $next_uid
     * @return \BackBuilder\NestedNode\Page
     */
    public function movePageInTree(Page $page, Page $parent, $next_uid = null)
    {
        $page = $this->insertNodeAsLastChildOf($page, $parent);
        $next = ($next_uid !== null) ? $this->find($next_uid) : null;
        if (!is_null($next)) {
            $this->moveAsPrevSiblingOf($page, $next);
        }
        return $page;
    }

    public function replaceRootContentSet(Page $page, ContentSet $oldContentSet, ContentSet $newContentSet)
    {
        try {
            $result = $page->replaceRootContentSet($oldContentSet, $newContentSet);
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getVisibleNextSibling(ANestedNode $node)
    {
        if (is_null($node))
            throw new \Exception(__METHOD__ . " node can't be null");
        $q = $this->_getNextSiblingQuery($node);
        //->andWhere("n._state = :online")
        //->setParameter('online', Page::STATE_ONLINE);
        return $q->getQuery()->getOneOrNullResult();
    }

    public function getNotDeletedDescendants(ANestedNode $node, $depth = NULL, $includeNode = FALSE, $order = array())
    {
        $q = $this->_getDescendantsQuery($node, $depth, $includeNode)
                ->andWhere('n._state < :deleted')
                ->setParameter('deleted', Page::STATE_DELETED);
        if (is_array($order) && !empty($order)) {
            if (array_key_exists("field", $order) && array_key_exists("sort", $order)) {
                if (!empty($order["field"]) && !empty($order["sort"])) {
                    $q->orderBy('n._' . trim($order["field"]), trim($order["sort"]));
                }
            }
        }

        return $q->getQuery()->getResult();
    }

    public function getRoot(\BackBuilder\Site\Site $site, $restrictedStates = array())
    {
        try {
            $q = $this->createQueryBuilder('p')
                    ->andWhere('p._site = :site')
                    ->andWhere('p._parent is null')
                    ->setMaxResults(1)
                    ->setParameters(array(
                'site' => $site
                    ));

            $restrictedStates = (array) $restrictedStates;
            if (0 < count($restrictedStates)) {
                $q = $q->andWhere('p._state IN (:states)')
                        ->setParameter('states', implode(',', $restrictedStates));
            }

            return $q->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getOnlineChildren(\BackBuilder\NestedNode\Page $page, $maxResults = null, $order = array('_leftnode', 'ASC'))
    {
        $q = $this->createQueryBuilder('n')
                ->andWhere('n._parent = :page')
                ->setParameters(array(
            'page' => $page
                ));

        if (false === is_array($order)) {
            $order = array($order);
        }
        if (0 === count($order)) {
            $order[] = '_leftnode';
        }
        if (1 === count($order)) {
            $order[] = 'ASC';
        }
        
        $q = $q->orderBy('n.'.$order[0], $order[1]);
        
        $q = $this->_andOnline($q);
        if (null !== $maxResults)
            $q = $q->setMaxResults($maxResults);

        return $q->getQuery()->getResult();
    }

    public function getChildren(\BackBuilder\NestedNode\Page $page, $order_sort = '_title', $order_dir = 'asc', $paging = array(), $restrictedStates = array(), $options = array())
    {
        $result = null;
        $q = $this->createQueryBuilder('p')
                ->andWhere('p._parent = :page')
                ->orderBy('p.' . $order_sort, $order_dir)
                ->setParameters(array(
            'page' => $page
                ));

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
                ->andWhere('p._root = :root')
                ->andWhere('p._leftnode >= :leftnode')
                ->andWhere('p._rightnode <= :rightnode')
                ->setParameters(array(
            'root' => $page->getRoot(),
            'leftnode' => $page->getLeftnode(),
            'rightnode' => $page->getRightnode()
                ));

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
        if (($content instanceof ContentSet)
                && true === array_key_exists('pages', $cloning_datas)
                && true === array_key_exists('contents', $cloning_datas)
                && 0 < count($cloning_datas['pages'])
                && 0 < count($cloning_datas['contents'])) {

            // reading copied elements
            $copied_pages = array_keys($cloning_datas['pages']);
            $copied_contents = array_keys($cloning_datas['contents']);

            // Updating subcontent if needed
            foreach ($content as $subcontent) {
                if (false === $this->_em->contains($subcontent)) {
                    $subcontent = $this->_em->find(get_class($subcontent), $subcontent->getUid());
                }

                if (null !== $subcontent->getMainNode()
                        && true === in_array($subcontent->getMainNode()->getUid(), $copied_pages)
                        && true === in_array($subcontent->getUid(), $copied_contents)) {

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
        if (null !== $mainnode
                && 0 < count($cloning_pages)
                && true === in_array($mainnode->getUid(), array_keys($cloning_pages))) {

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

    public function removeEmptyPages(\BackBuilder\Site\Site $site)
    {
        $q = $this->createQueryBuilder('p')
                ->select()
                ->andWhere('p._contentset IS NULL')
                ->andWhere('p._site = :site')
                ->orderBy('p._leftnode', 'desc')
                ->setParameter('site', $site);

        foreach($q->getQuery()->execute() as $page) {
            $this->delete($page);
        }
    }
}