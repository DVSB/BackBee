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
use BackBuilder\NestedNode\Page,
    BackBuilder\Site\Layout;
use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Patcher\EntityPatcher;
use BackBuilder\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBuilder\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBuilder\Rest\Patcher\OperationSyntaxValidator;
use BackBuilder\Rest\Patcher\RightManager;
use BackBuilder\Workflow\State;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;


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
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @Rest\QueryParam(name="parent_uid", description="Parent Page UID")
     * @Rest\QueryParam(name="state", description="State", requirements={
     *   @Assert\Choice(choices = {0, 1, 2, 3, 4}, message="State is not valid")
     * })
     * @Rest\QueryParam(name="order", description="Order by field", default="leftnode", requirements={
     *   @Assert\Choice(choices = {"leftnode", "date", "title"}, message="Order by is not valid")
     * })
     * @Rest\QueryParam(name="dir", description="Order direction", default="asc", requirements={
     *   @Assert\Choice(choices = {"asc", "desc"}, message="Order direction is not valid")
     * })
     * 
     * @Rest\QueryParam(name="depth", description="Page depth", requirements={
     *   @Assert\Range(min = 0, max = 100, minMessage="Page depth must be a positive number", maxMessage="Page depth cannot be greater than 100")
     * })
     *
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBuilder\NestedNode\Page", required=false
     * )
     */
    public function getCollectionAction(Request $request, $start, $count, Page $parent = null)
    {
        $qb = $this->getPageRepository()->createQueryBuilder('p')
            ->orderByMultiple(['_' . $request->query->get('order') => $request->query->get('dir')])
        ;

        if(null !== $parent) {
            // parent was defined - don't include it in the results
            $qb = $qb->andIsDescendantOf($parent, true);
        } else {
            // parent wasn't defined - retrieve the site's home page & include it in the returned results
            $parent = $this->getPageRepository()->getRoot($this->getApplication()->getSite());
            $qb = $qb->andIsDescendantOf($parent, false);
        }

        $this->granted('VIEW', $parent);
        
        $qb
            ->setFirstResult($start)
            ->setMaxResults($count)
        ;

        if (null !== $request->query->get('state')) {
            $qb->andStateIsIn((array) $request->query->get('state'));
        }
        
        if (null !== $request->query->get('depth')) {
            $qb->andLevelIsLowerThan($parent->getLevel() + $request->query->get('depth'));
        }

        $results = $qb->getQuery()->getResult();

        $result_count = $start + count($results);

        $response = $this->createResponse($this->formatCollection($results));
        $response->headers->set('Content-Range', "$start-$result_count/" . count($results));

        return $response;
    }

    /**
     * Get page by uid
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
     * Create a page
     *
     * @Rest\RequestParam(name="title", description="Page title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contain at least 3 characters"),
     *   @Assert\NotBlank()
     * })
     *
     * @Rest\ParamConverter(
     *   name="layout", id_name="layout_uid", id_source="request", class="BackBuilder\Site\Layout", required=true
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
     *
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function postAction(Layout $layout, Request $request, Page $parent = null)
    {
        if(null !== $parent) {
            $this->granted(MaskBuilder::MASK_EDIT, $parent);
        }

        $builder = $this->getApplication()->getContainer()->get('pagebuilder');
        $builder->setLayout($layout);

        if(null !== $parent) {
            $builder->setParent($parent);
            $builder->setRoot($parent->getRoot());
            $builder->setSite($parent->getSite());
        } else {
            $builder->setSite($this->getApplication()->getSite());
        }

        $builder->setTitle($request->request->get('title'));
        $builder->setUrl($request->request->get('url', null));
        $builder->setState($request->request->get('state'));
        $builder->setTarget($request->request->get('target'));
        $builder->setRedirect($request->request->get('redirect'));
        $builder->setAltTitle($request->request->get('alttitle'));
        $builder->setPublishing(
            null !== $request->request->get('publishing')
                ? new \DateTime(date('c', $request->request->get('publishing')))
                : null
        );

        $builder->setArchiving(
            null !== $request->request->get('archiving')
                ? new \DateTime(date('c', $request->request->get('archiving')))
                : null
        );

        try {
            $page = $builder->getPage();

            $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));
            $this->granted('CREATE', $page);

            $this->getEntityManager()->persist($page);

            $this->getEntityManager()->flush($page);
            $this->getPageRepository()->updateTreeNatively($page->getRoot()->getUid());
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage(), 500);
        }


        return $this->redirect(
            $this->getApplication()->getRouting()->getUri($page->getUrl(), null, $page->getSite()),
            201
        );
    }

    /**
     * Update page
     *
     * @Rest\RequestParam(name="title", description="Page title", requirements={
     *   @Assert\NotBlank(message="title is required")
     * })
     * @Rest\RequestParam(name="url", description="page url", requirements={
     *   @Assert\NotBlank(message="url is required")
     * })
     * @Rest\RequestParam(name="target", description="page target", requirements={
     *   @Assert\NotBlank(message="target is required")
     * })
     * @Rest\RequestParam(name="state", description="page state", requirements={
     *   @Assert\NotBlank(message="state is required")
     * })
     * @Rest\RequestParam(name="publishing", description="Publishing flag", requirements={
     *   @Assert\Type(type="digit", message="The value should be a positive number")
     * })
     * @Rest\RequestParam(name="archiving", description="Archiving flag", requirements={
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
    public function putAction(Page $page, Layout $layout, Request $request)
    {
        $page->setLayout($layout);
        $this->trySetPageWorkflowState($page, $this->getEntityFromAttributes('workflow'));

        $page->setTitle($request->request->get('title'))
            ->setUrl($request->request->get('url'))
            ->setTarget($request->request->get('target'))
            ->setState($request->request->get('state'))
            ->setRedirect($request->request->get('redirect', null))
            ->setAltTitle($request->request->get('alttitle', null))
        ;

        $publishing = $request->request->get('publishing');
        $page->setPublishing(null !== $publishing ? new \DateTime(date('c', $publishing)) : null);

        $archiving = $request->request->get('archiving');
        $page->setArchiving(null !== $archiving ? new \DateTime(date('c', $archiving)) : null);

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse('', 204);
    }

    /**
     * Patch page
     *
     * @Rest\RequestParam(name="0", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="Request must contain at least one operation")
     * })
     *
     * @Rest\ParamConverter(name="page", class="BackBuilder\NestedNode\Page")
     * @Rest\Security(expression="is_granted('EDIT', page)")
     */
    public function patchAction(Page $page, Request $request)
    {
        $operations = $request->request->all();

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

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
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
    public function moveNodeAction(Page $page, Page $parent)
    {
        if (true === $page->isRoot()) {
            throw new AccessDeniedHttpException('Cannot move root node of a site.');
        }

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page); // user must have publish permission on the page
        }

        try {
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
     * Delete page
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
     * Clone a page
     *
     * @Rest\RequestParam(name="title", description="Cloning page new title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters")
     * })
     *
     * @Rest\ParamConverter(
     *   name="source", id_name="source_uid", id_source="request", class="BackBuilder\NestedNode\Page", required=true
     * )
     *
     * @Rest\Security(expression="is_granted('CREATE', source)")
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function cloneAction(Page $source, Request $request)
    {
        // user must have view permission on chosen layout
        $this->granted('VIEW', $source->getLayout());

        if (null !== $source->getParent()) {
            $this->granted('EDIT', $source->getParent());
        } else {
            $this->granted('EDIT', $this->getApplication()->getSite());
        }

        $newPage = $this->getPageRepository()->duplicate(
            $source, $request->request->get('title'), $source->getParent(), true, $this->getApplication()->getBBUserToken()
        );

        return $this->redirect(
            $this->getApplication()->getRouting()->getUri($newPage->getUrl(), null, $newPage->getSite()),
            201
        );
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
