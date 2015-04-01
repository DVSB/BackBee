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

use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\NestedNode\Media;
use BackBee\NestedNode\MediaFolder;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Rest\Controller\ARestController;
use BackBee\Utils;

/**
 * Description of MediaController
 *
 * @author h.baptiste <harris.baptiste@lp-digital.fr>
 */
class MediaController extends AbstractRestController
{

    /**
     * @param Request $request
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction(Request $request, $start, $count)
    {
        $this->preloadMediaClasses();
        $queryParams = $request->query->all();
        $mediaFolderUid = $request->get("mediaFolder_uid", null);
        if (null === $mediaFolderUid) {
            throw new BadRequestHttpException('A mediaFolder uid should be provided!');
        }
        $mediaFolder = $this->getMediaFolderRepository()->find($mediaFolderUid);
        if (null === $mediaFolder) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("The mediaFolder can't be found!");
        }
        $paging = array("start" => $start, "limit" => $count);
        $paginator = $this->getMediaRepository()->getMedias($mediaFolder, $queryParams, "_modified", "desc", $paging);
        $results = [];
        foreach ($paginator as $media) {
            $results[] = $media;
        }
        $response = $this->createJsonResponse($this->mediaToJson($results));
        return $this->addRangeToContent($response, $paginator, $start, $count);
    }

    private function mediaToJson($collection)
    {
        $result = array();
        foreach ($collection as $media) {
            $content = $media->getContent();

            if (null !== $draft = $this->getClassContentManager()->getDraft($content)) {
                $content->setDraft($draft);
            }

            $mediaJson = $media->jsonSerialize();
            $contentJson = $this->getClassContentManager()->jsonEncode($media->getContent());
            $mediaJson['image'] = $contentJson['image'];
            $result[] = $mediaJson;
        }

        return $result;
    }

    private function addRangeToContent(Response $response, Paginator $collection, $start, $count)
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
     * @param type $id
     * @return type
     * @throws BadRequestHttpException
     */
    public function deleteAction($id = null)
    {
        $this->preloadMediaClasses();
        if (null === $id) {
            throw new BadRequestHttpException("A media id should be provided!");
        }
        $media = $this->getMediaRepository()->find($id);
        if (!$media) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('media can\'t be found');
        }
        try {
            $this->getEntityManager()->getRepository('BackBee\ClassContent\AClassContent')->deleteContent($media->getContent());
            $this->getEntityManager()->remove($media);
            $this->getEntityManager()->flush();
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Error while deleting the media: " . $e->getMessage());
        }
        return $this->createJsonResponse(null, 204);
    }

    private function preloadMediaClasses()
    {
        $media = $this->getApplication()->getAutoloader()->glob('Media' . DIRECTORY_SEPARATOR . '*');
        foreach ($media as $mediaClass) {
            class_exists($mediaClass);
        }
    }

    /**
     * Update media content's and folder
     */
    public function putAction($id, Request $request)
    {
        $this->preloadMediaClasses();
        $media = $this->getMediaRepository()->find($id);
        $media_title = $request->get("title", "Untitled media");
        $mediaContentUid = $request->get("content_uid");
        $mediaType = $request->get("type", null);
        if (!$media) {
            throw new BadRequestHttpException('media can\'t be found');
        }
        $media->setTitle($media_title);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();
        return $this->createJsonResponse(null, 204);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     * @throws BadRequestHttpException
     */
    public function postAction(Request $request)
    {
        $media_id = $request->get("id", null);
        $mediaContentUid = $request->get("content_uid");
        $mediaType = $request->get("type", null);
        $mediaFolder_uid = $request->get("folder_uid", null);
        $media_title = $request->get("title", "Untitled media");

        $content = $this->getClassContentManager()->findOneByTypeAndUid($mediaType, $mediaContentUid, true, true);
        $mediaFolder = $this->getMediaFolderRepository()->find($mediaFolder_uid);

        if (!$media_id) {
            $media = new Media();
        } else {
            $media = $this->getMediaRepository()->find($media_id);
            if (!$media) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Media can't be found!");
            }
        }
        $media->setContent($content);
        $media->setTitle($media_title);
        $media->setMediaFolder($mediaFolder);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();
        return $this->createJsonResponse(null, 201, [
                    'BB-RESOURCE-UID' => $media->getId(),
                    'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                            'bb.rest.media.get', [
                        'version' => $request->attributes->get('version'),
                        'uid' => $media->getId()
                            ], '', false
                    ),
                ]);
    }

    private function getMediaRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\Media');
    }

    private function getMediaFolderRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\MediaFolder');
    }

    private function getClassContentManager()
    {
        $manager = $this->getApplication()->getContainer()->get('classcontent.manager')
                ->setBBUserToken($this->getApplication()->getBBUserToken());

        return $manager;
    }

}
