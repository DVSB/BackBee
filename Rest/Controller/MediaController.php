<?php

namespace BackBee\Rest\Controller;

use BackBee\Rest\Controller\ARestController;
use DateTime;
use stdClass;
use BackBee\NestedNode\MediaFolder;
use BackBee\NestedNode\Media;
use BackBee\Utils;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use BackBee\Rest\Controller\Annotations as Rest;

/**
 * Description of MediaController
 *
 * @author h.baptiste
 */
class MediaController extends ARestController
{

    /**
     * @param Request $request
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction(Request $request, $start, $count)
    {
        /* collection */
        $this->preloadMediaClasses();
        $qb = $this->getMediaRepository()->createQueryBuilder("media");
        $mediaFolder = $request->get("mediaFolder_uid", null);
        $qb = $this->doSeach($qb, $request);
        $qb->andWhere("media._media_folder = :mediaFolder");
        $qb->orderBy("media._modified", "desc");
        $qb->setParameter('mediaFolder', $mediaFolder);
        $paginator = new Paginator($qb->setFirstResult($start)->setMaxResults($count));
        $results = [];
        foreach ($paginator as $media) {
            $results[] = $media;
        }
        $response = $this->createJsonResponse($this->mediaToJson($results));
        return $this->addRangeToContent($response, $paginator, $start, $count);
    }

    private function doSeach($qb, Request $request) {
        $title = $request->get('title', null);
        $pubBefore = $request->get('beforeDate', null);
        $pubAfter = $request->get('afterDate', null);
        if(null !== $title)
        $qb->andWhere("media._title Like :title ");
        if(null !== $title) {
            $qb->setParameter("title", '%'.$title.'%');
        }
        if(null !== $pubBefore) {
            $qb->andWhere("media._created >= :beforeDate");
            $qb->setParameter("beforeDate", $pubBefore * 1000);
        }
        if(null !== $pubAfter) {
            $qb->andWhere("media._created >= :afterDate");
            $qb->setParameter("afterDate", $pubBefore * 1000);
        }
        return $qb;
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

    private function addRangeToContent(Response $response, $collection, $start, $count)
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
    public function deleteAction($id)
    {
        $this->preloadMediaClasses();
        $media = $this->getMediaRepository()->find($id);
        if (!$media) {
            throw new BadRequestHttpException('media can\'t be found');
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
       foreach($media as $mediaClass) {
            class_exists($mediaClass);
       }
    }

    /**
     * Update media content's and folder
     */
    public function putAction($id, Request $request)
    {
        $media = $this->getMediaRepository()->find($id);
        $media_title = $request->get("title", "Untitled media");
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
                throw new BadRequestHttpException('media can\'t be found' . $e->getMessage());
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