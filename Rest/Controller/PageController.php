<?php
namespace BackBuilder\Rest\Controller;

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

use BackBuilder\Exception\InvalidArgumentException;
use BackBuilder\NestedNode\Page;
use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Patcher\EntityPatcher;
use BackBuilder\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBuilder\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBuilder\Rest\Patcher\OperationSyntaxValidator;
use BackBuilder\Rest\Patcher\RightManager;
use BackBuilder\Site\Layout;
use BackBuilder\Workflow\State;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Page Controller
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PageController extends ARestController
{
    /**
     * Returns page entity available status
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getAvailableStatusAction()
    {
        return $this->createResponse(json_encode(Page::$STATES));
    }

    /**
     * Get collection of page entity
     * 
     * By default returns online pages only
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBuilder\NestedNode\Page", required=false
     * )
     * @Rest\QueryParam(name="state", description="State", default="1", requirements={
     *   @Assert\Choice(choices = {0, 1, 2, 3, 4}, message="State is not valid", multiple=true)
     * })
     */
    public function getCollectionAction($parent = null, $state = [1])
    {
        if (null === $parent = $this->getEntityFromAttributes('parent')) {
            $parent = $this->getPageRepository()->getRoot($this->getApplication()->getSite());
        }

        $this->granted('VIEW', $parent);
        $children = $this->getPageRepository()->getNotDeletedDescendants(
            $parent,
            1,
            false,
            array('_leftnode' => 'asc'),
            true,
            $start = $this->getRequest()->attributes->get('start'),
            $this->getRequest()->attributes->get('count')
        );

        $result_count = $start;
        foreach ($children as $child) {
            $result_count++;
        }

        $response = $this->createResponse($this->formatCollection($children));
        $response->headers->set('Content-Range', "$start-$result_count/" . $children->count());

        return $response;
    }

    /**
     * Get page entity by uid
     *
     * @param string $uid the unique identifier of the page we want to retrieve
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     * @Rest\Security(expression="is_granted('VIEW', page)")
     */
    public function getAction(Page $page)
    {
        return $this->createResponse($this->formatItem($page));
    }

    /**
     * Create or clone a page entity
     *
     * @Rest\QueryParam(name="title", description="Cloning page new title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters")
     * })
     *
     * @Rest\ParamConverter(
     *   name="layout", id_name="layout_uid", id_source="request", class="BackBuilder\Site\Layout", required=false
     * )
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBuilder\NestedNode\Page", required=false
     * )
     * @Rest\ParamConverter(
     *   name="source", id_name="source_uid", id_source="query", class="BackBuilder\NestedNode\Page", required=false
     * )
     * @Rest\ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBuilder\Workflow\State", required=false
     * )
     */
    public function postAction()
    {
        if (0 === count($this->getRequest()->request->all())) {
            return $this->clonePageAction();
        }

        $this->granted('VIEW', $layout = $this->getEntityFromAttributes('layout'));
        $this->granted('EDIT', $parent = $this->getEntityFromAttributes('parent'));

        if (0 === strlen($title = $this->getRequest()->request->get('title', null))) {
            throw new NotFoundHttpException('Page\'s title cannot be empty.');
        }

        $builder = $this->getApplication()->getContainer()->get('pagebuilder');
        $builder->setLayout($layout);
        $builder->setParent($parent);
        $builder->setRoot($parent->getRoot());
        $builder->setSite($parent->getSite());
        $builder->setTitle($title);
        $builder->setUrl($this->getRequest()->request->get('url', null));
        $builder->setState($this->getRequest()->request->get('state'));
        $builder->setTarget($this->getRequest()->request->get('target'));
        $builder->setRedirect($this->getRequest()->request->get('redirect'));
        $builder->setAltTitle($this->getRequest()->request->get('alt_title'));
        $builder->setPublishing(
            null !== $this->getRequest()->request->get('publishing')
                ? new \DateTime(date('c', $this->getRequest()->request->get('publishing')))
                : null
        );
        $builder->setArchiving(
            null !== $this->getRequest()->request->get('archiving')
                ? new \DateTime(date('c', $this->getRequest()->request->get('archiving')))
                : null
        );

        $page = $builder->getPage();
        $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));
        $this->granted('CREATE', $page);

        $this->getEntityManager()->persist($page);
        try {
            $this->getEntityManager()->flush($page);
            $this->getPageRepository()->updateTreeNatively($page->getRoot()->getUid());
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage(), 500);
        }

        $response = $this->createResponse('', 201);
        $response->headers->set(
            'Location', $this->getApplication()->getRouting()->getUri($page->getUrl(), null, $page->getSite())
        );

        return $response;
    }

    /**
     * Update page entity with $uid
     *
     * @Rest\RequestParam(name="title", description="page new title", requirements={
     *   @Assert\NotBlank(message="title is required")
     * })
     * @Rest\RequestParam(name="url", description="page new url", requirements={
     *   @Assert\NotBlank(message="url is required")
     * })
     * @Rest\RequestParam(name="target", description="page new target", requirements={
     *   @Assert\NotBlank(message="target is required")
     * })
     * @Rest\RequestParam(name="state", description="page new state", requirements={
     *   @Assert\NotBlank(message="state is required")
     * })
     * @Rest\RequestParam(name="publishing", description="page new publishing", requirements={
     *   @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     * @Rest\RequestParam(name="archiving", description="page new archiving", requirements={
     *   @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     * @Rest\ParamConverter(name="layout", id_name="layout_uid", class="BackBuilder\Site\Layout", id_source="request")
     * @Rest\ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBuilder\Workflow\State", required=false
     * )
     * @Rest\Security(expression="is_granted('EDIT', page)")
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function putAction(Page $page)
    {
        $page->setLayout($this->getEntityFromAttributes('layout'));
        $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));
        $page->setTitle($this->getRequest()->request->get('title'));
        $page->setUrl($this->getRequest()->request->get('url'));
        $page->setTarget($this->getRequest()->request->get('target'));
        $page->setState($this->getRequest()->request->get('state'));
        $page->setRedirect($this->getRequest()->request->get('redirect', null));
        $page->setAltTitle($this->getRequest()->request->get('alt_title', null));

        $publishing = $this->getRequest()->request->get('publishing');
        $page->setPublishing(null !== $publishing ? new \DateTime(date('c', $publishing)) : null);

        $archiving = $this->getRequest()->request->get('archiving');
        $page->setArchiving(null !== $archiving ? new \DateTime(date('c', $archiving)) : null);

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse('', 204);
    }

    /**
     * Patch of a page entity
     *
     * @Rest\RequestParam(name="operations", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="operations is required")
     * })
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     * @Rest\Security(expression="is_granted('EDIT', page)")
     */
    public function patchAction(Page $page)
    {
        $operations = $this->getRequest()->request->get('operations');

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: ' . $e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));

        try {
            $entity_patcher->patch($page, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new AccessDeniedHttpException('Invalid patch operation: ' . $e->getMessage());
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse('', 204);
    }

    /**
     * Update page by moving it from current parent to new one
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", class="BackBuilder\NestedNode\Page", id_source="request"
     * )
     * @Rest\ParamConverter(
     *   name="next", id_name="next_uid", class="BackBuilder\NestedNode\Page", id_source="request", required=false
     * )
     * @Rest\Security(expression="is_granted('EDIT', page)")
     * @Rest\Security(expression="is_granted('EDIT', parent)")
     */
    public function movePageNodeAction(Page $page)
    {
        if (true === $page->isRoot()) {
            throw new AccessDeniedHttpException('Cannot move root node of a site.');
        }

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page); // user must have publish permission on the page
        }

        try {
            $parent = $this->getEntityFromAttributes('parent');
            if (null === $next = $this->getEntityFromAttributes('next')) {
                $this->getPageRepository()->moveAsLastChildOf($page, $parent);
            } else {
                if (false === $next->getParent()->equals($parent)) {
                    throw new AccessDeniedHttpException('Next node must have the same parent node.');
                }

                $this->getPageRepository()->moveAsPrevSiblingOf($page, $next);
            }
        } catch (InvalidArgumentException $e) {
            throw new AccessDeniedHttpException('Invalid node move action: ' . $e->getMessage());
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse('', 204);
    }

    /**
     * [deleteAction description]
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     */
    public function deleteAction(Page $page)
    {
        if (true === $page->isRoot()) {
            throw new AccessDeniedHttpException('Cannot remove root page of a site.');
        }

        $this->granted('EDIT', $page->getParent()); // user must have edit permission on parent

        if (true === $page->isOnline()) {
            $this->granted('PUBLISH', $page); // user must have publish permission on the page
        }

        $this->getPageRepository()->toTrash($page);

        return $this->createResponse('', 204);
    }

    /**
     * [clonePageAction description]
     *
     * @return [type]
     */
    private function clonePageAction()
    {
        if (null === $page = $this->getEntityFromAttributes('source')) {
            throw new BadRequestHttpException('`source_uid` query parameter is missing.');
        }

        $title = $this->getRequest()->query->get('title', null);
        if (null === $title) {
            throw new BadRequestHttpException('`title` query parameter is missing.');
        }

        $this->granted('VIEW', $page->getLayout()); // user must have view permission on choosen layout
        $this->granted('CREATE', $page); // user must have create permission on page

        if (null !== $page->getParent()) {
            $this->granted('EDIT', $page->getParent());
        } else {
            $this->granted('EDIT', $this->getApplication()->getSite());
        }

        try {
            $new_page = $this->getPageRepository()->duplicate(
                $page, $title, $page->getParent(), true, $this->getApplication()->getBBUserToken()
            );
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage(), 500);
        }

        $response = $this->createResponse('', 201);
        $response->headers->set(
            'Location', $this->getApplication()->getRouting()->getUri($page->getUrl(), null, $page->getSite())
        );

        return $response;
    }

    /**
     * Getter for page entity repository
     *
     * @return \BackBuilder\NestedNode\Repository\PageRepository
     */
    private function getPageRepository()
    {
        return $this->getEntityManager()->getRepository('BackBuilder\NestedNode\Page');
    }

    /**
     * [trySetPageWorkflowState description]
     *
     * @param  Page   $page
     * @param  [type] $workflow
     */
    private function trySetPageWorkflowState(Page $page, State $workflow = null)
    {
        $page->setWorkflowState(null);
        if (null !== $workflow) {
            if (null === $workflow->getLayout() || $workflow->getLayout()->getUid() === $page->getLayout()->getUid()) {
                $page->setWorkflowState($workflow);
            }
        }
    }
}
