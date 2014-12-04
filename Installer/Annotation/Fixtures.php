<?php
namespace BackBuilder\Installer\Annotation;

/**
 * @Annotation
 */
class Fixtures
{
    private $_qty;

    public function __construct(array $options = array())
    {
        $this->_qty = (isset($options["qty"])) ? $options["qty"] : 0;
    }

    public function __get($name)
    {
        if ($name === 'qty') {
            return $this->{'_'.$name};
        }

        return;
    }
}
