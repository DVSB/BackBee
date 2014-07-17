<?php
namespace BackBuilder\Config\Tests;

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

use BackBuilder\Config\Config;
use BackBuilder\Config\ConfigProxy;
use BackBuilder\DependencyInjection\Container;

/**
 * Set of tests for BackBuilder\Config\ConfigProxy
 *
 * @category    BackBuilder
 * @package     BackBuilder\Config
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 *
 * @coversDefaultClass \BackBuilder\Config\ConfigProxy
 */
class ConfigProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test restore of ConfigProxy
     *
     * @covers ConfigProxy::__construct
     * @covers ConfigProxy::restore
     * @covers ConfigProxy::isRestored
     */
    public function testConfigProxy()
    {
        // prepare variables we need to perform tests on Config\ConfigProxy
        $container = new Container();
        $config = new Config(__DIR__ . '/ConfigTest_Resources');
        $config_dump = $config->dump();

        // set of tests on Config\ConfigProxy
        $config_proxy = new ConfigProxy();
        $this->assertFalse($config_proxy->isRestored());
        $config_proxy->restore($container, $config_dump);
        $this->assertTrue($config_proxy->isRestored());
        $this->assertEquals($config_dump, $config_proxy->dump());
    }
}
