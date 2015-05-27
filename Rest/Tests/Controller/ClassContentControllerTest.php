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

namespace BackBee\Rest\Tests\Controller;

use Symfony\Component\Yaml\Yaml;
use BackBee\ClassContent\Category;
use BackBee\Rest\Controller\ClassContentController;
use BackBee\Tests\BackBeeTestCase;

/**
 * Test for AclController class.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      eric.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBee\Rest\Controller\ClassContentController
 * @group Rest
 */
class ClassContentControllerTest extends BackBeeTestCase
{
    public function testGetCategory()
    {
        $categoryManager = self::$app->getContainer()->get('classcontent.category_manager');
        $controller = new ClassContentController(self::$app);

        // Test ClassContentController::getCategoryCollectionAction
        $expectedResponse = [];
        foreach ($categoryManager->getCategories() as $id => $category) {
            $expectedResponse[] = array_merge(['id' => $id], $category->jsonSerialize());
        }

        $allCategoriesResponse = $controller->getCategoryCollectionAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $allCategoriesResponse);
        $this->assertEquals(json_encode($expectedResponse), $allCategoriesResponse->getContent());

        // Test ClassContentController::getCategoryCollectionAction
        $expectedResponse = $categoryManager->getCategory('Demo');

        $categoryResponse = $controller->getCategoryAction('Demo');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $categoryResponse);
        $this->assertEquals(json_encode($expectedResponse), $categoryResponse->getContent());
    }

    /**
     * @expectedException        Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Classcontent's category `invalid` not found.
     */
    public function testGetInvalidCategory()
    {
        $controller = new ClassContentController(self::$app);
        $controller->getCategoryAction('invalid');
    }
}
