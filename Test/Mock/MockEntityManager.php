<?php
namespace BackBuilder\Test\Mock;

use Faker\Factory;

/**
 * @category    BackBuilder
 * @package     Test\Unit\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockEntityManager extends \PHPUnit_Framework_TestCase implements IMock
{
    private $faker;

    private $stub_container = array();

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->seed(1337);
        $this->faker->addProvider(new \BackBuilder\Test\Fixtures\Provider\Theme($this->faker));
    }

    public function getRepository($namespace)
    {
        if (!array_key_exists($namespace, $this->stub_container)) {
            $exploded_namespace = explode('\\', $namespace);
            $this->stub_container[$namespace] = $this->{lcfirst(end($exploded_namespace)).'Stub'}($namespace);
        }
        return $this->stub_container[$namespace];
    }

    private function personalThemeEntityStub()
    {
        $theme = new \BackBuilder\Theme\PersonalThemeEntity($this->faker->themeEntity);

        $stub = $this->getMockBuilder('BackBuilder\Theme\Repository\ThemeRepository')->disableOriginalConstructor()->getMock();
        
        $stub->expects($this->any())
              ->method('retrieveBySiteUid')
              ->will($this->returnValue($theme));
        
        return $stub;
    }
}
