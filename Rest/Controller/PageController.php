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
     *
     * @param  [type] $uid [description]
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getAction($uid)
    {
        if (null === $page = $this->getPageByUid($uid)) {
            return $this->create404Response("None page exists with uid `$uid`.");
        }

        $this->isGranted('VIEW', $page);

        $serialized_page = json_decode($page->serialize());
        $serialized_page->url = $this->getApplication()->getRenderer()->getUri($serialized_page->url);
        if (null !== $serialized_page->redirect) {
            $serialized_page->redirect = $this->getApplication()->getRenderer()->getUri($serialized_page->redirect);
        }

        $defaultmeta = new MetaDataBag($this->getApplication()->getConfig()->getSection('metadata'));
        $serialized_page->metadata = null === $serialized_page->metadata
            ? $defaultmeta->toArray()
            : array_merge($defaultmeta->toArray(), $page->getMetadata()->toArray())
        ;

        return $this->createResponse(json_encode($serialized_page));
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
        if (null === $page = $this->getPageByUid($uid)) {
            return $this->create404Response("None page exists with uid `$uid`.");
        }

        if (true === $page->isRoot()) {
            return $this->createResponse('Cannot move root node of a site.', 403);
        }

        $parent_uid = $this->getRequest()->request->get('parent_uid');
        if (null === $parent = $this->getPageByUid($parent_uid)) {
            return $this->create404Response("None page exists with uid (parent_uid: `$parent_uid`).");
        }

        $this->isGranted('EDIT', $page); // user must have edit permission on page
        $this->isGranted('EDIT', $page->getParent()); // user must have edit permission on new parent page

        if (true === $page->isOnline(true)) {
            $this->isGranted('PUBLISH', $page); // user must have publish permission on the page
        }

        try {
            if (null === $next = $this->getPageByUid($this->getRequest()->request->get('next_uid', ''))) {
                $this->getPageRepository()->moveAsLastChildOf($page, $parent);
            } else {
                if (false === $next->getParent()->equals($parent)) {
                    return $this->createResponse('Next node must have the same parent node.', 403);
                }

                $this->getPageRepository()->moveAsPrevSiblingOf($page, $next);
            }
        } catch (InvalidArgumentException $e) {
            return $this->createResponse('Invalid node move action: ' . $e->getMessage(), 403);
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse($this->buildLeafJSON($page, 'folder'));
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

        $layout_uid = $this->getRequest()->request->get('layout_uid');
        if (null === $layout = $this->getLayoutByUid($layout_uid)) {
            return $this->create404Response("None layout exists with uid `$layout_uid`.");
        }

        $parent_uid = $this->getRequest()->request->get('parent_uid');
        if (null === $parent = $this->getPageByUid($parent_uid)) {
            return $this->create404Response("None page exists with uid `$parent_uid`.");
        }

        if (0 === strlen($title = $this->getRequest()->request->get('title', null))) {
            return $this->create404Response('Page\'s title cannot be empty.');
        }

        $builder = $this->getApplication()->getContainer()->get('pagebuilder');
        $builder->setLayout($layout);
        $builder->setParent($parent);
        $builder->setRoot($parent->getRoot());
        $builder->setSite($parent->getSite());
        $builder->setTitle($title);
        $page = $builder->getPage();

        if (0 < strlen($target = $this->getRequest()->request->get('target', null))) {
            $page->setTarget($target);
        }

        if (0 < strlen($redirect = $this->getRequest()->request->get('redirect', null))) {
            $page->setRedirect($redirect);
        }

        if (0 < strlen($url = $this->getRequest()->request->get('url', null))) {
            $page->setUrl($url);
        }

        if (0 < strlen($alt_title = $this->getRequest()->request->get('alt_title', null))) {
            $page->setAltTitle($alt_title);
        }

        $this->getEntityManager()->persist($page);
        try {
            $this->getEntityManager()->flush($page);
            $this->getPageRepository()->updateTreeNatively($page->getRoot()->getUid());
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage(), 500);
        }

        return $this->createResponse(json_encode(array(
            'attr'  => json_decode($page->serialize(), true),
            'data'  => html_entity_decode($page->getTitle(), ENT_COMPAT, 'UTF-8'),
            'state' => 'closed'
        )));
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
        if (null === $page = $this->getPageByUid($uid)) {
            return $this->create404Response("None page exists with uid `$uid`.");
        }

        $this->isGranted('EDIT', $page);

        $operations = $this->getRequest()->request->get('operations');
        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            return $this->createResponse('operation invalid syntax: ' . $e->getMessage(), 400);
        }

        $rest_config = $this->getApplication()->getConfig()->getRestConfig();
        $entity_patcher = new EntityPatcher(new RightManager(
            null !== $rest_config ? $rest_config['patcher']['rights'] : array()
        ));
        try {
            $entity_patcher->patch($page, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            return $this->createResponse('Invalid patch operation: ' . $e->getMessage(), 403);
        }

        $this->getEntityManager()->flush($page);

        return $this->createResponse(json_encode(true));
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
        if (null === $page = $this->getPageByUid($uid)) {
            return $this->create404Response("None page exists with uid `$uid`.");
        }

        if (true === $page->isRoot()) {
            return $this->createResponse('Cannot remove root page of a site.', 403);
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
            return $this->createResponse('`source_uid` query parameter is missing.', 400);
        }

        $title = $this->getRequest()->query->get('title', null);
        if (null === $title) {
            return $this->createResponse('`title` query parameter is missing.', 400);
        }

        if (null === $page = $this->getPageByUid($source_uid)) {
            return $this->create404Response("None page exists with uid `$uid`.");
        }

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

        return $this->createResponse($this->buildLeafJSON($new_page, 'leaf'));
    }

    /**
     * Getter of page entity by its uid
     *
     * @param  string $uid the uid of the requested page
     *
     * @return null|BackBuilder\NestedNode\Page null if none page exists for the provided uid or the entity page
     */
    private function getPageByUid($uid)
    {
        return $this->getEntityManager()->find('BackBuilder\NestedNode\Page', $uid);
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
        return $this->getEntityManager()->find('BackBuilder\Site\Layout', $uid);
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
     * [getPageId description]
     * @param  Page   $page [description]
     * @return [type]       [description]
     */
    private function getPageId(Page $page)
    {
        return 'node_' . $page->getUid();
    }

    /**
     * [buildLeafJSON description]
     *
     * @param  [type] $page [description]
     * @param  [type] $rel  [description]
     *
     * @return [type]       [description]
     */
    private function buildLeafJSON($page, $rel)
    {
        return json_encode(array(
            'attr'  => array(
                'rel' => $rel,
                'id'  => $this->getPageId($page)
            ),
            'data'  => $page->getTitle(),
            'state' => 'closed'
        ));
    }
}
