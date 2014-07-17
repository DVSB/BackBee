<?php
namespace BackBuilder\Tests\Mock;

use BackBuilder\Config\Config;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Tests\Mock
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
