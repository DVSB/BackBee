<?php
namespace BackBee\Tests\Mock;

use BackBee\AutoLoader\AutoLoader;

/**
 * @category    BackBee
 * @package     BackBee\Tests\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockAutoloader extends AutoLoader implements IMock
{
    private $_fakespaces = array();

    public function getListenerNamespace()
    {
        return $this->_fakespaces['BackBee\Event\Listener'];
    }

    public function registerListenerNamespace($path)
    {
        if (!isset($this->_fakespaces['BackBee\Event\Listener'])) {
            $this->_fakespaces['BackBee\Event\Listener'] = array();
        }
        array_unshift($this->_fakespaces['BackBee\Event\Listener'], $path);
    }
}
