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

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal,
    BackBuilder\Util\String;
use BackBuilder\Services\Exception\ServicesException;

/**
 * Description of Media folder
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class MediaFolder extends AbstractServiceLocal
{

    const MEDIAFOLDER_TITLE = "Médiathèque";

    private function createRoot()
    {
        try {
            $mediaFolderRoot = $this->em->getRepository("\BackBuilder\NestedNode\MediaFolder")->getRoot();
            if (is_null($mediaFolderRoot)) {
                $mediaFolderRoot = new \BackBuilder\NestedNode\MediaFolder();
                $mediaFolderRoot->setTitle(self::MEDIAFOLDER_TITLE);
                $mediaFolderRoot->setLeftnode(1);
                $mediaFolderRoot->setRightnode(2);
                $mediaFolderRoot->setLevel(1);
                $this->em->persist($mediaFolderRoot);
                $this->em->flush();
            }
        } catch (\Exception $e) {
            throw new ServicesException("Error while creating the root media folder!");
        }
        return $mediaFolderRoot;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBBrowserTree($root_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $tree = array();

        if ($root_uid !== null) {
            $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $root_uid);

            foreach ($em->getRepository('\BackBuilder\NestedNode\MediaFolder')->getDescendants($mediafolder, 1) as $child) {
                $leaf = new \stdClass();
                $leaf->attr = new \stdClass();
                $leaf->attr->rel = 'folder';
                $leaf->attr->id = 'node_' . $child->getUid();
                $leaf->attr->state = 1;
                $leaf->data = $child->getTitle();
                $leaf->state = 'closed';

                $children = $this->getBBBrowserTree($child->getUid());
                $leaf->state = ((count($children) > 0) ? 'closed' : 'leaf');

                $tree[] = $leaf;
            }
        } else {
            $mediafolder = $em->getRepository('\BackBuilder\NestedNode\MediaFolder')->getRoot();
            /* create a root */
            if (is_null($mediafolder)) {
                $mediafolder = $this->createRoot();
            }
            if ($mediafolder) {
                $leaf = new \stdClass();
                $leaf->attr = new \stdClass();
                $leaf->attr->rel = 'root';
                $leaf->attr->id = 'node_' . $mediafolder->getUid();
                $leaf->attr->state = 1;
                $leaf->data = $mediafolder->getTitle();
                $leaf->state = 'closed';

                /* traitement content node */
                if ($this->isContentTypeMediaFolder($mediafolder)) {
                    $leaf->type = "contentType";
                }

                $children = $this->getBBBrowserTree($mediafolder->getUid());
                $leaf->state = ((count($children) > 0) ? 'closed' : 'leaf');

                $tree[] = $leaf;
            }
        }

        return $tree;
    }

    private function isContentTypeMediaFolder($mediaFolder)
    {
        $pattern = "/ClassContent/i";
        $result = array();
        $match = preg_match($pattern, $mediaFolder->getTitle(), $result);
        return $match;
    }

    /**
     * @exposed(secured=true)
     */
    public function insertBBBrowserTree($title, $root_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $root = $em->find('\BackBuilder\NestedNode\MediaFolder', $root_uid);

        if ($root) {
            $mediafolder = new \BackBuilder\NestedNode\MediaFolder();
            $mediafolder->setTitle($title);

            $mediafolder = $em->getRepository('\BackBuilder\NestedNode\MediaFolder')->insertNodeAsFirstChildOf($mediafolder, $root);

            $mediafolder->setUrl($mediafolder->getParent()->getUrl() . '/' . String::urlize($mediafolder->getTitle()));

            $em->persist($mediafolder);
            $em->flush();

            $leaf = new \stdClass();
            $leaf->attr = new \stdClass();
            $leaf->attr->rel = 'folder';
            $leaf->attr->id = 'node_' . $mediafolder->getUid();
            $leaf->attr->state = 1;
            $leaf->data = $mediafolder->getTitle();
            $leaf->state = 'leaf';

            return $leaf;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function renameBBBrowserTree($title, $mediafolder_uid)
    {
        $em = $this->bbapp->getEntityManager();

        $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $mediafolder_uid);

        if ($mediafolder) {
            $mediafolder->setTitle($title);
            $mediafolder->setUrl($mediafolder->getParent()->getUrl() . '/' . String::urlize($mediafolder->getTitle()));

            $em->persist($mediafolder);
            $em->flush();

            return true;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function moveBBBrowserTree($mediafolder_uid, $root_uid, $next_uid)
    {
        $em = $this->bbapp->getEntityManager();
        $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $mediafolder_uid);
        $root = $em->find('\BackBuilder\NestedNode\MediaFolder', $root_uid);

        if (!is_null($root) && !is_null($mediafolder)) {
            $mediafolder = $em->getRepository('\BackBuilder\NestedNode\MediaFolder')->insertNodeAsLastChildOf($mediafolder, $root);
            $next = ((NULL != $next_uid) ? $em->find('\BackBuilder\NestedNode\MediaFolder', $next_uid) : null);
            if (!is_null($next)) {
                $em->getRepository('\BackBuilder\NestedNode\MediaFolder')->moveAsPrevSiblingOf($mediafolder, $next);
            }
            $em->flush();

            $leaf = new \stdClass();
            $leaf->attr = new \stdClass();
            $leaf->attr->rel = 'folder';
            $leaf->attr->id = 'node_' . $mediafolder->getUid();
            $leaf->attr->state = 1;
            $leaf->data = $mediafolder->getTitle();
            $leaf->state = 'closed';

            return $leaf;
        }

        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function deleteBBBrowserTree($mediafolder_uid)
    {
        $em = $this->bbapp->getEntityManager();
        $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $mediafolder_uid);
        if ($mediafolder) {
            return $em->getRepository('\BackBuilder\NestedNode\MediaFolder')->delete($mediafolder, $this->bbapp);
        }
        return false;
    }

    /**
     * @exposed(secured=true)
     */
    public function delete($uid)
    {
        if (null === $folder = $this->em->find('\BackBuilder\NestedNode\MediaFolder', $uid))
            throw new ServicesException(sprintf('Unable to delete media folder for `%s` uid', $uid));

        if (true === $folder->isRoot())
            throw new ServicesException('mediaselector.error.is_root');

        if (0 < $this->em->getRepository("\BackBuilder\NestedNode\Media")->countMedias($folder))
            throw new ServicesException('mediaselector.error.is_not_empty');

        return $this->em->getRepository('\BackBuilder\NestedNode\MediaFolder')->delete($folder);
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorView($params = array(), $mediafolder_uid, $order_sort = '_title', $order_dir = 'asc', $limit = 5, $start = 0)
    {
        $em = $this->bbapp->getEntityManager();
        $renderer = $this->bbapp->getRenderer();

        $view = array();
        $result = array("numResults" => 0, "views" => $view);

        if (NULL !== $mediafolder_uid) {
            $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $mediafolder_uid);
            $pagingInfos = array("start" => (int) $start, "limit" => (int) $limit);

            if (NULL !== $mediafolder) {
                if (FALSE !== $classnames = $this->bbapp->getAutoloader()->glob('Media' . DIRECTORY_SEPARATOR . '*'))
                    foreach ($classnames as $classname)
                        class_exists($classname);

                $nbContent = $em->getRepository("\BackBuilder\NestedNode\Media")->countMedias($mediafolder, $params);

                foreach ($em->getRepository('\BackBuilder\NestedNode\Media')->getMedias($mediafolder, $params, $order_sort, $order_dir, $pagingInfos) as $media) {
                    $media_content = $media->getContent();
                    $row = new \stdClass();
                    $row->id = $media->getId();
                    $row->mediafolder_uid = $media->getMediaFolder()->getUid();
                    $row->title = $media->getTitle();
                    $row->content = new \stdClass();
                    $row->content->uid = $media_content->getUid();
                    $row->content->classname = get_class($media_content);
                    $row->content->url = $renderer->render($media_content, 'bbselector_value');
                    $row->created = $media->getCreated()->format('c');
                    $row->modified = $media->getModified()->format('c');

                    $view[] = Array(
                        'html' => $renderer->render($media_content, 'bbselector_view'),
                        'media' => $row
                    );
                }
                $result = array("numResults" => $nbContent, "views" => $view);
            }
        }

        return $result;
    }

}