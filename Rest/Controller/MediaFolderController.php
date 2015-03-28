<?php

namespace BackBee\Rest\Controller;

use BackBee\NestedNode\MediaFolder;
use BackBee\Utils;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Rest\Patcher\EntityPatcher;
use BackBee\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\Rest\Patcher\OperationSyntaxValidator;
use BackBee\Rest\Patcher\RightManager;

/**
 * Description of MediaFolderController
 *
 * @author h.baptiste
 */
class MediaFolderController extends AbstractRestController
{

    /**
     * Get collection of media folder
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBee\NestedNode\MediaFolder", required=false
     * )
     *
     */
    public function getCollectionAction(Request $request, $start, $count, MediaFolder $parent = null)
    {
        $qb = $this->getMediaFolderRepository()->createQueryBuilder("mf");
        $qb->andParentIs($parent);
        $paginator = new Paginator($qb->setFirstResult($start)->setMaxResults($count));
        $results = [];
        foreach ($paginator as $mediaFolder) {
            $results[] = $mediaFolder;
        }
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
     * @return Symfony\Component\HttpFoundation\Response
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
     * @return Symfony\Component\HttpFoundation\Response
     * @Rest\RequestParam(name="title", description="media title", requirements={
     *      @Assert\NotBlank()
     * })
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     *
     */
    public function putAction(MediaFolder $mediaFolder, Request $request)
    {
        $title = trim($request->request->get('title'));
        if ($this->mediaFolderAlreadyExists($title)) {
            throw new BadRequestHttpException("A MediaFolder named '" . $title . "' already exists.");
        }
        $mediaFolder->setTitle($request->request->get('title'));
        $this->getEntityManager()->persist($mediaFolder);
        $this->getEntityManager()->flush();
        return $this->createJsonResponse(null, 204);
    }

    /**
     * @return Symfony\Component\HttpFoundation\Response
     * @Rest\ParamConverter(name="mediaFolder", class="BackBee\NestedNode\MediaFolder")
     */
    public function deleteAction(MediaFolder $mediaFolder)
    {
        if (true === $mediaFolder->isRoot()) {
            throw new BadRequestHttpException('Cannot remove the root node of the MediaFodler');
        }
        $this->getMediaFolderRepository()->delete($mediaFolder);
        return new Response("", 204);
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
                if (null !== $parent) {
                    $mediaFolder->setParent($parent);
                    $this->getMediaFolderRepository()->insertNodeAsLastChildOf($mediaFolder, $parent);
                } else {
                    $root = $this->getMediaFolderRepository()->getRoot();
                    $this->getMediaFolderRepository()->insertNodeAsLastChildOf($mediaFolder, $root);
                }
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

                    $sibling = $this->getMedialFolderByUid($sibling_operation['op']['value']);
                    $this->getMediaFolderRepository()->moveAsPrevSiblingOf($mediaFolder, $sibling);
                } elseif (null !== $parent_operation) {
                    unset($operations[$parent_operation['key']]);

                    $parent = $this->getMedialFolderByUid($parent_operation['op']['value']);
                    $this->getMediaFolderRepository()->moveAsLastChildOf($mediaFolder, $parent);
                }
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid node move action: ' + $e->getMessage());
            }
        }
    }

    private function getMedialFolderByUid($uid)
    {
        if (null === $mediaFolder = $this->getMediaFolderRepository()->find($uid)) {
            throw new NotFoundHttpException("Unable to find mediaFolder with uid `$uid`");
        }
        return $mediaFolder;
    }

    private function mediaFolderAlreadyExists($title, MediaFolder $parent)
    {
        $folderExists = false;
        $medialFolder = $this->getMediaFolderRepository()->findOneBy(array("_title" => $title));
        if ($medialFolder) {
            $folderExists = true;
        }
        return $folderExists;
    }

}
?>

