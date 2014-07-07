<?php
namespace BackBuilder\Test\Mock;

use BackBuilder\Renderer\ARenderer;

/**
 * @category    BackBuilder
 * @package     Test\Unit\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockRenderer extends ARenderer
{
    public $_scriptdir = array();
    public $_layoutdir = array();
    public $_helpers = array();
}
