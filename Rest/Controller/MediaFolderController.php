<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Rest\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\Exception\InvalidArgumentException;
use BackBee\NestedNode\MediaFolder;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\Rest\Patcher\OperationSyntaxValidator;
use BackBee\Utils;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Description of MediaFolderController
 *
 * @author h.baptiste <harris.baptiste@lp-digital.fr>
 */
class MediaFolderController extends AbstractRestController
{

    /**
     * Get collection of media folder
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=100, max_count=200)
     *
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBee\NestedNode\MediaFolder", required=false
     * )
     *
     */
    public function getCollectionAction($start, MediaFolder $parent = null)
    {
        $results = $this->getMediaFolderRepository()->getMediaFolders($parent,array("field"=>"_leftnode", "dir"=>"asc"));
        $response = $this->createJsonResponse($results);

        return $this->addRangeToContent($response, $results, $start);
    }

    public function addRangeToContent(Response $response, $collection, $start)
    {
        $count = count($collection);
        if ($collection instanceof Paginator) {
            $count = count($collection->getIterator());
        }
        $lastResult = $start + $count - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/" . count($collection));
        return $response;
    }

    /**
     *
     * @param MediaFolder $mediaFolder
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     *
     */
    public function getAction(MediaFolder $mediaFolder)
    {
        return $this->createResponse($this->formatItem($mediaFolder));
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\RequestParam(name="title", description="media title", requirements={
     *      @Assert\NotBlank()
     * })
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     *
     */
    public function putAction(MediaFolder $mediaFolder, Request $request)
    {
        $parent_id = $request->get("parent_uid", null);
        if (null === $parent_id) {
            $parent = $this->getMediaFolderRepository()->getRoot();
        }else{
            $parent = $this->getMediaFolderRepository()->find($parent_id);
        }

        $title = trim($request->request->get('title'));
        if ($this->mediaFolderAlreadyExists($title, $parent)) {
            throw new BadRequestHttpException("A MediaFolder named '" . $title . "' already exists.");
        }
        $mediaFolder->setTitle($request->request->get('title'));
        $this->getEntityManager()->persist($mediaFolder);
        $this->getEntityManager()->flush();
        return $this->createJsonResponse(null, 204);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     */
    public function deleteAction(MediaFolder $mediaFolder)
    {
        if (true === $mediaFolder->isRoot()) {
            throw new BadRequestHttpException('Cannot remove the root node of the MediaFodler');
        }
        $isEmpty = $this->getMediaRepository()->countMedias($mediaFolder);
        if (!$isEmpty) {
            $response = new Response("", 204);
            $this->getMediaFolderRepository()->delete($mediaFolder);
        } else {
            $response = new Response("MediaFolder [".$mediaFolder->getTitle()."] is not empty", 500);
        }
        return $response;
    }

    private function getMediaRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\Media');
    }

    /**
     * Create a media folder
     * and if a parent is provided added has its last child
     * @param MediaFolder $mediaFolder
     *
     * @Rest\RequestParam(name="title", description="media title", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\NestedNode\MediaFolder", required=false
     * )
     */
    public function postAction(Request $request, $parent = null)
    {
        try {
            $title = trim($request->request->get('title'));
            $uid = $request->request->get("uid", null);
            if ($uid) {
                $mediaFolder = $this->getMediaFolderRepository()->find($request->request->get("uid"));
                $mediaFolder->setTitle($title);
            } else {
                $mediaFolder = new MediaFolder();
                $mediaFolder->setUrl($request->request->get('url', Utils\String::urlize($title)));
                $mediaFolder->setTitle($title);
                if (null === $parent) {
                    $parent = $this->getMediaFolderRepository()->getRoot();
                }
                if ($this->mediaFolderAlreadyExists($title, $parent)) {
                    throw new BadRequestHttpException("A MediaFolder named '" . $title . "' already exists. in [" . $parent->getTitle() . "]");
                }

                $mediaFolder->setParent($parent);
                $this->getMediaFolderRepository()->insertNodeAsLastChildOf($mediaFolder, $parent);
            }

            $this->getEntityManager()->persist($mediaFolder);
            $this->getEntityManager()->flush();
            return $this->createJsonResponse(null, 201, [
                        'BB-RESOURCE-UID' => $mediaFolder->getUid(),
                        'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                                'bb.rest.media-folder.get', [
                            'version' => $request->attributes->get('version'),
                            'uid' => $mediaFolder->getUid()
                                ], '', false
                        ),
                    ]);
        } catch (Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage());
        }
    }

    /**
     * @param \BackBee\NestedNode\MediaFolder $mediaFolder
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     * @return type
     */
    public function patchAction(MediaFolder $mediaFolder, Request $request)
    {
        $operations = $request->request->all();
        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: ' . $e->getMessage());
        }

        $this->patchSiblingAndParentOperation($mediaFolder, $operations);
        $this->getEntityManager()->flush();
        return $this->createJsonResponse(null, 204);
    }

    private function getMediaFolderRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\MediaFolder');
    }

    private function patchSiblingAndParentOperation(MediaFolder $mediaFolder, &$operations)
    {
        $sibling_operation = null;
        $parent_operation = null;

        foreach ($operations as $key => $operation) {
            $op = array('key' => $key, 'op' => $operation);
            if ('/sibling_uid' === $operation['path']) {
                $sibling_operation = $op;
            } elseif ('/parent_uid' === $operation['path']) {
                $parent_operation = $op;
            }
        }

        if (null !== $sibling_operation || null !== $parent_operation) {
            if ($mediaFolder->isRoot()) {
                throw new BadRequestHttpException('Cannot move root node of a site.');
            }
            try {
                if (null !== $sibling_operation) {
                    unset($operations[$sibling_operation['key']]);

                    $sibling = $this->getMediaFolderByUid($sibling_operation['op']['value']);
                    $this->getMediaFolderRepository()->moveAsPrevSiblingOf($mediaFolder, $sibling);
                } elseif (null !== $parent_operation) {
                    unset($operations[$parent_operation['key']]);

                    $parent = $this->getMediaFolderByUid($parent_operation['op']['value']);
                    $this->getMediaFolderRepository()->moveAsLastChildOf($mediaFolder, $parent);
                }
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid node move action: ' + $e->getMessage());
            }
        }
    }

    private function getMediaFolderByUid($uid)
    {
        if (null === $mediaFolder = $this->getMediaFolderRepository()->find($uid)) {
            throw new NotFoundHttpException("Unable to find mediaFolder with uid `$uid`");
        }
        return $mediaFolder;
    }

    private function mediaFolderAlreadyExists($title, MediaFolder $parent)
    {
        $folderExists = false;
        $medialFolder = $this->getMediaFolderRepository()->findOneBy(array("_title" => trim($title), "_parent" => $parent));
        if ($medialFolder) {
            $folderExists = true;
        }
        return $folderExists;
    }

}
?>

