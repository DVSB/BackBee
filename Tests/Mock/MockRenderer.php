<?php
namespace BackBee\Tests\Mock;

use BackBee\Renderer\ARenderer;

/**
 * @category    BackBee
 * @package     BackBee\Tests\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockRenderer extends ARenderer
{
    public $_scriptdir = array();
    public $_layoutdir = array();
    public $_helpers = array();
}
