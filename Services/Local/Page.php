<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Exception\ServicesException;
use BackBuilder\Services\Local\AbstractServiceLocal;
use BackBuilder\Util\String;
use BackBuilder\NestedNode\Page as NestedPage;

/**
 * Description of Page
 *
 * @copyright   Lp system
 * @author      m.baptista
 */
class Page extends AbstractServiceLocal
{

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
        $page = $em->getRepository('\BackBuilder\NestedNode\Page')->find($uid);

        if (NULL === $page)
            throw new ServicesException(sprintf('Unable to find page for `%s` uid', $uid));

        $newpage = clone $page;
    }

    /**
     * Return the serialized form of a page
     * @exposed(secured=true)
     */
    public function find($uid)
    {
        $em = $this->bbapp->getEntityManager();
        $page = $em->getRepository('\BackBuilder\NestedNode\Page')->find($uid);

        if (NULL === $page)
            throw new ServicesException(sprintf('Unable to find page for `%s` uid', $uid));

        $opage = json_decode($page->serialize());
        if (NULL !== $this->bbapp->getRenderer()) {
            $opage->url = $this->bbapp->getRenderer()->getUri($opage->url);
            $opage->redirect = $this->bbapp->getRenderer()->getUri($opage->redirect);
        }

        $defaultmeta = new \BackBuilder\MetaData\MetaDataBag($this->bbapp->getConfig()->getSection('metadata'));
        $opage->metadata = (null === $opage->metadata) ? $defaultmeta->toArray() : array_merge($defaultmeta->toArray(), $page->getMetadata()->toArray());

        return $opage;
    }

    /**
     * Update a page
     * @exposed(secured=true)
     */
    public function update($serialized)
    {
        $object = json_decode($serialized);
        if (FALSE === property_exists($object, 'uid'))
            throw new ServicesException('An uid has to be provied');

        if (TRUE === property_exists($object, 'url') && NULL !== $this->bbapp->getRenderer()) {
            $object->url = $this->bbapp->getRenderer()->getRelativeUrl($object->url);

            if ('/' == $redirect = $this->bbapp->getRenderer()->getRelativeUrl($object->redirect))
                $object->redirect = null;
        }

        $em = $this->bbapp->getEntityManager();
        $page = $em->getRepository('\BackBuilder\NestedNode\Page')->find($object->uid);
        if (NULL === $page)
            throw new ServicesException(sprintf('Unable to find page for `%s` uid', $object->uid));

        $page->unserialize($object);
        $em->flush();

        return array('url' => $page->getUrl(), 'state' => $page->getState());
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBBrowserTree($site_uid, $root_uid, $current_uid = null)
    {
        $em = $this->bbapp->getEntityManager();

        $site = $em->find('\BackBuilder\Site\Site', $site_uid);

        if (NULL === $site)
            return false;

        $tree = array();

        if (NULL === $root_uid) {
            $page = $em->getRepository('\BackBuilder\NestedNode\Page')->getRoot($site);

            $leaf = new \stdClass();
            $leaf->attr = json_decode($page->serialize());
            $leaf->data = $page->getTitle();
            $leaf->state = $page->isLeaf() ? 'leaf' : 'open';
            $leaf->children = $this->getBBBrowserTree($site_uid, $page->getUid(), $current_uid, array("field" => "leftnode", "sort" => "desc"));

            $tree[] = $leaf;
        } else {
            $page = $em->find('\BackBuilder\NestedNode\Page', $root_uid);

            if (NULL === $page)
                return false;

            //$children = $em->getRepository('\BackBuilder\NestedNode\Page')->getNotDeletedDescendants($page, 1, FALSE, array(\BackBuilder\NestedNode\Page::STATE_ONLINE, \BackBuilder\NestedNode\Page::STATE_OFFLINE, \BackBuilder\NestedNode\Page::STATE_HIDDEN));
            $children = $em->getRepository('\BackBuilder\NestedNode\Page')->getNotDeletedDescendants($page, 1, FALSE, array("field" => "leftnode", "sort" => "asc"));
            foreach ($children as $child) {
                $leaf = new \stdClass();
                $leaf->attr = json_decode($child->serialize());
                $leaf->data = $child->getTitle();
                $leaf->state = $child->isLeaf() ? 'leaf' : 'closed';

                if (!$child->isLeaf() && NULL !== $current_uid && NULL !== $current = $em->getRepository('\BackBuilder\NestedNode\Page')->find($current_uid)) {
                    if ($child->isAncestorOf($current)) {
                        $leaf->children = $this->getBBBrowserTree($site_uid, $child->getUid(), $current_uid, array("field" => "leftnode", "sort" => "asc"));
                        $leaf->state = 'open';
                    }
                }

                $tree[] = $leaf;
            }
        }

        return $tree;
    }

    /**
     * @exposed(secured=true)
     */
    public function insertBBBrowserTree($title, $root_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $root = $em->find('\BackBuilder\NestedNode\Page', $root_uid);
        if (NULL !== $root) {
            $page = new \BackBuilder\NestedNode\Page();
            $page->setTitle($title)
                    ->setSite($root->getSite())
                    ->setRoot($root->getRoot())
                    ->setParent($root)
                    ->setLayout($root->getLayout())
                    ->setState(\BackBuilder\NestedNode\Page::STATE_HIDDEN);

            $page = $em->getRepository('\BackBuilder\NestedNode\Page')->insertNodeAsLastChildOf($page, $root);

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

        $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);

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
    public function cloneBBPage($page_uid, $title)
    {
        set_time_limit(0);
        
        $em = $this->bbapp->getEntityManager();
        /* @var $page \BackBuilder\NestedNode\Page */
        $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);

        if (NULL === $page) {
            throw new ServicesException(sprintf('Unable to find page for `%s` uid', $page_uid));
        }

        $new_page = $em->getRepository('\BackBuilder\NestedNode\Page')
                ->duplicate($page, $title, $page->getParent(), true, $this->bbapp->getBBUserToken());
        
        $leaf = new \stdClass();
        $leaf->attr = new \stdClass();
        $leaf->attr->rel = 'leaf';
        $leaf->attr->id = 'node_' . $new_page->getUid();
        $leaf->data = $new_page->getTitle();
        $leaf->state = 'closed';

        return $leaf;
    }

    /**
     * @exposed(secured=true)
     */
    public function moveBBBrowserTree($page_uid, $root_uid, $next_uid)
    {
        set_time_limit(0);
        
        $em = $this->bbapp->getEntityManager();

        $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);
        $root = $em->find('\BackBuilder\NestedNode\Page', $root_uid);

        if (!is_null($root) && !is_null($page)) {
            if (null === $next_uid) {
                $em->getRepository('\BackBuilder\NestedNode\Page')->moveAsLastChildOf($page, $root);
                $em->flush();
            } else if (null !== $next = $em->find('\BackBuilder\NestedNode\Page', $next_uid)) {
                $em->getRepository('\BackBuilder\NestedNode\Page')->moveAsNextSiblingOf($page, $next);
                $em->flush();                
            }
             
            $em->getRepository('\BackBuilder\NestedNode\Page')->updateHierarchicalDatas($root, $root->getLeftnode(), $root->getLevel());
            $em->refresh($page);
            
            /* check if page has children */
            $leaf = new \stdClass();
            $leaf->attr = new \stdClass();
            $leaf->attr->rel = 'folder';
            $leaf->attr->id = 'node_' . $page->getUid();
            $leaf->data = $page->getTitle();
            $leaf->state = 'closed';
            return $leaf;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function delete($uid)
    {
        $em = $this->bbapp->getEntityManager();

        if (NULL !== $page = $em->find('\BackBuilder\NestedNode\Page', $uid)) {
            if ($page->isRoot())
                throw new ServicesException('Can not remove root page of the site');

            $em->getRepository('\BackBuilder\NestedNode\Page')->toTrash($page);
            return json_decode($page->getParent()->serialize());
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorTree($site_uid, $root_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $site = $em->find('\BackBuilder\Site\Site', $site_uid);
        $tree = array();

        if ($site) {
            if ($root_uid !== null) {
                $page = $em->find('\BackBuilder\NestedNode\Page', $root_uid);

                foreach ($em->getRepository('\BackBuilder\NestedNode\Page')->getNotDeletedDescendants($page, 1) as $child) {
                    $leaf = new \stdClass();
                    $leaf->attr = new \stdClass();
                    $leaf->attr->rel = 'folder';
                    $leaf->attr->id = 'node_' . $child->getUid();
                    $leaf->data = html_entity_decode($child->getTitle(), ENT_COMPAT, 'UTF-8');
                    $leaf->state = 'closed';

                    $children = $this->getBBSelectorTree($site_uid, $child->getUid());
                    $leaf->state = ((count($children) > 0) ? 'closed' : 'leaf');

                    $tree[] = $leaf;
                }
            } else {
                $page = $em->getRepository('\BackBuilder\NestedNode\Page')->getRoot($site);

                if ($page) {
                    $leaf = new \stdClass();
                    $leaf->attr = new \stdClass();
                    $leaf->attr->rel = 'root';
                    $leaf->attr->id = 'node_' . $page->getUid();
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
        if (NULL !== $searchField)
            $options['searchField'] = $searchField;
        if (NULL !== $beforePubdateField && "" !== $beforePubdateField)
            $options['beforePubdateField'] = $beforePubdateField;
        if (NULL !== $afterPubdateField && "" !== $afterPubdateField)
            $options['afterPubdateField'] = $afterPubdateField;


        //var_dump($options);

        $em = $this->bbapp->getEntityManager();

        $site = $em->find('\BackBuilder\Site\Site', $site_uid);

        $view = array();
        $result = array();
        if ($site) {
            if ($page_uid !== null) {
                $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);

                $nbChildren = $em->getRepository('\BackBuilder\NestedNode\Page')->countChildren($page, $typeField, $options);
                $pagingInfos = array("start" => (int) $start, "limit" => (int) $limit);
                foreach ($em->getRepository('\BackBuilder\NestedNode\Page')->getChildren($page, $order_sort, $order_dir, $pagingInfos, $typeField, $options) as $child) {
                    $row = new \stdClass();
                    $row->uid = $child->getUid();
                    $row->title = $child->getTitle();
                    $row->url = NULL === $child->getRedirect() ? $child->getUrl() : $child->getRedirect();
                    $row->created = $child->getCreated()->format('r');
                    $row->modified = $child->getModified()->format('r');

                    $view[] = $row;
                }
            } else {
                $page = $em->getRepository('\BackBuilder\NestedNode\Page')->getRoot($site);
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

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorForm($page_uid)
    {
        $em = $this->bbapp->getEntityManager();
        
        $page = new \stdClass();
        $page->uid = null;
        $page->title = null;
        $page->url = null;
        $page->target = \BackBuilder\NestedNode\Page::DEFAULT_TARGET;
        $page->layout_uid = null;

        if ($page_uid) {
            $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);

            if ($page) {
                $page->uid = $page->getUid();
                $page->title = html_entity_decode($page->getTitle(), ENT_COMPAT, 'UTF-8');
                $page->url = $page->getUrl();
                $page->target = $page->getTarget();
                $page->redirect = $page->getRedirect();
                $page->layout_uid = $page->getLayout()->getUid();
            }
        }

        return $page;
    }

    /**
     * @exposed(secured=true)
     */
    public function postBBSelectorForm($page_uid, $root_uid, $title, $url, $target, $redirect, $layout_uid, $flag)
    {
        $em = $this->bbapp->getEntityManager();

        $layout = null;
        $root = null;
        $page = null;

        if ($layout_uid !== null)
            $layout = $em->find('\BackBuilder\Site\Layout', $layout_uid);
        if ($root_uid !== null)
            $root = $em->find('\BackBuilder\NestedNode\Page', $root_uid);

        if ($layout) {
            if ($page_uid !== null) {
                $page = $em->find('\BackBuilder\NestedNode\Page', $page_uid);
                if ($page) {
                    $page->setTitle($title);
                    $page->setTarget($target);
                    $page->setLayout($layout);
                    $page->setRedirect('' == $redirect ? NULL : $redirect);

                    $em->flush();
                }
            } else if ($root) {
                $page = new \BackBuilder\NestedNode\Page();
                $page->setTitle($title);
                $page->setTarget($target);
                $page = $em->getRepository('\BackBuilder\NestedNode\Page')->insertNodeAsFirstChildOf($page, $root);
                $page->setRedirect('' == $redirect ? NULL : $redirect);
                $page->setLayout($layout)
                        ->setState(\BackBuilder\NestedNode\Page::STATE_HIDDEN);

                $em->flush();
            }

            if (null !== $page) {
                $leaf = new \stdClass();
                $leaf->attr = json_decode($page->serialize());
                $leaf->data = html_entity_decode($page->getTitle(), ENT_COMPAT, 'UTF-8');
                $leaf->state = 'closed';

                return $leaf;
            }
        }

        throw new \Symfony\Component\Config\Definition\Exception\Exception('Undefined root/layout provided');
    }

    /**
     * @exposed(secured=true)
     */
    public function searchPage($parameters = array())
    {
        $searchField = (isset($parameters['searchField'])) ? $parameters['searchField'] : NULL;
        $searchField = (isset($parameters['searchField'])) ? $parameters['searchField'] : NULL;
        $searchField = (isset($parameters['searchField'])) ? $parameters['searchField'] : NULL;
    }

}
