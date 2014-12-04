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

namespace BackBuilder\Rest\Tests\Fixtures\Controller;

use Symfony\Component\Validator\Constraints as Assert;
use BackBuilder\Rest\Controller\Annotations as Rest;

/**
 * Fixture Controller
 *
 * @Annotation
 *
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class FixtureAnnotatedController
{
    /**
     * @Rest\Pagination
     */
    public function defaultPaginationAction()
    {
    }

    /**
     * @Rest\Pagination(default_count=20, max_count=100, min_count=10)
     */
    public function customPaginationAction()
    {
    }

    /**
     * @Rest\RequestParam(name = "name", key = "_name", requirements = {
     *  @Assert\NotBlank(message="Name not provided"),
     *  @Assert\Length(min = "2", max = "50")
     * })
     *
     * @Rest\RequestParam(name = "url", default = "http://test.com", requirements = {
     *  @Assert\Url()
     * })
     *
     * @Rest\QueryParam(name = "url", default = "http://test.com", requirements = {
     *  @Assert\Url()
     * })
     *
     * @Rest\Pagination(default_count=20, max_count=100, min_count=10)
     */
    public function requestParamsAction()
    {
    }

    /**
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name not provided"),
     *  @Assert\Length(min = "2", max = "50")
     * })
     *
     * @Rest\RequestParam(name = "nameDefault", default = "DefaultName", requirements = {
     *  @Assert\NotBlank(message="Name not provided"),
     *  @Assert\Length(min = "2", max = "50")
     * })
     * @Rest\RequestParam(name = "fieldWithoutRequirements")
     *
     */
    public function requestParamsWithoutViolationsArgumentAction()
    {
    }

    /**
     * @Rest\QueryParam(name = "queryParamField")
     *
     */
    public function queryParamsAction()
    {
    }

    /**
     * @Rest\RequestParam(name = "name", requirements = {
     *  @Assert\NotBlank(message="Name not provided"),
     *  @Assert\Length(min = "2", max = "50")
     * })
     */
    public function requestParamsWithViolationsArgumentAction(\Symfony\Component\Validator\ConstraintViolationList $violations)
    {
    }

    /**
     * this is not a controller action
     */
    public function justARandomMethod()
    {
        return 1;
    }

    /**
     * This is not a valid controller action as it is not a public method
     */
    private function privateMethodInvalidAction()
    {
        return 'privateMethodInvalidAction';
    }

    /**
     *
     *
     */
    public function noMetadataAction()
    {
    }
}
