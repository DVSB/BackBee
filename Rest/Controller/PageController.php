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
use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\MetaData\MetaDataBag;
use BackBuilder\NestedNode\Page;
use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Patcher\EntityPatcher;
use BackBuilder\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBuilder\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBuilder\Rest\Patcher\OperationSyntaxValidator;
use BackBuilder\Rest\Patcher\RightManager;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;

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
     * @Rest\Pagination(default_count=25, max_count=100)
     */
    public function getCollectionAction($start, $count)
    {
        $parent_uid = $this->getRequest()->query->get('parent_uid', null);
        $parent = null;
        if (null !== $parent_uid) {
            $parent = $this->getPageByUid($parent_uid);
        } else {
            $parent = $this->getPageRepository()->getRoot($this->getApplication()->getSite());
        }

        $this->isGranted('VIEW', $parent);

        $children = $this->getPageRepository()->getNotDeletedDescendants(
            $parent,
            1,
            false,
            array('_leftnode' => 'asc'),
            true,
            $start,
            $count
        );

        $result_count = 0;
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
     */
    public function getAction($uid)
    {
        $page = $this->getPageByUid($uid);
        $this->isGranted('VIEW', $page);

        return $this->createResponse($this->formatItem($page));
    }

    /**
     * Create or clone a page entity
     *
     * Clone action requirements:
     *
     * @Rest\QueryParam(name="source_uid", description="Source uid for cloning", requirements={
     *     @Assert\Length(min=32, max=32, exactMessage="source_uid must contains 32 characters")
     * })
     * @Rest\QueryParam(name="title", description="Cloning page new title", requirements={
     *     @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters")
     * })
     */
    public function postAction()
    {
        if (0 === count($this->getRequest()->request->all())) {
            return $this->clonePageAction();
        }

        $layout = $this->getLayoutByUid($this->getRequest()->request->get('layout_uid'));
        $this->isGranted('VIEW', $layout);

        $parent = $this->getPageByUid($this->getRequest()->request->get('parent_uid'));
        $this->isGranted('EDIT', $parent);

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
        $this->trySetPageWorkflowState($page, $this->getRequest()->request->get('workflow_uid'));
        $this->isGranted('CREATE', $page);

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
     *     @Assert\NotBlank(message="title is required"),
     *     @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters")
     * })
     * @Rest\RequestParam(name="url", description="page new url", requirements={
     *     @Assert\NotBlank(message="url is required")
     * })
     * @Rest\RequestParam(name="target", description="page new target", requirements={
     *     @Assert\NotBlank(message="target is required")
     * })
     * @Rest\RequestParam(name="layout_uid", description="page new layout", requirements={
     *     @Assert\NotBlank(message="layout_uid is required")
     * })
     * @Rest\RequestParam(name="state", description="page new state", requirements={
     *     @Assert\NotBlank(message="state is required")
     * })
     * @Rest\RequestParam(name="publishing", description="page new publishing", requirements={
     *     @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     * @Rest\RequestParam(name="archiving", description="page new archiving", requirements={
     *     @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     */
    public function putAction($uid)
    {
        $page = $this->getPageByUid($uid);
        $this->isGranted('EDIT', $page);

        $layout = $this->getLayoutByUid($this->getRequest()->request->get('layout_uid'));
        $this->isGranted('VIEW', $layout);

        $page->setLayout($layout);
        $this->trySetPageWorkflowState($page, $this->getRequest()->request->get('workflow_uid'));
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
            $this->isGranted('PUBLISH', $page);
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse('', 204);
    }

    /**
     * Patch of a page entity
     *
     * @Rest\RequestParam(name="operations", description="Patch operations", requirements={
     *     @Assert\NotBlank(message="operations is required")
     * })
     */
    public function patchAction($uid)
    {
        $page = $this->getPageByUid($uid);
        $this->isGranted('EDIT', $page);

        $operations = $this->getRequest()->request->get('operations');
        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: ' . $e->getMessage());
        }

        $rest_config = $this->getApplication()->getConfig()->getRestConfig();
        $entity_patcher = new EntityPatcher(new RightManager(
            null !== $rest_config ? $rest_config['patcher']['rights'] : array()
        ));
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
     * @Rest\RequestParam(name="parent_uid", description="new parent node uid", requirements={
     *     @Assert\NotBlank(message="parent_uid is required"),
     *     @Assert\Length(min=32, max=32, exactMessage="parent_uid must contains 32 characters")
     * })
     * @Rest\RequestParam(name="next_uid", description="next node uid", requirements={
     *     @Assert\Length(min=32, max=32, exactMessage="next_uid must contains 32 characters")
     * })
     */
    public function movePageNodeAction($uid)
    {
        $page = $this->getPageByUid($uid);

        if (true === $page->isRoot()) {
            throw new AccessDeniedHttpException('Cannot move root node of a site.');
        }

        $parent = $this->getPageByUid($this->getRequest()->request->get('parent_uid'));

        $this->isGranted('EDIT', $page); // user must have edit permission on page
        $this->isGranted('EDIT', $parent); // user must have edit permission on new parent page

        if (true === $page->isOnline(true)) {
            $this->isGranted('PUBLISH', $page); // user must have publish permission on the page
        }

        try {
            if (null === $next = $this->getPageByUid($this->getRequest()->request->get('next_uid', ''), false)) {
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
     * @param  [type] $uid [description]
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($uid)
    {
        $page = $this->getPageByUid($uid);

        if (true === $page->isRoot()) {
            throw new AccessDeniedHttpException('Cannot remove root page of a site.');
        }

        $this->isGranted('EDIT', $page->getParent()); // user must have edit permission on parent

        if (true === $page->isOnline()) {
            $this->isGranted('PUBLISH', $page); // user must have publish permission on the page
        }

        $this->getPageRepository()->toTrash($page);

        return $this->createResponse('', 204);
    }

    /**
     * [clonePageAction description]
     *
     * @return [type] [description]
     */
    private function clonePageAction()
    {
        $source_uid = $this->getRequest()->query->get('source_uid', null);
        if (null === $source_uid) {
            throw new BadRequestHttpException('`source_uid` query parameter is missing.');
        }

        $title = $this->getRequest()->query->get('title', null);
        if (null === $title) {
            throw new BadRequestHttpException('`title` query parameter is missing.');
        }

        $page = $this->getPageByUid($source_uid);

        $this->isGranted('VIEW', $page->getLayout()); // user must have view permission on choosen layout
        $this->isGranted('CREATE', $page); // user must have create permission on page

        if (null !== $page->getParent()) {
            $this->isGranted('EDIT', $page->getParent());
        } else {
            $this->isGranted('EDIT', $this->getApplication()->getSite());
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
     * Getter of page entity by its uid
     *
     * @param  string $uid the uid of the requested page
     *
     * @return null|BackBuilder\NestedNode\Page null if none page exists for the provided uid or the entity page
     */
    private function getPageByUid($uid, $throw_exception = true)
    {
        if (
            null === ($page = $this->getEntityManager()->find('BackBuilder\NestedNode\Page', $uid))
            && true === $throw_exception
        ) {
            throw new NotFoundHttpException("None page exists with uid `$uid`.");
        }

        return $page;
    }

    /**
     * Getter of layout entity by its uid
     *
     * @param  string $uid the uid of the requested layout
     *
     * @return null|BackBuilder\Site\Layout null if none layout exists for the provided uid or the entity layout
     */
    private function getLayoutByUid($uid)
    {
        if (null === $layout = $this->getEntityManager()->find('BackBuilder\Site\Layout', $uid)) {
            throw new NotFoundHttpException("None layout exists with uid `$uid`.");
        }

        return $layout;
    }

    /**
     * Getter of workflow state entity by its uid
     *
     * @param  string $uid the uid of the requested workflow state
     *
     * @return null|BackBuilder\Workflow\State null if none workflow state exists for the provided uid
     *                                      or the entity workflow state
     */
    private function getWorkflowStateByUid($uid)
    {
        return $this->getEntityManager()->find('BackBuilder\Workflow\State', $uid);
    }

    /**
     * Getter for page entity repository
     *
     * @return BackBuilder\NestedNode\Repository\PageRepository
     */
    private function getPageRepository()
    {
        return $this->getEntityManager()->getRepository('BackBuilder\NestedNode\Page');
    }

    /**
     * [trySetPageWorkflowState description]
     * @param  Page   $page               [description]
     * @param  [type] $workflow_state_uid [description]
     * @return [type]                     [description]
     */
    private function trySetPageWorkflowState(Page $page, $workflow_uid)
    {
        $page->setWorkflowState(null);
        if (null !== $workflow_uid) {
            if (null === $workflow = $this->getWorkflowStateByUid($workflow_uid)) {
                return $this->create404Response("None workflow exists with uid `$workflow_uid`.");
            }

            if (null === $workflow->getLayout() || $workflow->getLayout()->getUid() === $page->getLayout()->getUid()) {
                $page->setWorkflowState($workflow);
            }
        }
    }
}
