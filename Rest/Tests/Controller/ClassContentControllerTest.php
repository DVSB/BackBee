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
use BackBee\Tests\TestCase;

class ClassContentControllerTest extends TestCase
{
    public function testGetCategory()
    {
        $app = $this->getApplication();
        file_put_contents(
            $app->getRepository().DIRECTORY_SEPARATOR.'ClassContent'.DIRECTORY_SEPARATOR.'test.yml',
            Yaml::dump([
                'test' => [
                    'properties' => [
                        'name'        => 'Test content',
                        'description' => 'ClassContentController test content description',
                        'category'    => ['Test'],
                    ],
                ],
            ])
        );

        $categoryManager = $app->getContainer()->get('classcontent.category_manager');
        $controller = new ClassContentController($app);

        // Test ClassContentController::getCategoryCollectionAction
        $expectedResponse = [];
        foreach ($categoryManager->getCategories() as $id => $category) {
            $expectedResponse[] = array_merge(['id' => $id], $category->jsonSerialize());
        }

        $allCategoriesResponse = $controller->getCategoryCollectionAction();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $allCategoriesResponse);
        $this->assertEquals(json_encode($expectedResponse), $allCategoriesResponse->getContent());

        // Test ClassContentController::getCategoryCollectionAction
        $expectedResponse = $categoryManager->getCategory('test');

        $categoryResponse = $controller->getCategoryAction('test');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $categoryResponse);
        $this->assertEquals(json_encode($expectedResponse), $categoryResponse->getContent());
    }

    /**
     * @expectedException        Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Classcontent's category `invalid` not found.
     */
    public function testGetInvalidCategory()
    {
        $controller = new ClassContentController($this->getApplication());
        $controller->getCategoryAction('invalid');
    }
}
