<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\ClassContent\AClassContent;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Routing\RouteCollection;
use BackBee\Rest\Patcher\EntityPatcher;
use BackBee\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\Rest\Patcher\OperationSyntaxValidator;
use BackBee\Rest\Patcher\RightManager;
use BackBee\Utils\File\File;

/**
 * ClassContent API Controller
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ClassContentController extends ARestController
{
    /**
     * @var BackBee\ClassContent\ClassContentManager
     */
    private $manager;

    /**
     * Returns category's datas if $id is valid
     *
     * @param string $id category's id
     * @return Response
     */
    public function getCategoryAction($id)
    {
        $category = $this->getCategoryManager()->getCategory($id);
        if (null === $category) {
            throw new NotFoundHttpException("Classcontent's category `$id` not found.");
        }

        return $this->createJsonResponse($category);
    }

    /**
     * Returns every availables categories datas
     *
     * @return Response
     */
    public function getCategoryCollectionAction()
    {
        $categories = [];
        foreach ($this->getCategoryManager()->getCategories() as $id => $category) {
            $categories[] = array_merge(['id' => $id], $category->jsonSerialize());
        }

        return $this->addContentRangeHeadersToResponse($this->createJsonResponse($categories), $categories, 0);
    }

    /**
     * Returns collection of classcontent associated to category and according to provided criterias
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction($start, $count, Request $request)
    {
        $contents = [];
        $format = $this->getFormatParam();
        $response = $this->createJsonResponse();
        $categoryName = $request->query->get('category', null);

        if (AClassContent::JSON_DEFINITION_FORMAT === $format) {
            $response->setData($contents = $this->getClassContentDefinitionsByCategory($categoryName));
            $start = 0;
        } else {
            if (null !== $categoryName) {
                $contents = $this->getClassContentByCategory($categoryName, $start, $count);
            } else {
                $classnames = $this->getClassContentManager()->getAllClassContentClassnames();
                $contents = $this->findContentsByCriterias($classnames, $start, $count);
            }

            $data = $this->getClassContentManager()->jsonEncodeCollection($contents, $this->getFormatParam());
            $response->setData($data);
        }

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Returns collection of classcontent associated to $type and according to provided criterias
     *
     * @param string $type
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionByTypeAction($type, $start, $count)
    {
        $classname = AClassContent::getClassnameByContentType($type);
        $contents = $this->findContentsByCriterias((array) $classname, $start, $count);
        $response = $this->createJsonResponse($this->getClassContentManager()->jsonEncodeCollection(
            $contents,
            $this->getFormatParam()
        ));

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Get classcontent
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\QueryParam(name="mode", description="The render mode to use")
     * @Rest\QueryParam(name="page_uid", description="The page to set to application's renderer before rendering")
     *
     * @Rest\ParamConverter(
     *   name="page", id_name="page_uid", id_source="query", class="BackBee\NestedNode\Page", required=false
     * )
     */
    public function getAction($type, $uid, Request $request)
    {
        $this->granted('VIEW', $content = $this->getClassContentManager()->findOneByTypeAndUid($type, $uid, true));

        $response = null;
        if (in_array('text/html', $request->getAcceptableContentTypes())) {
            if (null !== $this->getEntityFromAttributes('page')) {
                $this->getApplication()->getRenderer()->getCurrentPage($page);
            }

            $mode = $request->query->get('mode', null);
            $response = $this->createResponse(
                $this->getApplication()->getRenderer()->render($content, $mode), 200, 'text/html'
            );
        } else {
            $response = $this->createJsonResponse();
            $response->setData($this->getClassContentManager()->jsonEncode($content, $this->getFormatParam()));
        }

        return $response;
    }

    /**
     * Creates classcontent according to provided type
     *
     * @param string  $type
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function postAction($type, Request $request)
    {
        $classname = AClassContent::getClassnameByContentType($type);
        $content = new $classname();

        $this->getEntityManager()->persist($content);
        $content->setDraft($this->getClassContentManager()->getDraft($content, true));
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 201, [
            'BB-RESOURCE-UID' => $content->getUid(),
            'Location'        => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.classcontent.get',
                [
                    'version' => $request->attributes->get('version'),
                    'type'    => $type,
                    'uid'     => $content->getUid()
                ],
                '',
                false
            ),
        ]);
    }

    /**
     * Updates classcontent's elements and parameters
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function putAction($type, $uid, Request $request)
    {
        $this->updateClassContent($type, $uid, $request->request->all());
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Updates collection of classcontent elements and parameters
     *
     * @param  Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function putCollectionAction(Request $request)
    {
        $result = [];
        foreach ($request->request->all() as $data) {
            if (!isset($data['type']) || !isset($data['uid'])) {
                throw new BadRequestHttpException("type and/or uid is missing.");
            }

            try {
                $content = $this->updateClassContent($data['type'], $data['uid'], $data);
                $result[] = [
                    'uid'        => $content->getUid(),
                    'type'       => $content->getContentType(),
                    'statusCode' => 200,
                    'message'    => 'OK',
                ];
            } catch (AccessDeniedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 401,
                    'message'    => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 500,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse($result);
    }

    /**
     * delete a classcontent
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction($type, $uid)
    {
        $content = $this->getClassContentManager()->findOneByTypeAndUid($type, $uid);

        try {
            $this->getEntityManager()->getRepository('BackBee\ClassContent\AClassContent')->deleteContent($content);
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createJsonResponse(null, 204);
    }

    /**
     * ClassContent's draft getter
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDraftAction($type, $uid)
    {
        $this->granted('VIEW', $content = $this->getClassContentManager()->findOneByTypeAndUid($type, $uid));

        return $this->createJsonResponse($this->getClassContentManager()->getDraft($content));
    }

    /**
     * Returns all drafts of current user
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDraftCollectionAction()
    {
        $contents = $this->getEntityManager()
            ->getRepository('BackBee\ClassContent\Revision')
            ->getAllDrafts($this->getApplication()->getBBUserToken())
        ;

        $contents = $this->sortDraftCollection($contents);

        return $this->addContentRangeHeadersToResponse($this->createJsonResponse($contents), $contents, 0);
    }

    /**
     * Updates a classcontent's draft.
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function putDraftAction($type, $uid, Request $request)
    {
        $this->updateClassContentDraft($type, $uid, $request->request->all());
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Updates collection of classcontents' drafts.
     *
     * @param  Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function putDraftCollectionAction(Request $request)
    {
        $result = [];
        foreach ($request->request->all() as $data) {
            if (!isset($data['type']) || !isset($data['uid'])) {
                throw new BadRequestHttpException("type and/or uid is missing.");
            }

            try {
                $content = $this->updateClassContentDraft($data['type'], $data['uid'], $data);
                $result[] = [
                    'uid'        => $content->getUid(),
                    'type'       => $content->getContentType(),
                    'statusCode' => 200,
                    'message'    => 'OK',
                ];
            } catch (AccessDeniedException $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 401,
                    'message'    => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $result[] = [
                    'uid'        => $data['uid'],
                    'type'       => $data['type'],
                    'statusCode' => 500,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse($result);
    }

    /**
     * Getter of classcontent category manager
     *
     * @return BackBee\ClassContent\CategoryManager
     */
    private function getCategoryManager()
    {
        return $this->getContainer()->get('classcontent.category_manager');
    }

    /**
     * Returns ClassContentManager
     *
     * @return BackBee\ClassContent\ClassContentManager
     */
    private function getClassContentManager()
    {
        if (null === $this->manager) {
            $this->manager = $this->getApplication()->getContainer()->get('classcontent.manager')
                ->setBBUserToken($this->getApplication()->getBBUserToken())
            ;
        }

        return $this->manager;
    }

    /**
     * Sorts the provided array that contains current logged user's drafts.
     *
     * @param  array  $drafts
     * @return array
     */
    private function sortDraftCollection(array $drafts)
    {
        $sortedDrafts = [];
        foreach ($drafts as $draft) {
            $sortedDrafts[$draft->getContent()->getUid()] = [$draft->getContent()->getUid() => $draft];
        }

        foreach ($drafts as $draft) {
            foreach ($draft->getContent()->getData() as $key => $element) {
                if (
                    is_object($element)
                    && $element instanceof AClassContent
                    && in_array($element->getUid(), array_keys($sortedDrafts))
                ) {
                    $elementUid = $element->getUid();
                    $sortedDrafts[$draft->getContent()->getUid()][$key] = $sortedDrafts[$elementUid][$elementUid];
                }
            }
        }

        $drafts = [];
        foreach ($sortedDrafts as $key => $data) {
            if (!array_key_exists($key, $drafts)) {
                $drafts[$key] = $data;
            }

            foreach ($data as $elementName => $draft) {
                if ($key === $elementName) {
                    continue;
                }

                if (false === $drafts[$key]) {
                    $drafts[$draft->getContent()->getUid()] = false;
                } elseif (isset($sortedDrafts[$draft->getContent()->getUid()])) {
                    $drafts[$key][$elementName] = $sortedDrafts[$draft->getContent()->getUid()];
                    $drafts[$draft->getContent()->getUid()] = false;
                }
            }
        }

        return array_filter($drafts);
    }

    /**
     * Updates and returns content and its draft according to provided data.
     *
     * @param  string $type
     * @param  string $uid
     * @param  array $data
     * @return AClassContent
     */
    private function updateClassContent($type, $uid, $data)
    {
        $content = $this->getClassContentManager()->findOneByTypeAndUid($type, $uid, true, true);
        $this->granted('EDIT', $content);
        $this->getClassContentManager()->update($content, $data);

        return $content;
    }

    /**
     * Commits or reverts content's draft according to provided data.
     *
     * @param  string $type
     * @param  string $uid
     * @param  array  $data
     * @return AClassContent
     */
    private function updateClassContentDraft($type, $uid, $data)
    {
        $this->granted('VIEW', $content = $this->getClassContentManager()->findOneByTypeAndUid($type, $uid));

        $operation = $data['operation'];
        if (!in_array($operation, ['commit', 'revert'])) {
            throw new BadRequestHttpException(sprintf('%s is not a valid operation for update draft.', $operation));
        }

        $this->getClassContentManager()->$operation($content, $data);

        return $content;
    }

    /**
     * Returns classcontent datas if couple (type;uid) is valid
     *
     * @param string $type
     * @param string $uid
     * @return AClassContent
     */
    private function getClassContentByTypeAndUid($type, $uid)
    {
        $content = null;
        $classname = AClassContent::getClassnameByContentType($type);

        try {
            $content = $this->getEntityManager()->find($classname, $uid);
        } catch (ClassNotFoundException $e) {
            throw new NotFoundHttpException("No classcontent found with provided type (:$type)");
        }

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$uid`");
        }

        return $content;
    }

    /**
     * Returns classcontent by category
     *
     * @param  string  $name  category's name
     * @param  integer $start
     * @param  integer $count
     * @return null|Paginator
     */
    private function getClassContentByCategory($name, $start, $count)
    {
        return $this->findContentsByCriterias($this->getClassContentClassnamesByCategory($name), $start, $count);
    }

    /**
     * Returns all classcontents classnames containing by the given page
     *
     * @param string $pageUid The unique identifier of the page we want to get all classcontents
     *
     * @return array
     */
    private function getClassContentClassnamesByPageUid($pageUid)
    {
        $result = $this->getEntityManager()->getConnection()->prepare(
            'SELECT DISTINCT c.classname
             FROM idx_content_content icc, content c, page p
             WHERE p.uid = :pageUid AND p.contentset = icc.content_uid
             AND icc.subcontent_uid = c.uid AND c.classname != :contentset_classname'
        )->execute([
            'pageUid'              => $pageUid,
            'contentset_classname' => AClassContent::CLASSCONTENT_BASE_NAMESPACE.'ContentSet',
        ]);

        $classnames = [];
        foreach ($result as $classname) {
            $classnames[] = $classname['classname'];
        }

        return $classnames;
    }

    /**
     * Returns all classcontents classnames that belong to provided category
     *
     * @param  string $name The category name
     * @return array
     */
    private function getClassContentClassnamesByCategory($name)
    {
        try {
            return $this->getCategoryManager()->getClassContentClassnamesByCategory($name);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    /**
     * Returns classcontent data with definition format (AContent::JSON_DEFAULT_FORMAT). If category name
     * is provided it will returns every classcontent definition that belongs to this category, else it
     * will returns all classcontents definitions.
     *
     * @param  string|null $name the category's name or null
     * @return array
     */
    private function getClassContentDefinitionsByCategory($name = null)
    {
        $classnames = [];
        if (null === $name) {
            $classnames = $this->getClassContentManager()->getAllClassContentClassnames();
        } else {
            $classnames = $this->getClassContentClassnamesByCategory($name);
        }

        $definitions = [];
        foreach ($classnames as $classname) {
            $definitions[] = $this->getClassContentManager()->jsonEncode(
                (new $classname),
                AClassContent::JSON_DEFINITION_FORMAT
            );
        }

        return $definitions;
    }

    /**
     * Find classcontents by provided classnames, criterias from request, provided start and count
     *
     * @param array   $classnames
     * @param integer $start
     * @param integer $count
     *
     * @return null|Paginator
     */
    private function findContentsByCriterias(array $classnames, $start, $count)
    {
        $criterias = array_merge([
            'only_online' => false,
            'site_uid'    => $this->getApplication()->getSite()->getUid(),
        ], $this->getRequest()->query->all());

        $criterias['only_online'] = (boolean) $criterias['only_online'];

        $order_infos = [
            'column'    => isset($criterias['order_by']) ? $criterias['order_by'] : '_modified',
            'direction' => isset($criterias['order_direction']) ? $criterias['order_direction'] : 'desc',
        ];

        $pagination = ['start' => $start, 'limit' => $count];

        unset($criterias['order_by']);
        unset($criterias['order_direction']);

        return $this->getEntityManager()
            ->getRepository('BackBee\ClassContent\AClassContent')
            ->findContentsBySearch($classnames, $order_infos, $pagination, $criterias)
        ;
    }

    /**
     * Returns AContent valid json format by looking at request query parameter and if no format found,
     * it fallback to AContent::JSON_DEFAULT_FORMAT.
     *
     * @return integer One of AContent::$jsonFormats:
     *                 JSON_DEFAULT_FORMAT | JSON_DEFINITION_FORMAT | JSON_CONCISE_FORMAT | JSON_INFO_FORMAT
     */
    private function getFormatParam()
    {
        $validFormats = array_keys(AClassContent::$jsonFormats);
        $queryParamsKey = array_keys($this->getRequest()->query->all());
        $format = ($collection = array_intersect($validFormats, $queryParamsKey))
            ? array_shift($collection)
            : $validFormats[AClassContent::JSON_DEFAULT_FORMAT]
        ;

        return AClassContent::$jsonFormats[$format];
    }

    /**
     * Add 'Content-Range' parameters to $response headers
     *
     * @param Response $response   the response object
     * @param mixed    $collection collection from where we extract Content-Range data
     * @param integer  $start      the start value
     */
    private function addContentRangeHeadersToResponse(Response $response, $collection, $start)
    {
        $count = count($collection);
        if ($collection instanceof Paginator) {
            $count = count($collection->getIterator());
        }

        $lastResult = $start + $count - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/".count($collection));

        return $response;
    }
}
