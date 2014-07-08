<?php
namespace BackBuilder\Test\Mock;

use BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     Test\Unit\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockConfig extends Config implements IMock
{
    public function __construct()
    {
        $path = dirname(__DIR__);
        parent::__construct($path);
    }
}
