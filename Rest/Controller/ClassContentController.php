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
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\ClassContent\AClassContent;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Routing\RouteCollection;
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
     * Contains every potential classcontent thumbnail base folder
     * @var array
     */
    private $thumbnail_base_directory = null;

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

        return $this->createJsonResponse($categories, 200, [
            'Content-Range' => '0-'.(count($categories) - 1).'/'.count($categories)
        ]);
    }

    /**
     * Returns collection of classcontent associated to category and according to provided criterias
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\QueryParam(name="category", description="Filter classcontent collection by provided category", requirements={
     *     @Assert\NotBlank
     * })
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionByCategoryAction($start, $count, Request $request)
    {
        $category = $this->getCategoryManager()->getCategory($category_name = $request->query->get('category'));

        if (null === $category) {
            throw new NotFoundHttpException("`$category_name` is not a valid classcontent category.");
        }

        $classnames = [];
        foreach ($category->getBlocks() as $block) {
            $classnames[] = $this->getClassnameByType($block->type);
        }

        $contents = $this->findContentsByCriterias($classnames, $start, $count);
        $response = $this->createJsonResponse($this->formatClassContentCollection($contents));

        return $this->addContentRangeHeadersToResponse($response, $contents, $start);
    }

    /**
     * Returns definition for provided type
     *
     * @param string $type
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getDefinitionAction($type)
    {
        $classname = $this->getClassnameByType($type);

        return $this->createJsonResponse($this->getDefinitionFromClassContent(new $classname()));
    }

    /**
     * Returns definitions of every declared classcontent in current application; you can also filter it by:
     *     - category name (provide 'category' as query parameter): returns definitions of every classcontent that
     *     belong to provided category
     *     - page uid (provide 'page_uid' as query parameter): returns definitions of every classcontent contained
     *     by page's contentset
     *
     * @param Resquest $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getDefinitionCollectionAction(Request $request)
    {
        $definitions = null;

        if (null !== $category = $request->query->get('category', null)) {
            $definitions = $this->getDefinitionsByCategory($category);
        } elseif (null !== $page_uid = $request->query->get('page_uid', null)) {
            $definitions = $this->getDefinitionsByPageUid($page_uid);
        } else {
            $definitions = $this->getAllElementDefinitions();
            $definitions = array_merge($definitions, $this->getAllDefinitionsFromCategoryManager());
        }

        return $this->createJsonResponse($definitions, 200, [
            'Content-Range' => '0-'.(count($definitions) - 1).'/'.count($definitions)
        ]);
    }

    /**
     * Returns collection of classcontent associated to $type and according to provided criterias
     *
     * @param string $type
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\QueryParam(name="verbose", default=true, description="Response will contains classcontent definition and its own data")
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction($type, $start, $count)
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
     * @Rest\QueryParam(name="verbose", default=true, description="Response will contains classcontent definition and its own data")
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
            $verbose = (boolean) $request->query->get('verbose', true);
            $response = $this->createJsonResponse($this->updateClassContentImageUrl($content->jsonSerialize($verbose)));
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

        $em = $this->getApplication()->getEntityManager();
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
            $this->getApplication()->getEntityManager()->getRepository('BackBee\ClassContent\AClassContent')
                ->deleteContent($content)
            ;
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
        return $this->getApplication()->getContainer()->get('classcontent.category_manager');
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
     * Returns provided content definition
     *
     * @param AClassContent $content
     *
     * @return array
     */
    private function getDefinitionFromClassContent(AClassContent $content)
    {
        $definition = $content->jsonSerialize();
        $definition = $this->updateClassContentImageUrl($definition);
        unset(
            $definition['uid'],
            $definition['state'],
            $definition['created'],
            $definition['modified'],
            $definition['revision'],
            $definition['elements'],
            $definition['draft_uid'],
            $definition['main_node']
        );

        return $definition;
    }

    /**
     * Returns definitions of classcontent that belong to provided category name
     *
     * @param string $category_name
     *
     * @return array
     */
    private function getDefinitionsByCategory($category_name)
    {
        $category = $this->getCategoryManager()->getCategory($category_name);
        if (null === $category) {
            throw new NotFoundHttpException("`$category_name` is not a valid classcontent category.");
        }

        $definitions = [];
        foreach ($category->getBlocks() as $block) {
            $classname = $this->getClassnameByType($block->type);
            $definitions[] = $this->getDefinitionFromClassContent(new $classname());
        }

        return $definitions;
    }

    /**
     * Returns definitions of classcontent contained by provided page contentset
     *
     * @param string $page_uid
     *
     * @return array
     */
    private function getDefinitionsByPageUid($page_uid)
    {
        $classnames = $this->getApplication()->getEntityManager()->getConnection()->executeQuery(
            'SELECT DISTINCT c.classname
             FROM idx_content_content icc, content c, page p
             WHERE p.uid = :page_uid AND p.contentset = icc.content_uid
             AND icc.subcontent_uid = c.uid AND c.classname != :contentset_classname
            ',
            [
                'page_uid'             => $page_uid,
                'contentset_classname' => AClassContent::CLASSCONTENT_BASE_NAMESPACE.'ContentSet'
            ]
        )->fetchAll();

        $definitions = [];
        foreach ($classnames as $classname) {
            $classname = $classname['classname'];
            $definitions[] = $this->getDefinitionFromClassContent(new $classname());
        }

        return $definitions;
    }

    /**
     * Returns definitions of every declared classcontent in current application
     *
     * @return array
     */
    private function getAllDefinitionsFromCategoryManager()
    {
        $classnames = [];
        foreach ($this->getCategoryManager()->getCategories() as $category) {
            foreach ($category->getBlocks() as $block) {
                $classnames[] = $this->getClassnameByType($block->type);
            }
        }

        return $this->getDefinitionsFromClassnames($classnames);
    }

    /**
     * Returns definitions of every element classcontent
     *
     * @return array
     */
    private function getAllElementDefinitions()
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

        return $this->getDefinitionsFromClassnames($classnames);
    }

    /**
     * Returns every classcontent definitions of provided classnames
     *
     * @param array $classnames
     *
     * @return array
     */
    private function getDefinitionsFromClassnames(array $classnames)
    {
        $definitions = [];
        foreach ($classnames as $classname) {
            $definitions[] = $this->getDefinitionFromClassContent(new $classname());
        }

        return $definitions;
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
            $content = $this->getApplication()->getEntityManager()->find($classname, $uid);
        } catch (ClassNotFoundException $e) {
            throw new NotFoundHttpException("No classcontent found with provided type (:$type)");
        }

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$uid`");
        }

        return $content;
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
        return $this->getApplication()->getEntityManager()->getRepository('BackBee\ClassContent\Revision')
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
        ], $this->getApplication()->getRequest()->query->all());

        $criterias['only_online'] = (boolean) $criterias['only_online'];

        $order_infos = [
            'column'    => isset($criterias['order_by']) ? $criterias['order_by'] : '_modified',
            'direction' => isset($criterias['order_direction']) ? $criterias['order_direction'] : 'desc',
        ];

        $pagination = ['start' => $start, 'limit' => $count];

        unset($criterias['order_by']);
        unset($criterias['order_direction']);

        return $this->getApplication()->getEntityManager()
            ->getRepository('BackBee\ClassContent\AClassContent')
            ->findContentsBySearch($classnames, $order_infos, $pagination, $criterias)
        ;
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
        $verbose = (boolean) $this->getApplication()->getRequest()->query->get('verbose', true);
        foreach ($paginator as $content) {
            $contents[] = $content->jsonSerialize($verbose);
        }

        return $this->updateClassContentCollectionImageUrl($contents);
    }

    /**
     * Update class content collection image url
     *
     * @param array $classcontents
     */
    private function updateClassContentCollectionImageUrl(array $classcontents)
    {
        $result = [];
        foreach ($classcontents as $classcontent) {
            $result[] = $this->updateClassContentImageUrl($classcontent);
        }

        return $result;
    }

    /**
     * Update a single classcontent image url
     *
     * @param array $classcontent the classcontent we want to update its image url
     *
     * @return array
     */
    private function updateClassContentImageUrl(array $classcontent)
    {
        $image_uri = '';
        $url_type = RouteCollection::RESOURCE_URL;
        if ('/' === $classcontent['image'][0]) {
            $image_uri = $classcontent['image'];
            $url_type = RouteCollection::IMAGE_URL;
        } else {
            $image_filepath = $this->getThumbnailBaseFolderPath().DIRECTORY_SEPARATOR.$classcontent['image'];
            $base_folder = $this->getApplication()->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            if (file_exists($image_filepath) && is_readable($image_filepath)) {
                $image_uri = $base_folder.'/'.$classcontent['image'];
            } else {
                $image_uri = $base_folder.'/'.'default_thumbnail.png';
            }
        }

        $classcontent['image'] = $this->getApplication()->getRouting()->getUri($image_uri, null, null, $url_type);

        return $classcontent;
    }

    /**
     * Getter of class content thumbnail folder path
     *
     * @return string
     */
    private function getThumbnailBaseFolderPath()
    {
        if (null === $this->thumbnail_base_directory) {
            $base_folder = $this->getApplication()->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            $this->thumbnail_base_directory = array_map(function ($directory) use ($base_folder) {
                return str_replace(
                    DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $directory.'/'.$base_folder
                );
            }, $this->getApplication()->getResourceDir());

            foreach ($this->thumbnail_base_directory as $directory) {
                if (is_dir($directory)) {
                    $this->thumbnail_base_directory = $directory;
                    break;
                }
            }
        }

        return $this->thumbnail_base_directory;
    }

    /**
     * Add 'Content-Range' parameters to $response headers
     *
     * @param Response  $response   the response object
     * @param Paginator $collection collection from where we extract Content-Range data
     * @param integer   $start      the start value
     */
    private function addContentRangeHeadersToResponse(Response $response, Paginator $collection, $start)
    {
        $count = 0;
        foreach ($collection as $row) {
            $count++;
        }

        $last_result = $start + $count - 1;
        $response->headers->set('Content-Range', "$start-$last_result/".count($collection));

        return $response;
    }
}
