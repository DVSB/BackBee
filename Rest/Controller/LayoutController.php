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

use BackBuilder\Rest\Controller\Annotations as Rest;
use BackBuilder\Rest\Controller\ARestController;
use BackBuilder\Site\Layout;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class LayoutController extends ARestController
{
    /**
     * @Rest\ParamConverter(
     *   name="site", id_name="site_uid", id_source="query", class="BackBuilder\Site\Site", required=false
     * )
     */
    public function getCollectionAction()
    {
        $layouts = null;
        $site = $this->getEntityFromAttributes('site');
        if (null === $site) {
            $layouts = $this->getEntityManager()->getRepository('BackBuilder\Site\Layout')->getModels();
        } else {
            $layouts = $site->getLayouts();
        }

        return $this->createResponse($this->formatCollection($layouts));
    }

    /**
     * @Rest\ParamConverter(name="layout", class="BackBuilder\Site\Layout")
     * @Rest\Security(expression="is_granted('VIEW', layout)")
     */
    public function getAction(Layout $layout)
    {
        return $this->createResponse($this->formatItem($layout));
    }

    /**
     * @Rest\RequestParam(name="label", requirements={
     *   @Assert\NotBlank()
     * })
     * Rest\RequestParam(name="picpath", requirements={
     *   Assert\NotBlank()
     * })
     * @Rest\RequestParam(name="path", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\RequestParam(name="data", requirements={
     *   @Assert\NotBlank()
     * })
     *
     * @Rest\ParamConverter(name="site", id_name="site_uid", id_source="request", class="BackBuilder\Site\Site")
     */
    public function postAction()
    {
        $layout = new Layout();
        $layout->setData(json_encode($this->getRequest()->request->get('data')));
        $layout->setSite($this->getEntityFromAttributes('site'));
        $layout->setLabel($this->getRequest()->request->get('label'));
        $layout->setPath($this->getRequest()->request->get('path'));
        $layout->setPicPath('img/layouts/' . $layout->getUid() . '.png');

        $this->granted('CREATE', $layout);

        $this->getEntityManager()->persist($layout);
        $this->getEntityManager()->flush($layout);

        return $this->createResponse('', 204);
    }

    /**
     * @Rest\RequestParam(name="label", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\RequestParam(name="picpath", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\RequestParam(name="path", requirements={
     *   @Assert\NotBlank()
     * })
     * @Rest\RequestParam(name="data", requirements={
     *   @Assert\NotBlank()
     * })
     *
     * @Rest\ParamConverter(name="layout", class="BackBuilder\Site\Layout")
     * @Rest\Security(expression="is_granted('EDIT', layout)")
     */
    public function putAction(Layout $layout)
    {
        $layout->setLabel($this->getRequest()->request->get('label'));
        $layout->setData(json_encode($this->getRequest()->request->get('data')));
        $layout->setPath($this->getRequest()->request->get('path'));
        $layout->setPicPath($this->getRequest()->request->get('picpath'));

        $this->getEntityManager()->flush($layout);

        return $this->createResponse('', 204);
    }

    /**
     * @Rest\ParamConverter(name="layout", class="BackBuilder\Site\Layout")
     */
    public function deleteAction(Layout $layout)
    {
        try {
            $this->getEntityManager()->remove($layout);
            $this->getEntityManager()->flush($layout);
        } catch (\Exception $e) {
            return $this->createResponse('Internal server error: ' . $e->getMessage(), 500);
        }

        return $this->createResponse('', 204);
    }
}
