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
use BackBuilder\Rest\Controller\ARestController;

use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @param  string $id category's id
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
        return $this->createResponse(json_encode($this->getCategoryManager()->getCategories()));
    }

    /**
     * Returns collection of classcontent associated to $type and according to provided criterias
     *
     * @param  string $type
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction($type, $start, $count, Request $request)
    {
        $criterias = array_merge(array(
            'only_online' => false,
            'site_uid'    => $this->getApplication()->getSite()->getUid()
        ), $request->query->all());

        $criterias['only_online'] = (boolean) $criterias['only_online'];

        $order_infos = array(
            'column'    => isset($criterias['order_by']) ? $criterias['order_by'] : '_modified',
            'direction' => isset($criterias['order_direction']) ? $criterias['order_direction'] : 'desc',
        );

        $pagination = array('start' => $start, 'limit' => $count);

        unset($criterias['order_by']);
        unset($criterias['order_direction']);

        $classname = 'BackBuilder\ClassContent\\' . str_replace('/', NAMESPACE_SEPARATOR, $type);
        $contents = $this->getApplication()->getEntityManager()
            ->getRepository('BackBuilder\ClassContent\AClassContent')
            ->findContentsBySearch((array) $classname, $order_infos, $pagination, $criterias)
        ;

        return $this->createResponse(json_encode($this->convertPaginatorToArray($contents)));
    }

    /**
     * Get classcontent
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getAction($type, $uid)
    {
        $this->granted('VIEW', $content = $this->getClassContentByTypeAndUid($type, $uid));

        if (null !== $draft = $this->getClassContentRevision($content)) {
            $content->setDraft($draft);
        }

        return $this->createResponse($content->toJson());
    }

    /**
     * delete a classcontent
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
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
            throw new AccessDeniedHttpException("Unable to delete content with type: `$type` and uid: `$uid`");
        }

        return $this->createResponse('', 204);
    }

    /**
     * render classcontent, with mode and/or page if needed
     *
     * @param  string $type type of the class content (ex: Element/text)
     * @param  string $uid  identifier of the class content
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
    public function renderAction($type, $uid, Request $request, Page $page = null)
    {
        $content = $this->getClassContentByTypeAndUid($type, $uid);
        $mode = $request->query->get('mode', null);

        if (null !== $page) {
            $this->getApplication()->getRenderer()->getCurrentPage($page);
        }

        return $this->createResponse(json_encode(array(
            'uid'    => $uid,
            'type'   => $type,
            'render' => $this->getApplication()->getRenderer()->render($content, $mode)
        )));
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
     * @param  string $type short namespace of a classcontent
     *                      (full: BackBuilder\ClassContent\Block\paragraph => short: Block\paragraph)
     * @param  string $uid
     *
     * @return
     */
    private function getClassContentByTypeAndUid($type, $uid)
    {
        $content = null;
        $classname = 'BackBuilder\ClassContent\\' . str_replace('/', NAMESPACE_SEPARATOR, $type);

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
     * @param  AClassContent $content content we want to get the latest revision
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
     * Converts Doctrine's paginator to php array
     *
     * @param  Paginator $paginator the paginator to convert
     *
     * @return array
     */
    private function convertPaginatorToArray(Paginator $paginator)
    {
        $contents = array();
        foreach ($paginator as $content) {
            $contents[] = $content;
        }

        return $contents;
    }
}
