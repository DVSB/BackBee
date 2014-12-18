<?php
namespace BackBee\Tests\Mock;

use BackBee\Config\Config;

/**
 * @category    BackBee
 * @package     BackBee\Tests\Mock
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
