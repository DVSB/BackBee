<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace bbUnit\Util;

use BackBee\Util\Parameter;

class ParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \BackBee\Util\Parameter::paramsReplaceRecursive
     */
    public function testParamsReplaceRecursive()
    {
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
