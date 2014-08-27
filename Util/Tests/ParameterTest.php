<?php

namespace bbUnit\Util;

use \BackBuilder\Util\Parameter;

class ParameterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers \BackBuilder\Util\Parameter::paramsReplaceRecursive
     */
    public function testParamsReplaceRecursive() {
        $array1 = array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e');
        $array2 = array('k6' => 'aaa', 'k2' => 'bbb', 'k3' => 'cccc', 'k7' => 'dddd', 'k5' => 'eee');
        $this->assertEquals(array('k1' => 'a', 'k2' => 'bbb', 'k3' => 'cccc', 'k4' => 'd', 'k5' => 'eee'), Parameter::paramsReplaceRecursive($array1, $array2));

        $this->assertNotEquals(array('k1' => 'aaa', 'k2' => 'bbb', 'k3' => 'cccc', 'k4' => 'd', 'k5' => 'eee'), Parameter::paramsReplaceRecursive($array1, $array2));

        $array1 = array('k1' => 'a', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e');
        $array2 = array('k1' => 'aaa', 'k2' => 'bbb', 'k3' => 'cccc', 'k7' => 'dddd', 'k5' => 'eee');
        $this->assertEquals(array('k1' => 'aaa', 'k2' => 'b', 'k3' => 'c', 'k4' => 'd', 'k5' => 'e'), Parameter::paramsReplaceRecursive($array1, $array2, array('k1')));

        $this->assertEquals(array(), Parameter::paramsReplaceRecursive(array(), array('k1' => 'aaa', 'k2' => 'bbb')));
        $this->assertEquals(array('k3' => 'aaa'), Parameter::paramsReplaceRecursive(array('k3' => 'aaa'), array('k1' => 'aaa', 'k2' => 'bbb')));
        $this->assertEquals(array('k3' => 'aaa'), Parameter::paramsReplaceRecursive(array('k3' => 'aaa'), array()));
        $this->assertEquals(array('k3' => 'aaa'), Parameter::paramsReplaceRecursive(array('k3' => 'aaa'), array('k1' => 'aaa', 'k2' => 'bbb')));
        $this->assertEquals(array('k3' => 'aaa'), Parameter::paramsReplaceRecursive(array('k3' => 'aaa'), array('k1' => 'aaa', 'k2' => 'bbb')));

        $this->assertEquals(array('k1' => array('k2' => 'bbb', 'k3' => 'ccc')), Parameter::paramsReplaceRecursive(array('k1' => array('k2' => 'bbb', 'k3' => 'ccc')), array('k1' => 'aaa', 'k2' => 'bbb')));
    }

}
