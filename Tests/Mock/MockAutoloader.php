<?php
namespace BackBuilder\Tests\Mock;

use BackBuilder\AutoLoader\AutoLoader;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Tests\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockAutoloader extends AutoLoader implements IMock
{
    private $_fakespaces = array();

    public function getListenerNamespace()
    {
        return $this->_fakespaces['BackBuilder\Event\Listener'];
    }

    public function registerListenerNamespace($path)
    {
        if (!isset($this->_fakespaces['BackBuilder\Event\Listener'])) {
            $this->_fakespaces['BackBuilder\Event\Listener'] = array();
        }
        array_unshift($this->_fakespaces['BackBuilder\Event\Listener'], $path);
    }
}
