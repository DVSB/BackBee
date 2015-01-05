<?php

namespace BackBuilder\TestUnit\BackBuilder\Util;

use BackBuilder\Util\Arrays;

/**
 * @category    BackBuilder
 * @package     BackBuilder\TestUnit\BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class ArraysTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $_mock;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->_mock = array(
            'key' => array(
                'subkey' => array(
                    'subsubkey' => 'value',
                ),
            ),
        );
    }

    /**
     * @covers \BackBuilder\Util\Arrays::has
     */
    public function testHas()
    {
        $this->assertTrue(Arrays::has($this->_mock, 'key:subkey:subsubkey'));
        $this->assertFalse(Arrays::has($this->_mock, 'key:subkey:unknown'));
        $this->assertFalse(Arrays::has($this->_mock, 'key:subkey:subsubkey:unknown'));
        $this->assertTrue(Arrays::has($this->_mock, 'key::subkey::subsubkey', '::'));
        $this->assertFalse(Arrays::has($this->_mock, 'key:subkey:subsubkey', '::'));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::has
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testHasWithInvalidKey()
    {
        $this->assertTrue(Arrays::has($this->_mock, new \stdClass()));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::has
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testHasWithInvalidSeparator()
    {
        $this->assertTrue(Arrays::has($this->_mock, 'key', new \stdClass()));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::get
     */
    public function testGet()
    {
        $this->assertEquals('value', Arrays::get($this->_mock, 'key:subkey:subsubkey'));
        $this->assertNull(Arrays::get($this->_mock, 'key:subkey:unknown'));
        $this->assertNull(Arrays::get($this->_mock, 'key:subkey:subsubkey:unknown'));
        $this->assertEquals('default', Arrays::get($this->_mock, 'key:subkey:subsubkey:unknown', 'default'));
        $this->assertEquals('value', Arrays::get($this->_mock, 'key::subkey::subsubkey', null, '::'));
        $this->assertNull(Arrays::get($this->_mock, 'key:subkey:subsubkey', null, '::'));

        $result = array(
            'subkey' => array(
                'subsubkey' => 'value',
        ), );
        $this->assertEquals($result, Arrays::get($this->_mock, 'key'));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::get
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testGetWithInvalidKey()
    {
        $this->assertTrue(Arrays::get($this->_mock, new \stdClass()));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::get
     * @expectedException \BackBuilder\Exception\InvalidArgumentException
     */
    public function testGetWithInvalidSeparator()
    {
        $this->assertTrue(Arrays::get($this->_mock, 'key', null, new \stdClass()));
    }

    /**
     * @covers \BackBuilder\Util\Arrays::array_column
     */
    public function testArray_column()
    {
        $mock = array(
            'unused',
            array(
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ),
            array(
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ),
            array(
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ),
            array(
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            )
        );

        $this->assertEquals(array(
            array(
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ),
            array(
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ),
            array(
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ),
            array(
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            )
                ), Arrays::array_column($mock));
        $this->assertEquals(array('John', 'Sally', 'Jane', 'Peter'), Arrays::array_column($mock, 'first_name'));
        $this->assertEquals(array(2135 => 'John', 3245 => 'Sally', 5342 => 'Jane', 5623 => 'Peter'), Arrays::array_column($mock, 'first_name', 'id'));
        $this->assertEquals(array(
            2135 => array(
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ),
            3245 => array(
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ),
            5342 => array(
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ),
            5623 => array(
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            )
                ), Arrays::array_column($mock, null, 'id'));
    }

}
