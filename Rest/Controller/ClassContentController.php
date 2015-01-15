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

use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\ClassContent\AClassContent;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Routing\RouteCollection;
use BackBee\Utils\File\File;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

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
     * Contains every potential classcontent thumbnail base folder
     * @var array
     */
    private $thumbnailBaseDir = null;

    /**
     * Returns category's datas if $id is valid
     *
     * @param string $id category's id
     *
     * @return Response
     */
    public function getCategoryAction($id)
    {
        $category = $this->getCategoryManager()->getCategory($id);
        if (null === $category) {
            throw new NotFoundHttpException("No classcontent category exists for id `$id`.");
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
        if (null !== $categoryName && AClassContent::JSON_DEFINITION_FORMAT !== $format) {
            $contents = $this->getClassContentByCategory($categoryName, $start, $count);
            $response->setData($this->formatClassContentCollection($contents));
        } elseif (AClassContent::JSON_DEFINITION_FORMAT === $format) {
            $response->setData($contents = $this->getClassContentDefinitionsByCategory($categoryName));
            $start = 0;
        } else {
            $contents = $this->findContentsByCriterias($this->getAllClassContentClassnames(), $start, $count);
            $response->setData($this->formatClassContentCollection($contents));
        }

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Returns collection of classcontent associated to $type and according to provided criterias
     *
     * @param string $type
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionByTypeAction($type, $start, $count)
    {
        $classname = $this->getClassnameByType($type);
        $contents = $this->findContentsByCriterias((array) $classname, $start, $count);
        $response = $this->createJsonResponse($this->formatClassContentCollection($contents));

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Get classcontent
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
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
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid));

        if (null !== $draft = $this->getClassContentRevision($content)) {
            $content->setDraft($draft);
        }

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
            $response->setData($this->encodeClassContent($content, $this->getFormatParam()));
        }

        return $response;
    }

    /**
     * Creates classcontent according to provided type
     *
     * @param string  $type
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function postAction($type, Request $request)
    {
        $classname = $this->getClassnameByType($type);
        $content = new $classname();

        $em = $this->getEntityManager();
        $em->persist($content);

        $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft(
            $content,
            $this->getApplication()->getBBUserToken(),
            true
        );

        $content->setDraft($draft);
        $em->flush();

        return $this->createJsonResponse(null, 201, [
            'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.classcontent.get',
                [
                    'version' => $request->attributes->get('version'),
                    'type'    => $type,
                    'uid'     => $content->getUid()
                ],
                '',
                false
            )
        ]);
    }

    /**
     * delete a classcontent
     *
     * @param string $type type of the class content (ex: Element/text)
     * @param string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($type, $uid)
    {
        $content = $this->getClassContentByTypeAndUid($type, $uid);

        try {
            $this->getEntityManager()->getRepository('BackBee\ClassContent\AClassContent')->deleteContent($content);
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createJsonResponse(null, 204);
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
     * Returns complete namespace of classcontent with provided $type
     *
     * @param string $type
     *
     * @return string classname associated to provided
     *
     * @throws
     */
    private function getClassnameByType($type)
    {
        $classname = AClassContent::CLASSCONTENT_BASE_NAMESPACE.str_replace('/', NAMESPACE_SEPARATOR, $type);

        try {
            class_exists($classname);
        } catch (\Exception $e) {
            throw new NotFoundHttpException("`$type` is not a valid type.");
        }

        return $classname;
    }

    /**
     * Returns classcontent datas if couple (type;uid) is valid
     *
     * @param string $type short namespace of a classcontent
     *                     (full: BackBee\ClassContent\Block\paragraph => short: Block\paragraph)
     * @param string $uid
     *
     * @return AClassContent
     */
    private function getClassContentByTypeAndUid($type, $uid)
    {
        $content = null;
        $classname = $this->getClassnameByType($type);

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
     * Returns all classcontents classnames
     *
     * @return array An array that contains all classcontents classnames
     */
    private function getAllClassContentClassnames()
    {
        $classnames = [];
        foreach ($this->getCategoryManager()->getCategories() as $category) {
            foreach ($category->getBlocks() as $block) {
                $classnames[] = $this->getClassnameByType($block->type);
            }
        }

        return array_merge($this->getAllElementClassContentClassnames(), $classnames);
    }

    /**
     * Returns all BackBee elements classcontents classnames
     *
     * @return array An array that contains all elements classcontents classnames
     */
    private function getAllElementClassContentClassnames()
    {
        $directory = $this->getApplication()->getBBDir().DIRECTORY_SEPARATOR.'ClassContent';
        $classnames = array_map(
            function ($path) use ($directory) {
                return str_replace(
                    [DIRECTORY_SEPARATOR, '\\\\'],
                    [NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR],
                    AClassContent::CLASSCONTENT_BASE_NAMESPACE.str_replace([$directory, '.yml'], ['', ''], $path)
                );
            },
            File::getFilesRecursivelyByExtension($directory, 'yml')
        );
        $classnames[] = AClassContent::CLASSCONTENT_BASE_NAMESPACE.'ContentSet';

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
        $category = $this->getCategoryManager()->getCategory($name);
        if (null === $category) {
            throw new NotFoundHttpException("`$name` is not a valid classcontent category.");
        }

        $classnames = [];
        foreach ($category->getBlocks() as $block) {
            $classnames[] = $this->getClassnameByType($block->type);
        }

        return $classnames;
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
            $classnames = $this->getAllClassContentClassnames();
        } else {
            $classnames = $this->getClassContentClassnamesByCategory($name);
        }

        $definitions = [];
        foreach ($classnames as $classname) {
            $definitions[] = $this->updateClassContentImageUrl(
                (new $classname)->jsonSerialize(AClassContent::JSON_DEFINITION_FORMAT)
            );
        }

        return $definitions;
    }

    /**
     * Returns current revision for the given $content
     *
     * @param AClassContent $content content we want to get the latest revision
     *
     * @return null|BackBee\ClassContent\Revision
     */
    private function getClassContentRevision(AClassContent $content)
    {
        return $this->getEntityManager()->getRepository('BackBee\ClassContent\Revision')
            ->getDraft($content, $this->getApplication()->getBBUserToken())
        ;
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
            : AClassContent::JSON_DEFAULT_FORMAT
        ;

        return AClassContent::$jsonFormats[$format];
    }

    /**
     * Encodes classcontent according to provided format in request
     *
     * @param  AClassContent $content
     * @param  integer       $format
     * @return array
     */
    private function encodeClassContent(AClassContent $content, $format)
    {
        if (AClassContent::JSON_DEFINITION_FORMAT === $format) {
            $classname = get_class($content);
            $content = new $classname;
        }

        return $this->updateClassContentImageUrl($content->jsonSerialize($format));
    }

    /**
     * Converts Doctrine's paginator to php array
     *
     * @param Paginator $paginator the paginator to convert
     *
     * @return array
     */
    private function formatClassContentCollection(Paginator $paginator)
    {
        $contents = [];
        if (AClassContent::JSON_DEFINITION_FORMAT === $format = $this->getFormatParam()) {
            $contents[] = $this->encodeClassContent($paginator->getIterator()->current(), $format);
        } else {
            foreach ($paginator as $content) {
                $contents[] = $this->encodeClassContent($content, $format);
            }
        }

        return $contents;
    }

    /**
     * Update a single classcontent image url
     *
     * @param array $classcontent the classcontent we want to update its image url
     *
     * @return array
     */
    private function updateClassContentImageUrl(array $data)
    {
        if (!isset($data['image'])) {
            return $data;
        }

        $imageUri = '';
        $urlType = RouteCollection::RESOURCE_URL;
        if ('/' === $data['image'][0]) {
            $imageUri = $data['image'];
            $urlType = RouteCollection::IMAGE_URL;
        } else {
            $image_filepath = $this->getThumbnailBaseFolderPath().DIRECTORY_SEPARATOR.$data['image'];
            $baseFolder = $this->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            if (file_exists($image_filepath) && is_readable($image_filepath)) {
                $imageUri = $baseFolder.DIRECTORY_SEPARATOR.$data['image'];
            } else {
                $imageUri = $baseFolder.DIRECTORY_SEPARATOR.'default_thumbnail.png';
            }
        }

        $data['image'] = $this->getApplication()->getRouting()->getUri($imageUri, null, null, $urlType);

        return $data;
    }

    /**
     * Getter of class content thumbnail folder path
     *
     * @return string
     */
    private function getThumbnailBaseFolderPath()
    {
        if (null === $this->thumbnailBaseDir) {
            $baseFolder = $this->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            $this->thumbnailBaseDir = array_map(function ($directory) use ($baseFolder) {
                return str_replace(
                    DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $directory.DIRECTORY_SEPARATOR.$baseFolder
                );
            }, $this->getApplication()->getResourceDir());

            foreach ($this->thumbnailBaseDir as $directory) {
                if (is_dir($directory)) {
                    $this->thumbnailBaseDir = $directory;
                    break;
                }
            }
        }

        return $this->thumbnailBaseDir;
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

        $last_result = $start + $count - 1;
        $response->headers->set('Content-Range', "$start-$last_result/".count($collection));

        return $response;
    }
}
