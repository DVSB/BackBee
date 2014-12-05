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

namespace BackBuilder\Rest\Controller;

use BackBuilder\AutoLoader\Exception\ClassNotFoundException;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\NestedNode\Page;
use BackBuilder\Rest\Controller\Annotations as Rest;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ClassContent API Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ClassContentController extends ARestController
{
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

        return $this->createResponse(json_encode($category));
    }

    /**
     * Returns every availables categories datas
     *
     * @return Response
     */
    public function getCategoryCollectionAction()
    {
        $categories = array();
        foreach ($this->getCategoryManager()->getCategories() as $id => $category) {
            $categories[] = array_merge(array('id' => $id), $category->jsonSerialize());
        }

        return $this->createResponse(json_encode($categories));
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

        $classnames = array();
        foreach ($category->getBlocks() as $block) {
            $classnames[] = 'BackBuilder\ClassContent\\'.str_replace('/', NAMESPACE_SEPARATOR, $block->type);
        }

        return $this->createResponse(json_encode($this->convertPaginatorToArray($this->findContentsByCriterias(
            $classnames, $start, $count
        ))));
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
    public function getCollectionAction($type, $start, $count)
    {
        $classname = 'BackBuilder\ClassContent\\'.str_replace('/', NAMESPACE_SEPARATOR, $type);

        return $this->createResponse(json_encode($this->convertPaginatorToArray($this->findContentsByCriterias(
            (array) $classname, $start, $count
        ))));
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
     *   name="page", id_name="page_uid", id_source="query", class="BackBuilder\NestedNode\Page", required=false
     * )
     */
    public function getAction($type, $uid, Request $request)
    {
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid));

        if (null !== $draft = $this->getClassContentRevision($content)) {
            $content->setDraft($draft);
        }

        $content_type = 'application/json';
        if ('html' === $request->getContentType()) {
            if (null !== $this->getEntityFromAttributes('page')) {
                $this->getApplication()->getRenderer()->getCurrentPage($page);
            }

            $mode = $request->query->get('mode', null);
            $content = $this->getApplication()->getRenderer()->render($content, $mode);
            $content_type = 'text/html';
        } else {
            $content = json_encode($content);
        }

        return $this->createResponse($content, 200, $content_type);
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
            $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\AClassContent')
                ->deleteContent($content)
            ;
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createResponse('', 204);
    }

    /**
     * Getter of classcontent category manager
     *
     * @return BackBuilder\ClassContent\CategoryManager
     */
    private function getCategoryManager()
    {
        return $this->getApplication()->getContainer()->get('classcontent.category_manager');
    }

    /**
     * Returns classcontent datas if couple (type;uid) is valid
     *
     * @param string $type short namespace of a classcontent
     *                     (full: BackBuilder\ClassContent\Block\paragraph => short: Block\paragraph)
     * @param string $uid
     *
     * @return
     */
    private function getClassContentByTypeAndUid($type, $uid)
    {
        $content = null;
        $classname = 'BackBuilder\ClassContent\\'.str_replace('/', NAMESPACE_SEPARATOR, $type);

        try {
            $content = $this->getApplication()->getEntityManager()->find($classname, $uid);
        } catch (ClassNotFoundException $e) {
            throw new NotFoundHttpException("No classcontent (:$classname) found with provided type (:$type)");
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
     * @return null|BackBuilder\ClassContent\Revision
     */
    private function getClassContentRevision(AClassContent $content)
    {
        return $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')
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
        $criterias = array_merge(array(
            'only_online' => false,
            'site_uid'    => $this->getApplication()->getSite()->getUid(),
        ), $this->getApplication()->getRequest()->query->all());

        $criterias['only_online'] = (boolean) $criterias['only_online'];

        $order_infos = array(
            'column'    => isset($criterias['order_by']) ? $criterias['order_by'] : '_modified',
            'direction' => isset($criterias['order_direction']) ? $criterias['order_direction'] : 'desc',
        );

        $pagination = array('start' => $start, 'limit' => $count);

        unset($criterias['order_by']);
        unset($criterias['order_direction']);

        return $this->getApplication()->getEntityManager()
            ->getRepository('BackBuilder\ClassContent\AClassContent')
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
    private function convertPaginatorToArray(Paginator $paginator = null)
    {
        $contents = array();
        if (null !== $paginator) {
            foreach ($paginator as $content) {
                $contents[] = $content;
            }
        }

        return $contents;
    }
}
