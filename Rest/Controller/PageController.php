<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Validator\Constraints as Assert;

use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\Exception\InvalidArgumentException;
use BackBee\MetaData\MetaDataBag;
use BackBee\NestedNode\Page;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\Rest\Patcher\EntityPatcher;
use BackBee\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\Rest\Patcher\OperationSyntaxValidator;
use BackBee\Rest\Patcher\RightManager;
use BackBee\Site\Layout;
use BackBee\Workflow\State;

/**
 * Page Controller.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class PageController extends AbstractRestController
{
    /**
     * Returns page entity available status.
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getAvailableStatusAction()
    {
        return $this->createJsonResponse(Page::$STATES);
    }

    /**
     * Get page's metadatas.
     *
     * @param Page $page the page we want to get its metadatas
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     */
    public function getMetadataAction(Page $page)
    {
        $metadata = null !== $page->getMetaData() ? $page->getMetaData()->jsonSerialize() : array();
        $default_metadata = new MetaDataBag($this->getApplication()->getConfig()->getSection('metadata'));
        $metadata = array_merge($default_metadata->jsonSerialize(), $metadata);

        return $this->createJsonResponse($metadata);
    }

    /**
     * Update page's metadatas.
     *
     * @param Page    $page    the page we want to update its metadatas
     * @param Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     */
    public function putMetadataAction(Page $page, Request $request)
    {
        $metadatas = $page->getMetaData();

        foreach ($request->request->all() as $name => $attributes) {
            if ($metadatas->has($name)) {
                foreach ($attributes as $attr_name => $attr_value) {
                    if ($attr_value !== $metadatas->get($name)->getAttribute($attr_name)) {
                        $metadatas->get($name)->setAttribute($attr_name, $attr_value);
                    }
                }
            }
        }

        $page->setMetaData($metadatas->compute($page));
        $this->getApplication()->getEntityManager()->flush($page);

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Get collection of page entity.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @Rest\QueryParam(name="parent_uid", description="Parent Page UID")
     *
     * @Rest\QueryParam(name="order_by", description="Page order by", requirements={
     *   @Assert\Type(type="array", message="An array containing at least 1 column name to order by must be provided"),
     *   @Assert\All({
     *     @Assert\Choice(choices = {"asc", "desc"}, message="order direction is not valid")
     *   })
     * })
     *
     * @Rest\QueryParam(name="state", description="Page State", requirements={
     *   @Assert\Type(type="array", message="An array containing at least 1 state must be provided"),
     *   @Assert\All({
     *     @Assert\Choice(choices = {"0", "1", "2", "3", "4"}, message="State is not valid")
     *   })
     * })
     *
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBee\NestedNode\Page", required=false
     * )
     */
    public function getCollectionAction(Request $request, $start, $count, Page $parent = null)
    {
        $response = null;
        $content_uid = $request->query->get('content_uid', null);
        $content_type = $request->query->get('content_type', null);

        if (null !== $content_uid && null !== $content_type) {
            $response = $this->doGetCollectionByContent($content_type, $content_uid);
        } elseif ((null === $content_uid && null !== $content_type) || (null !== $content_uid && null === $content_type)) {
            throw new BadRequestHttpException(
                'To get page collection by content, you must provide `content_uid` and `content_type` as query parameters.'
            );
        } else {
            $response = $this->doClassicGetCollection($request, $start, $count, $parent);
        }

        return $response;
    }

    /**
     * Get page by uid.
     *
     * @param string $uid the unique identifier of the page we want to retrieve
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     * @Rest\Security(expression="is_granted('VIEW', page)")
     */
    public function getAction(Page $page)
    {
        return $this->createResponse($this->formatItem($page));
    }

    /**
     * Create a page.
     *
     * @Rest\RequestParam(name="title", description="Page title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contain at least 3 characters"),
     *   @Assert\NotBlank()
     * })
     *
     * @Rest\ParamConverter(
     *   name="layout", id_name="layout_uid", id_source="request", class="BackBee\Site\Layout", required=true
     * )
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\NestedNode\Page", required=false
     * )
     * @Rest\ParamConverter(
     *   name="source", id_name="source_uid", id_source="query", class="BackBee\NestedNode\Page", required=false
     * )
     * @Rest\ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBee\Workflow\State", required=false
     * )
     *
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function postAction(Layout $layout, Request $request, Page $parent = null)
    {
        if (null !== $parent) {
            $this->granted(MaskBuilder::MASK_EDIT, $parent);
        }

        $builder = $this->getApplication()->getContainer()->get('pagebuilder');
        $builder->setLayout($layout);

        if (null !== $parent) {
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
            return $this->createResponse('Internal server error: '.$e->getMessage(), 500);
        }

        return $this->createJsonResponse('', 201, array(
            'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.page.get',
                array(
                    'version' => $request->attributes->get('version'),
                    'uid'     => $page->getUid(),
                ),
                '',
                false
            ),
        ));
    }

    /**
     * Update page.
     *
     * @return Symfony\Component\HttpFoundation\Response
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
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     * @Rest\ParamConverter(name="layout", id_name="layout_uid", class="BackBee\Site\Layout", id_source="request")
     * @Rest\ParamConverter(
     *   name="workflow", id_name="workflow_uid", id_source="request", class="BackBee\Workflow\State", required=false
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

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Patch page.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\RequestParam(name="0", description="Patch operations", requirements={
     *   @Assert\NotBlank(message="Request must contain at least one operation")
     * })
     *
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     * @Rest\Security(expression="is_granted('EDIT', page)")
     */
    public function patchAction(Page $page, Request $request)
    {
        $operations = $request->request->all();

        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $entity_patcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));

        $this->patchStateOperation($page, $operations);
        $this->patchSiblingAndParentOperation($page, $operations);

        try {
            $entity_patcher->patch($page, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new BadRequestHttpException('Invalid patch operation: '.$e->getMessage());
        }

        if (true === $page->isOnline(true)) {
            $this->granted('PUBLISH', $page);
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Delete page.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="page", class="BackBee\NestedNode\Page")
     */
    public function deleteAction(Page $page)
    {
        if (true === $page->isRoot()) {
            throw new BadRequestHttpException('Cannot remove root page of a site.');
        }

        $this->granted('EDIT', $page->getParent()); // user must have edit permission on parent

        if (true === $page->isOnline()) {
            $this->granted('PUBLISH', $page); // user must have publish permission on the page
        }

        $this->getPageRepository()->toTrash($page);

        return $this->createJsonResponse(null, 204);
    }

    /**
     * Clone a page.
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\RequestParam(name="title", description="Cloning page new title", requirements={
     *   @Assert\Length(min=3, minMessage="Title must contains atleast 3 characters"),
     *   @Assert\NotBlank
     * })
     *
     * @Rest\ParamConverter(name="source", class="BackBee\NestedNode\Page")
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\NestedNode\Page", required=false
     * )
     * @Rest\ParamConverter(
     *   name="sibling", id_name="sibling_uid", id_source="request", class="BackBee\NestedNode\Page", required=false
     * )
     *
     * @Rest\Security(expression="is_granted('CREATE', source)")
     */
    public function cloneAction(Page $source, Page $parent = null, $sibling = null, Request $request)
    {
        // user must have view permission on chosen layout
        $this->granted('VIEW', $source->getLayout());

        if (null !== $source->getParent()) {
            $this->granted('EDIT', $source->getParent());
        } else {
            $this->granted('EDIT', $this->getApplication()->getSite());
        }

        $page = $this->getPageRepository()->duplicate(
            $source,
            $request->request->get('title'),
            $source->getParent(),
            true,
            $this->getApplication()->getBBUserToken()
        );

        if (null !== $sibling) {
            $this->granted('EDIT', $sibling->getParent());
            $this->getPageRepository()->moveAsPrevSiblingOf($page, $sibling);
        } elseif (null !== $parent) {
            $this->granted('EDIT', $parent);
            $this->getPageRepository()->moveAsLastChildOf($page, $parent);
        }

        $this->getApplication()->getEntityManager()->flush();

        return $this->createJsonResponse(null, 201, array(
            'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                'bb.rest.page.get',
                array(
                    'version' => $request->attributes->get('version'),
                    'uid'     => $page->getUid(),
                ),
                '',
                false
            ),
        ));
    }

    /**
     * Getter for page entity repository.
     *
     * @return \BackBee\NestedNode\Repository\PageRepository
     */
    private function getPageRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\Page');
    }

    /**
     * Returns every pages that contains provided classcontent.
     *
     * @param string $content_type
     * @param string $content_uid
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    private function doGetCollectionByContent($content_type, $content_uid)
    {
        $content = null;
        $classname = AbstractClassContent::getClassnameByContentType($content_type);
        $em = $this->getApplication()->getEntityManager();

        try {
            $content = $em->find($classname, $content_uid);
        } catch (ClassNotFoundException $e) {
            throw new NotFoundHttpException("No classcontent found with provided type (:$content_type)");
        }

        if (null === $content) {
            throw new NotFoundHttpException("No `$classname` exists with uid `$content_uid`");
        }

        $pages = $em->getRepository("BackBee\ClassContent\AbstractClassContent")->findPagesByContent($content);

        $response = $this->createResponse($this->formatCollection($pages));
        if (0 < count($pages)) {
            $response->headers->set('Content-Range', '0-'.(count($pages) - 1).'/'.count($pages));
        }

        return $response;
    }

    /**
     * Returns pages collection by doing classic selection and by applying filters provided in request
     * query parameters.
     *
     * @param Request   $request
     * @param integer   $start
     * @param integer   $count
     * @param Page|null $parent
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    private function doClassicGetCollection(Request $request, $start, $count, Page $parent = null)
    {
        $qb = $this->getPageRepository()->createQueryBuilder('p');
        if (null === $parent) {
            $qb->andSiteIs($this->getApplication()->getSite());
        } else {
            $this->granted('VIEW', $parent);
        }

        $qb = $qb->andParentIs($parent);

        $order_by = array();
        if (null !== $request->query->get('order_by', null)) {
            foreach ($request->query->get('order_by') as $key => $value) {
                if ('_' !== $key[0]) {
                    $key = '_'.$key;
                }

                $order_by[$key] = $value;
            }
        } else {
            $order_by['_leftnode'] = 'asc';
        }

        $qb->orderByMultiple($order_by);

        if (null !== $state = $request->query->get('state', null)) {
            $qb->andStateIsIn((array) $state);
        }

        $results = new Paginator($qb->setFirstResult($start)->setMaxResults($count));
        $count = 0;
        foreach ($results as $row) {
            $count++;
        }

        $result_count = $start + $count - 1; // minus 1 cause $start start at 0 and not 1
        $response = $this->createResponse($this->formatCollection($results));
        if (0 < $count) {
            $response->headers->set('Content-Range', "$start-$result_count/".count($results));
        }

        return $response;
    }

    /**
     * Page workflow state setter.
     *
     * @param Page  $page
     * @param State $workflow
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

    /**
     * Custom patch process for Page's state property.
     *
     * @param Page  $page
     * @param array $operations passed by reference
     */
    private function patchStateOperation(Page $page, array &$operations)
    {
        $state_operation = null;
        $is_visible_operation = null;
        foreach ($operations as $key => $operation) {
            $op = array('key' => $key, 'op' => $operation);
            if ('/state' === $operation['path']) {
                $state_operation = $op;
            } elseif ('/is_visible' === $operation['path']) {
                $is_visible_operation = $op;
            }
        }

        if (null !== $state_operation) {
            unset($operations[$state_operation['key']]);
            $states = explode('_', $state_operation['op']['value']);
            if (in_array($state = (int) array_shift($states), Page::$STATES)) {
                $page->setState($state | ($page->getState() & Page::STATE_HIDDEN ? Page::STATE_HIDDEN : 0));
            }

            if ($code = (int) array_shift($states)) {
                $workflow_state = $this->getApplication()->getEntityManager()
                    ->getRepository('BackBee\Workflow\State')
                    ->findOneBy(array(
                        '_code'   => $code,
                        '_layout' => $page->getLayout(),
                    ))
                ;

                if (null !== $workflow_state) {
                    $page->setWorkflowState($workflow_state);
                }
            }
        }

        if (null !== $is_visible_operation) {
            unset($operations[$is_visible_operation['key']]);

            $is_visible = (boolean) $is_visible_operation['op']['value'];

            if ($is_visible && ($page->getState() & Page::STATE_HIDDEN)) {
                $page->setState($page->getState() ^ Page::STATE_HIDDEN);
            } elseif (!$is_visible && !($page->getState() & Page::STATE_HIDDEN)) {
                $page->setState($page->getState() | Page::STATE_HIDDEN);
            }
        }
    }

    /**
     * Custom patch process for Page's sibling or parent node.
     *
     * @param Page  $page
     * @param array $operations passed by reference
     */
    private function patchSiblingAndParentOperation(Page $page, array &$operations)
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
            if ($page->isRoot()) {
                throw new BadRequestHttpException('Cannot move root node of a site.');
            }

            if ($page->isOnline(true)) {
                $this->granted('PUBLISH', $page); // user must have publish permission on the page
            }
        }

        try {
            if (null !== $sibling_operation) {
                unset($operations[$sibling_operation['key']]);

                $sibling = $this->getPageByUid($sibling_operation['op']['value']);
                $this->granted('EDIT', $sibling->getParent());

                $this->getPageRepository()->moveAsPrevSiblingOf($page, $sibling);
            } elseif (null !== $parent_operation) {
                unset($operations[$parent_operation['key']]);

                $parent = $this->getPageByUid($parent_operation['op']['value']);
                $this->granted('EDIT', $parent);

                $this->getPageRepository()->moveAsLastChildOf($page, $parent);
            }
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException('Invalid node move action: '.$e->getMessage());
        }
    }

    /**
     * Retrieves page entity with provided uid.
     *
     * @param string $uid
     *
     * @return Page
     *
     * @throws NotFoundHttpException raised if page not found with provided uid
     */
    private function getPageByUid($uid)
    {
        if (null === $page = $this->getApplication()->getEntityManager()->find('BackBee\NestedNode\Page', $uid)) {
            throw new NotFoundHttpException("Unable to find page with uid `$uid`");
        }

        return $page;
    }
}
