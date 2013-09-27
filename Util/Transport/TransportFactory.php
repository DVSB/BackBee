<?php

namespace BackBuilder\Util\Transport;

use BackBuilder\Util\Transport\Exception\TransportException;

class TransportFactory
{

    public static function create(array $config)
    {
        if (!array_key_exists('transport', $config))
            throw new TransportException(sprintf('Can not create Transport : missing classname.'));

        $classname = $config['transport'];
        if (!class_exists($classname))
            throw new TransportException(sprintf('Can not create Transport : unknown classname %s.', $classname));

        return new $classname($config);
    }

}