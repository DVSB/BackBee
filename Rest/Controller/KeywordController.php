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

namespace backbee\Rest\Controller;

use BackBee\Rest\Controller\AbstractRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BackBee\Rest\Controller\Annotations as Rest;
use BackBee\NestedNode\KeyWord;

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
            $parent = (null !== $parent) ? $parent : $this->getKeywordRepository()->getRoot();
            $orderInfos = [
                'field' => '_leftnode',
                'dir' => 'asc',
            ];
            $results = $this->getKeywordRepository()->getKeyWords($parent, $orderInfos, array('start' => $start, 'limit' => $count));
        }

        return $this->addRangeToContent($this->createJsonResponse($results), $results, $start);
    }

    private function addRangeToContent(Response $response, $collection, $start)
    {
        $count = count($collection);
        $lastResult = $start + $count - 1;
        $lastResult = $lastResult < 0 ? 0 : $lastResult;
        $response->headers->set('Content-Range', "$start-$lastResult/".$count);

        return $response;
    }

    private function getKeywordRepository()
    {
        return $this->getEntityManager()->getRepository('BackBee\NestedNode\KeyWord');
    }
}
