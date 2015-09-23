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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\NestedNode\KeyWord;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use BackBee\Rest\Patcher\EntityPatcher;
use BackBee\Rest\Patcher\Exception\InvalidOperationSyntaxException;
use BackBee\Rest\Patcher\Exception\UnauthorizedPatchOperationException;
use BackBee\Rest\Patcher\RightManager;
use BackBee\Rest\Patcher\OperationSyntaxValidator;

/**
 * ClassContent API Controller.
 *
 * @author h.baptiste
 */
class KeywordController extends AbstractRestController
{
    /**
     * @param Request $request
     *
     * @Rest\Pagination(default_count=25, max_count=100)
     *
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="query", class="BackBee\NestedNode\KeyWord", required=false
     * )
     */
    public function getCollectionAction(Request $request, $start, $count, KeyWord $parent = null)
    {
        $results = [];
        $term = $request->query->get('term', null);
        if (null !== $term) {
            $results = $this->getKeywordRepository()->getLikeKeyWords($term);
        } else {
            $orderInfos = [
                'field' => '_leftnode',
                'dir' => 'asc',
            ];
            $results = $this->getKeywordRepository()->getKeyWords($parent, $orderInfos, array('start' => $start, 'limit' => $count));
        }

        return $this->addRangeToContent($this->createJsonResponse($results), $results, $start);
    }

    /**
     * Get Keyword by uid.
     *
     * @param string $uid the unique identifier of the page we want to retrieve
     *
     * @return Symfony\Component\HttpFoundation\Response
     *
     * @Rest\ParamConverter(name="keyword", class="BackBee\NestedNode\KeyWord")
     * @Rest\Security(expression="is_granted('VIEW', keyword)")
     */
    public function getAction(KeyWord $keyword)
    {
        return $this->createJsonResponse($keyword);
    }

    /**
     * @return Response
     *
     * @Rest\ParamConverter(name="keyword", class="BackBee\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('DELETE', '\BackBee\NestedNode\KeyWord')")
     */
    public function deleteAction(KeyWord $keyword = null)
    {
        if (!$keyword) {
            throw new BadRequestHttpException('A keyword show be provided.');
        }

       /* delete only if keyword is not used */
       if (!$keyword->getContent()->isEmpty()) {
           throw new BadRequestHttpException(sprintf('Keyword `%s` is linked to a content', $keyword->getKeyWord()));
       }
        $this->getKeywordRepository()->delete($keyword);

        return new Response('', 204);
    }

    /**
     * @param KeyWord $keyword
     * @param Request $request
     *
     * @return Response
     *
     * @Rest\ParamConverter(name="keyword", class="BackBee\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('EDIT', '\BackBee\NestedNode\KeyWord')")
     */
    public function patchAction(KeyWord $keyword, Request $request)
    {
        $operations = $request->request->all();
        try {
            (new OperationSyntaxValidator())->validate($operations);
        } catch (InvalidOperationSyntaxException $e) {
            throw new BadRequestHttpException('operation invalid syntax: '.$e->getMessage());
        }

        $this->patchSiblingAndParentOperation($keyword, $operations);
        $entityPatcher = new EntityPatcher(new RightManager($this->getSerializer()->getMetadataFactory()));
        try {
            $entityPatcher->patch($keyword, $operations);
        } catch (UnauthorizedPatchOperationException $e) {
            throw new BadRequestHttpException('Invalid patch operation: '.$e->getMessage());
        }

        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    private function addRangeToContent(Response $response, $collection, $start)
    {
        $count = count($collection);
        $lastResult = $start + $count - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/".$count);

        return $response;
    }

    private function patchSiblingAndParentOperation(KeyWord $keyword, &$operations)
    {
        $siblingOperation = null;
        $parentOperation = null;

        foreach ($operations as $key => $operation) {
            $op = array('key' => $key, 'op' => $operation);
            if ('/sibling_uid' === $operation['path']) {
                $siblingOperation = $op;
            } elseif ('/parent_uid' === $operation['path']) {
                $parentOperation = $op;
            }
        }

        if (null !== $siblingOperation || null !== $parentOperation) {
            if ($keyword->isRoot()) {
                throw new BadRequestHttpException('Cannot move root node of a site.');
            }
            try {
                if (null !== $siblingOperation) {
                    unset($operations[$siblingOperation['key']]);

                    $sibling = $this->getKeywordByUid($siblingOperation['op']['value']);
                    $this->getKeywordRepository()->moveAsPrevSiblingOf($keyword, $sibling);
                } elseif (null !== $parentOperation) {
                    unset($operations[$parentOperation['key']]);

                    $parent = $this->getKeywordByUid($parentOperation['op']['value']);
                    $this->getKeywordRepository()->moveAsLastChildOf($keyword, $parent);
                }
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException(sprintf('Invalid node move action: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Create a keyword object
     * and if a parent is provided add the keyword as its last child.
     *
     * @param KeyWord $keyword
     *
     * @Rest\RequestParam(name="keyword", description="Keyword value", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\ParamConverter(
     *   name="parent", id_name="parent_uid", id_source="request", class="BackBee\NestedNode\KeyWord", required=false
     * )
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('CREATE', '\BackBee\NestedNode\KeyWord')")
     */
    public function postAction(Request $request, $parent = null)
    {
        try {
            $keyWordLabel = trim($request->request->get('keyword'));
            $uid = $request->request->get('uid', null);
            if (null !== $uid) {
                $keywordItem = $this->getKeywordRepository()->find($uid);
                $keywordItem->setKeyWord($keyWordLabel);
            } else {
                $keywordItem = new KeyWord();
                $keywordItem->setKeyWord($keyWordLabel);
                if (null === $parent) {
                    $parent = $this->getKeywordRepository()->getRoot();
                }

                if ($this->keywordAlreadyExists($keyWordLabel)) {
                    throw new BadRequestHttpException(sprintf(
                        'A Keyword named `%s` already exists.',
                        $keyWordLabel
                    ));
                }
                $keywordItem->setParent($parent);
                $this->getKeywordRepository()->insertNodeAsLastChildOf($keywordItem, $parent);
            }

            $this->getEntityManager()->persist($keywordItem);
            $this->getEntityManager()->flush();

            $response = $this->createJsonResponse(null, 201, [
                'BB-RESOURCE-UID' => $keywordItem->getUid(),
                'Location' => $this->getApplication()->getRouting()->getUrlByRouteName(
                    'bb.rest.keyword.get',
                    [
                        'version' => $request->attributes->get('version'),
                        'uid' => $keywordItem->getUid(),
                    ],
                    '',
                    false
                ),
            ]);
        } catch (\Exception $e) {
            $response = $this->createResponse(sprintf('Internal server error: %s', $e->getMessage()), 500);
        }

        return $response;
    }

    /**
     * @return Response
     *
     * @Rest\RequestParam(name="keyword", description="Keyword value", requirements={
     *      @Assert\NotBlank()
     * })
     * @Rest\ParamConverter(name="keyword", class="BackBee\NestedNode\KeyWord")
     * @Rest\Security("is_fully_authenticated() & has_role('ROLE_API_USER') & is_granted('EDIT', '\BackBee\NestedNode\KeyWord')")
     */
    public function putAction(KeyWord $keyword, Request $request)
    {
        $parentId = $request->get('parent_uid', null);
        if (null === $parentId) {
            $parent = $this->getKeywordRepository()->getRoot();
        } else {
            $parent = $this->getKeywordRepository()->find($parentId);
        }

        $keywordLabel = trim($request->request->get('keyword'));

        if ($this->keywordAlreadyExists($keywordLabel)) {
            throw new BadRequestHttpException(sprintf('A KeyWord named %s already exists.', $keyword));
        }

        $keyword->setKeyWord($keywordLabel);

        $this->getEntityManager()->persist($keyword);
        $this->getEntityManager()->flush();

        return $this->createJsonResponse(null, 204);
    }

    private function keywordAlreadyExists($keywordLabel)
    {
        $kwExists = false;
        $keywordItem = $this->getKeywordRepository()->findOneBy([
            '_keyWord' => strtolower(trim($keywordLabel)),
        ]);

        if (null !== $keywordItem) {
            $kwExists = true;
        }

        return $kwExists;
    }

    private function getKeywordByUid($uid)
    {
        if (null === $keyword = $this->getKeywordRepository()->find($uid)) {
            throw new NotFoundHttpException("Unable to find keyword with uid `$uid`");
        }

        return $keyword;
    }

    private function getKeywordRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\KeyWord');
    }
}
