<?php

namespace BackBee\TestUnit\BackBee\Util;

use BackBee\Util\ArrayPaginator;

/**
 * @category    BackBee
 * @package     BackBee\TestUnit\BackBee\Util
 * @copyright   Lp digital system
 * @author      Flavia Fodor
 */
class ArrayPaginatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @covers \BackBee\Util\ArrayPaginator::getIterator
     */
    public function testGetIterator()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $it = $obj->getIterator();

        $i = 0;
        foreach ($it as $key => $var) {
            $this->assertEquals('c', $var);
            $this->assertEquals('k3', $key);
            $i++;
        }
        $this->assertEquals(1, $i);
    }

    /**
     *
     * @covers \BackBee\Util\ArrayPaginator::count
     */
    public function testCount()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(5, $obj->count());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 1, 5);
        $this->assertEquals(1, $obj->count());

        $obj = ArrayPaginator::paginate(array(), 1, 5);
        $this->assertEquals(0, $obj->count());
    }

    /**
     * @covers \BackBee\Util\ArrayPaginator::getNextPageNumber
     */
    public function testGetNextPageNumber()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(3, $obj->getNextPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 1, 5);
        $this->assertEquals(0, $obj->getNextPageNumber());

        $obj = ArrayPaginator::paginate(array(), 1, 5);
        $this->assertEquals(-1, $obj->getNextPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 4);
        $this->assertEquals(1, $obj->getNextPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 30, 4);
        $this->assertEquals(1, $obj->getNextPageNumber());
    }

    /**
     * @covers \BackBee\Util\ArrayPaginator::getPreviousPageNumber
     */
    public function testGetPreviousPageNumber()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(1, $obj->getPreviousPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 1, 5);
        $this->assertEquals(0, $obj->getPreviousPageNumber());

        $obj = ArrayPaginator::paginate(array(), 1, 5);
        $this->assertEquals(0, $obj->getPreviousPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 4);
        $this->assertEquals(1, $obj->getPreviousPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 30, 4);
        $this->assertEquals(29, $obj->getPreviousPageNumber());
    }

    /**
     * @covers \BackBee\Util\ArrayPaginator::isNextPage
     */
    public function testIsNextPage()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(true, $obj->isNextPage());

        $obj = ArrayPaginator::paginate(array(), 2, 1);
        $this->assertEquals(false, $obj->isNextPage());

        $obj = ArrayPaginator::paginate(array(), 1, 5);
        $this->assertEquals(false, $obj->isNextPage());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 4);
        $this->assertEquals(false, $obj->isNextPage());
    }

    /**
     * @covers \BackBee\Util\ArrayPaginator::isPreviousPage
     */
    public function testIsPreviousPage()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(true, $obj->isPreviousPage());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 0, 4);
        $this->assertEquals(false, $obj->isPreviousPage());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 3, 4);
        $this->assertEquals(true, $obj->isPreviousPage());
    }

    /**
     * @covers \BackBee\Util\ArrayPaginator::getCurrentPageNumber
     */
    public function testGetCurrentPageNumber()
    {
        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 2, 1);
        $this->assertEquals(2, $obj->getCurrentPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), 1, 5);
        $this->assertEquals(1, $obj->getCurrentPageNumber());

        $obj = ArrayPaginator::paginate(array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), -1, 5);
        $this->assertEquals(-1, $obj->getCurrentPageNumber());
    }
}
