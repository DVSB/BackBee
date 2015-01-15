<?php

namespace BackBee\Rest\Tests\Controller;

use BackBee\ClassContent\Category;
use BackBee\Rest\Controller\ClassContentController;
use BackBee\Tests\TestCase;
use Symfony\Component\Yaml\Yaml;

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
