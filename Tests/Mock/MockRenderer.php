<?php
namespace BackBuilder\Tests\Mock;

use BackBuilder\Renderer\ARenderer;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Tests\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockRenderer extends ARenderer
{
    public $_scriptdir = array();
    public $_layoutdir = array();
    public $_helpers = array();
}
