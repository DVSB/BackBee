<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Services\Local;

use BackBee\BBApplication;
use BackBee\Exception\InvalidArgumentException;
use BackBee\MetaData\MetaDataBag;
use BackBee\NestedNode\Page as NestedPage;
use BackBee\Site\Layout as SiteLayout;

/**
 * RPC services for NestedNode\Page
 *
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class Page extends AbstractServiceLocal
{
    /**
     * Page entities repository
     * @var \BackBee\NestedNode\Repository\PageRepository
     */
    private $_repo;

    /**
     * Initialize the service
     * @param \BackBee\BBApplication $application
     * @codeCoverageIgnore
     */
    public function initService(BBApplication $application)
    {
        parent::initService($application);

        $this->_repo = $this->getEntityManager()
                ->getRepository('\BackBee\NestedNode\Page');
    }

    /**
     *
     * @exposed(secured=true)
     */
    public function getListAvailableStatus()
    {
        return NestedPage::$STATES;
    }

    /**
     * Duplicate a page
     * @exposed(secured=true)
     */
    public function duplicate($uid)
    {
        $em = $this->bbapp->getEntityManager();
        $page = $em->getRepository('\BackBee\NestedNode\Page')->find($uid);

        if (NULL === $page) {
            throw new ServicesException(sprintf('Unable to find page for `%s` uid', $uid));
        }

        $newpage = clone $page;
    }

    /**
     * Return the serialized form of a page
     * Returns the available workflow states for NestedNode\Page and Site\Layout
     * @param  string                                          $layout_uid
     * @return array
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if $layout_uid is invalid
     * @exposed(secured=true)
     */
    public function getWorkflowStatus($layout_uid)
    {
        if (null === $layout = $this->getEntityManager()->find('BackBee\Site\Layout', strval($layout_uid))) {
            throw new InvalidArgumentException(sprintf('None layout exists with uid `%s`.', $layout_uid));
        }

        $this->isGranted('VIEW', $layout);

        $layout_states = $this->getEntityManager()
                ->getRepository('BackBee\Workflow\State')
                ->getWorkflowStatesForLayout($layout);

        $result = array();
        foreach ($layout_states as $state) {
            $result[$state->getCode()] = $state->toArray();
        }

        return $result;
    }

    /**
     * Get the page info
     * @param  string                                                   $page_uid The unique identifier of the page
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $page_uid is invalid
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function find($page_uid)
    {
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $page_uid));
        }

        $this->isGranted('VIEW', $page);

        $opage = json_decode($page->serialize());
        if (null !== $this->getApplication()->getRenderer()) {
            $opage->url = $this->getApplication()->getRenderer()->getUri($opage->url);
            $opage->redirect = $this->getApplication()->getRenderer()->getUri($opage->redirect);
        }

        $defaultmeta = new MetaDataBag($this->getApplication()->getConfig()->getSection('metadata'));
        $opage->metadata = (null === $opage->metadata) ? $defaultmeta->toArray() : array_merge($defaultmeta->toArray(), $page->getMetadata()->toArray());

        return $opage;
    }

    /**
     * Updates a page
     * @param  string                                                   $serialized The serialized page
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $serialized is not valid
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function update($serialized)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        if (null === $object = json_decode($serialized)) {
            throw new InvalidArgumentException('Can not decode serialized data.');
        }

        if (false === property_exists($object, 'uid')) {
            throw new InvalidArgumentException('An uid has to be provided');
        }

        if (null === $page = $this->_repo->find(strval($object->uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $object->uid));
        }

        // User must have edit permission on page
        $this->isGranted('EDIT', $page);

        // If the page is online, user must have publish permission on it
        if ($page->isOnline(true)) {
            $this->isGranted('PUBLISH', $page);
        }

        // Updating URL of the page is needed
        if (true === property_exists($object, 'url') && null !== $this->getApplication()->getRenderer()) {
            $object->url = $this->getApplication()
                ->getRenderer()
                ->getRelativeUrl($object->url)
            ;

            if ('/' === $redirect = $this->getApplication()->getRenderer()->getRelativeUrl($object->redirect)) {
                $object->redirect = null;
            }
        }

        // Updating workflow state if provided
        if (null === $object->workflow_state) {
            $page->setWorkflowState(null);
        } else {
            $layout_states = $this->getEntityManager()
                    ->getRepository('BackBee\Workflow\State')
                    ->getWorkflowStatesForLayout($page->getLayout());

            foreach ($layout_states as $state) {
                if ($state->getCode() === $object->workflow_state) {
                    $object->workflow_state = $state;
                    break;
                }
            }
        }

        if (null === $page->getMetaData()) {
            $metadata_config = $this->getApplication()->getConfig()->getSection('metadata');
            $metadata = new \BackBee\MetaData\MetaDataBag($metadata_config, $page);
            $page->setMetaData($metadata->compute($page));
        }

        $page->unserialize($object);
        $this->getEntityManager()->flush();

        return array(
            'url'   => $page->getUrl().(
                true === $this->getApplication()->getController()->isUrlExtensionRequired()
                && '/' !== substr($page->getUrl(), -1)
                ? '.'.$this->getApplication()->getController()->getUrlExtension()
                : ''
            ),
            'state' => $page->getState(),
        );
    }

    /**
     * Returns a part of the site tree
     * @param  string                                                   $site_uid    The unique identifier of the site
     * @param  string                                                   $page_uid    The parent uid of the part of the tree
     * @param  string                                                   $current_uid
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $site_uid is not valid
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function getBBBrowserTree($site_uid, $page_uid, $current_uid = null, $firstresult = 0, $maxresult = 25, $having_child = false)
    {
        if (null === $site = $this->getEntityManager()->find('\BackBee\Site\Site', strval($site_uid))) {
            throw new InvalidArgumentException(sprintf('Site with uid `%s` does not exist', $site_uid));
        }
        $tree = array();
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            // @todo strange call to this service with another site
//$this->isGranted('VIEW', $site);
            $page = $this->_repo->getRoot($site);
            $leaf = new \stdClass();
            $leaf->attr = json_decode($page->serialize());
            $leaf->data = $page->getTitle();
            $leaf->state = $page->isLeaf() ? 'leaf' : 'open';
            $leaf->children = $this->getBBBrowserTree($site_uid, $page->getUid(), $current_uid, $firstresult, $maxresult, $having_child);
            $tree[] = $leaf;
        } else {
            try {
                $this->isGranted('VIEW', $page);
                $children = $this->_repo->getNotDeletedDescendants($page, 1, false, array("field" => "leftnode", "sort" => "asc"), true, $firstresult, $maxresult, $having_child);
                $tree['numresults'] = $children->count();
                $tree['firstresult'] = $firstresult;
                $tree['maxresult'] = $maxresult;
                $tree['results'] = array();
                if ($children->count() !== 0) {
                    foreach ($children as $child) {
                        $leaf = new \stdClass();
                        $leaf->attr = json_decode($child->serialize());
                        $leaf->attr->url = $this->getApplication()->getRouting()->getUri($leaf->attr->url, null, $child->getSite());
                        $leaf->data = $child->getTitle();
                        $leaf->state = $child->isLeaf() ? 'leaf' : 'closed';
                        if (false === $child->isLeaf() && null !== $current_uid && null !== $current = $this->_repo->find(strval($current_uid))) {
                            if ($child->isAncestorOf($current)) {
                                $leaf->children = $this->getBBBrowserTree($site_uid, $child->getUid(), $current_uid, $firstresult, $maxresult);
                                $leaf->state = 'open';
                            }
                        }
                        $tree['results'][] = $leaf;
                    }
                } else {
                    return;
                }
            } catch (\BackBee\Security\Exception\ForbiddenAccessException $e) {
                // Ignore it
            }
        }

        return $tree;
    }

    /**
     * Duplicate the page in the tree
     * @param  string                                                   $page_uid The unique identifier of the page
     * @param  string                                                   $title    The title of the clone
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $page_uid is invalid
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function cloneBBPage($page_uid, $title)
    {
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $page_uid));
        }

// User must have view permission on choosen layout
        $this->isGranted('VIEW', $page->getLayout());

// User must have create permission on page
        $this->isGranted('CREATE', $page);

        if (null !== $page->getParent()) {
            // User must have edit permission on parent
            $this->isGranted('EDIT', $page->getParent());
        } else {
            // User must have edit permission on site to add a new root
            $this->isGranted('EDIT', $this->getApplication()->getSite());
        }

        set_time_limit(0);
        $new_page = $this->_repo->duplicate($page, $title, $page->getParent(), true, $this->bbapp->getBBUserToken());

        $leaf = new \stdClass();
        $leaf->attr = new \stdClass();
        $leaf->attr->rel = 'leaf';
        $leaf->attr->id = 'node_'.$new_page->getUid();
        $leaf->data = $new_page->getTitle();
        $leaf->state = 'closed';

        return $leaf;
    }

    /**
     * Moves the page in the tree
     * @param  string                                                   $page_uid   The unique identifier of the page
     * @param  string                                                   $parent_uid The unique identifier of the new parent page
     * @param  string                                                   $next_uid   Optional, the unique identifier of the previous sibling
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $page_uid or $parent_uid are invalid
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function moveBBBrowserTree($page_uid, $parent_uid, $next_uid)
    {
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $page_uid));
        }

        if (null === $parent = $this->_repo->find(strval($parent_uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $parent_uid));
        }

// User must have edit permission on both page and parent
        $this->isGranted('EDIT', $page);
        $this->isGranted('EDIT', $parent);

        //User must have view permission on layout for move page
        $this->isGranted('VIEW', $page->getLayout());

// If the page is online, user must have publish permission on it
        if ($page->isOnline(true)) {
            $this->isGranted('PUBLISH', $page);
        }

        if (null === $next = $this->_repo->find(strval($next_uid))) {
            $this->_repo->moveAsLastChildOf($page, $parent);
        } else {
            if (false === $next->getParent()->equals($parent)) {
                throw new InvalidArgumentException('Previous sibling must have the same parent node');
            }

            $this->_repo->moveAsPrevSiblingOf($page, $next);
        }

        $this->getEntityManager()->flush($page);
        //$this->_repo->updateHierarchicalDatas($parent, $parent->getLeftnode(), $parent->getLevel());

        $leaf = new \stdClass();
        $leaf->attr = new \stdClass();
        $leaf->attr->rel = 'folder';
        $leaf->attr->id = 'node_'.$page->getUid();
        $leaf->data = $page->getTitle();
        $leaf->state = 'closed';

        return $leaf;
    }

    /**
     * Removes the page off the tree
     * @param  string                                                   $page_uid The unique identifier of the page
     * @return \stdClass                                                The serialized parent
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if $page_uid is invalid or page is root
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function delete($page_uid)
    {
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            throw new InvalidArgumentException(sprintf('None page exists with uid `%s`.', $page_uid));
        }

        if (true === $page->isRoot()) {
            throw new InvalidArgumentException('Can not remove root page of a site');
        }

// User must have edit permission on parent
        $this->isGranted('EDIT', $page->getParent());
// Add delete permission on parent to
        $this->isGranted('DELETE', $page->getParent());

// If the page is online, user must have publish permission on it
        if ($page->isOnline(true)) {
            $this->isGranted('PUBLISH', $page);
        }

        $this->_repo->toTrash($page);

        return json_decode($page->getParent()->serialize());
    }

    /**
     * Returns the serialize page to edit it
     * @param  string                                                   $page_uid The unique identifier of the page
     * @return \stdClass
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function getBBSelectorForm($page_uid)
    {
        if (null === $page = $this->_repo->find(strval($page_uid))) {
            $page = new \BackBee\NestedNode\Page();
            $page->setSite($this->getApplication()->getSite());
        } else {
            // User must have edit permission on page
            $this->isGranted('EDIT', $page);

// If the page is online, user must have publish permission on it
            if ($page->isOnline(true)) {
                $this->isGranted('PUBLISH', $page);
            }
        }

        return json_decode($page->serialize());
    }

    /**
     * Inserts or updates a Page from the posted BBSelector form
     * @param  string                                                   $page_uid   The unique identifier of the page
     * @param  string                                                   $parent_uid The unique identifier of the parent of the page
     * @param  string                                                   $title      The title
     * @param  string                                                   $url        The url (currently unused)
     * @param  string                                                   $target     The target is redirect is defined
     * @param  string                                                   $redirect   The permananet redirect URL
     * @param  string                                                   $layout_uid The unique identifier of the layout to use
     * @param  string                                                   $alttitle   The alternate title bb5 #366
     * @return \stdClass
     * @throws \BackBee\Exception\InvalidArgumentException          Occurs if the layout is undefined
     * @throws \BackBee\Exception\MissingApplicationException       Occurs if none BackBee application is defined
     * @throws \BackBee\Security\Exception\ForbiddenAccessException Occurs if the current token have not the required permission
     * @exposed(secured=true)
     */
    public function postBBSelectorForm($page_uid, $parent_uid, $title, $url, $target, $redirect, $layout_uid, $alttitle, $flag = "", $is_sibling = false, $move_node_uid = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        if (null === $layout = $this->getEntityManager()->find('\BackBee\Site\Layout', strval($layout_uid))) {
            throw new InvalidArgumentException(sprintf('None Layout exists with uid `%s`.', $layout_uid));
        }

        $parent = $this->_repo->find(strval($parent_uid));
        if (false === empty($move_node_uid)) {
            $parent = $this->_repo->find(strval($move_node_uid));
        }

        $is_final = $parent->getLayout()->getParam('final');
        if (true === $is_final) {
            if (null === $parent->getParent()) {
                throw new ServicesException('Impossible to create or move a child page for this layout.');
            }

            $leaf = $this->postBBSelectorForm($page_uid, $parent->getParent()->getUid(), $title, $url, $target, $redirect, $layout_uid, $alttitle, "", true);
        } else {
            // User must have view permission on choosen layout
            $this->isGranted('VIEW', $layout);

            if (null !== $page = $this->_repo->find(strval($page_uid))) {
                $this->isGranted('EDIT', $page);

                // If the page is online, user must have publish permission on it
                if ($page->isOnline(true)) {
                    $this->isGranted('PUBLISH', $page);
                }

                //User must have permision of current layout for change this
                $this->isGranted('VIEW', $page->getLayout());

                if (null !== $parent && false === $page->getParent()->equals($parent)) {
                    // User must have edit permission on parent
                    $this->isGranted('EDIT', $parent);
                    $this->_repo->moveAsFirstChildOf($page, $parent);
                }
            } else {
                $page = new NestedPage();

                $this->hydratePageInfosWith($page, $title, $target, $redirect, $layout, $alttitle);

                $this->getEntityManager()->persist($page);

                if (null !== $parent) {
                    // User must have edit permission on parent
                    $page->setParent($parent);
                    $this->isGranted('CREATE', $page);
                    $this->isGranted('EDIT', $parent);

                    $this->_repo->insertNodeAsFirstChildOf($page, $parent);
                } else {
                    // User must have edit permission on site to add a new root
                    $this->isGranted('CREATE', $page);
                    $this->isGranted('EDIT', $this->getApplication()->getSite());

                    $page->setSite($this->getApplication()->getSite());
                }
            }

            $this->hydratePageInfosWith($page, $title, $target, $redirect, $layout, $alttitle);

            if (false === $this->getEntityManager()->contains($page)) {
                $this->getEntityManager()->persist($page);
            }

            $this->getEntityManager()->flush($page);

            $leaf = new \stdClass();
            $leaf->attr = json_decode($page->serialize());
            $leaf->data = html_entity_decode($page->getTitle(), ENT_COMPAT, 'UTF-8');
            $leaf->state = 'closed';
            $leaf->is_sibling = $is_sibling;
            $leaf->move_node_uid = (false === empty($move_node_uid)) ? $move_node_uid : null;
        }

        return $leaf;
    }

    /**
     * Update page's attributes with this method's arguments
     * @param NestedPage $page
     * @param string     $target
     * @param string     $redirect
     * @param Layout     $layout
     */
    private function hydratePageInfosWith(NestedPage $page, $title, $target, $redirect, SiteLayout $layout, $alttitle)
    {
        $page->setTitle($title);
        $page->setTarget($target);
        $page->setRedirect('' === $redirect ? null : $redirect);
        $page->setLayout($layout);
        $page->setAltTitle($alttitle);
    }

    /**
     * @exposed(secured=true)
     */
    public function insertBBBrowserTree($title, $root_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $root = $em->find('\BackBee\NestedNode\Page', $root_uid);
        if (NULL !== $root) {
            $page = new \BackBee\NestedNode\Page();
            $page->setTitle($title)
                    ->setSite($root->getSite())
                    ->setRoot($root->getRoot())
                    ->setParent($root)
                    ->setLayout($root->getLayout())
                    ->setState(\BackBee\NestedNode\Page::STATE_HIDDEN);

            $page = $em->getRepository('\BackBee\NestedNode\Page')->insertNodeAsLastChildOf($page, $root);

            $em->persist($page);
            $em->flush();

            $leaf = new \stdClass();
            $leaf->attr = json_decode($page->serialize());
            $leaf->data = $page->getTitle();
            $leaf->state = 'leaf';

            return $leaf;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function renameBBBrowserTree($title, $page_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $page = $em->find('\BackBee\NestedNode\Page', $page_uid);

        if ($page) {
            $page->setTitle($title);

            $em->persist($page);
            $em->flush();

            return true;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorTree($site_uid, $root_uid)
    {
        $em = $this->bbapp->getEntityManager();
        $site = $em->find('\BackBee\Site\Site', $site_uid);
        $tree = array();

        if ($site) {
            if ($root_uid !== null) {
                $page = $em->find('\BackBee\NestedNode\Page', $root_uid);

                foreach ($em->getRepository('\BackBee\NestedNode\Page')->getNotDeletedDescendants($page, 1) as $child) {
                    $leaf = new \stdClass();
                    $leaf->attr = new \stdClass();
                    $leaf->attr->rel = 'folder';
                    $leaf->attr->id = 'node_'.$child->getUid();
                    $leaf->data = html_entity_decode($child->getTitle(), ENT_COMPAT, 'UTF-8');
                    $leaf->state = 'closed';

                    $children = $this->getBBSelectorTree($site_uid, $child->getUid());
                    $leaf->state = ((count($children) > 0) ? 'closed' : 'leaf');

                    $tree[] = $leaf;
                }
            } else {
                $page = $em->getRepository('\BackBee\NestedNode\Page')->getRoot($site);

                if ($page) {
                    $leaf = new \stdClass();
                    $leaf->attr = new \stdClass();
                    $leaf->attr->rel = 'root';
                    $leaf->attr->id = 'node_'.$page->getUid();
                    $leaf->data = html_entity_decode($page->getTitle(), ENT_COMPAT, 'UTF-8');
                    $leaf->state = 'closed';

                    $children = $this->getBBSelectorTree($site_uid, $page->getUid());
                    $leaf->state = ((count($children) > 0) ? 'closed' : 'leaf');

                    $tree[] = $leaf;
                }
            }
        }

        return $tree;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorView($params, $start = 0, $limit = 5)
    { //$site_uid, $page_uid, $order_sort = '_title', $order_dir = 'asc', $limit=5, $start=0
        if (is_array($params)) {
            $searchField = (array_key_exists('searchField', $params)) ? $params['searchField'] : null;
            $typeField = (array_key_exists('typeField', $params)) ? $params['typeField'] : null;
            $beforePubdateField = (array_key_exists('beforePubdateField', $params)) ? $params['beforePubdateField'] : null;
            $afterPubdateField = (array_key_exists('afterPubdateField', $params)) ? $params['afterPubdateField'] : null;
            $site_uid = (array_key_exists('site_uid', $params)) ? $params['site_uid'] : null;
            $page_uid = (array_key_exists('page_uid', $params)) ? $params['page_uid'] : null;
            $order_sort = (array_key_exists('order_sort', $params)) ? $params['order_sort'] : '_title';
            $order_dir = (array_key_exists('order_dir', $params)) ? $params['order_dir'] : 'asc';
        }

        $options = array();
        if (NULL !== $searchField) {
            $options['searchField'] = $searchField;
        }
        if (NULL !== $beforePubdateField && "" !== $beforePubdateField) {
            $options['beforePubdateField'] = $beforePubdateField;
        }
        if (NULL !== $afterPubdateField && "" !== $afterPubdateField) {
            $options['afterPubdateField'] = $afterPubdateField;
        }
        $em = $this->bbapp->getEntityManager();

        $site = $em->find('\BackBee\Site\Site', $site_uid);

        $view = array();
        $result = array();
        if ($site) {
            if ($page_uid !== null) {
                $page = $em->find('\BackBee\NestedNode\Page', $page_uid);

                $nbChildren = $em->getRepository('\BackBee\NestedNode\Page')->countChildren($page, $typeField, $options);
                $pagingInfos = array("start" => (int) $start, "limit" => (int) $limit);
                foreach ($em->getRepository('\BackBee\NestedNode\Page')->getChildren($page, $order_sort, $order_dir, $pagingInfos, $typeField, $options) as $child) {
                    $row = new \stdClass();
                    $row->uid = $child->getUid();
                    $row->title = $child->getTitle();
                    $row->url = NULL === $child->getRedirect() ? $child->getUrl() : $child->getRedirect();
                    $row->created = $child->getCreated()->format('r');
                    $row->modified = $child->getModified()->format('r');

                    $view[] = $row;
                }
            } else {
                $page = $em->getRepository('\BackBee\NestedNode\Page')->getRoot($site);
                $nbChildren = 1;
                $row = new \stdClass();
                $row->uid = $page->getUid();
                $row->title = $page->getTitle();
                $row->url = NULL === $page->getRedirect() ? $page->getUrl() : $page->getRedirect();
                $row->created = $page->getCreated()->format('c');
                $row->modified = $page->getModified()->format('c');

                $view[] = $row;
            }
            $result = array("numResults" => $nbChildren, "views" => $view);
        }

        return $result;
    }
}
