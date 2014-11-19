<?php

namespace BackBuilder\Config\Tests\Persistor;

/**
 *
 */
class FakeContainerBuilder
{
    const MESSAGE = 'FakeContainerBuilder::removeContainerDump() called';

    /**
     * [$throw_exception description]
     * @var boolean
     */
    private $throw_exception;

    public function __construct($throw_exception = false)
    {
        $this->throw_exception = $throw_exception;
    }

    /**
     * {@inheritdoc}
     */
    public function removeContainerDump()
    {
        if ($this->throw_exception) {
            throw new \Exception(self::MESSAGE);
        }

        return true;
    }
}