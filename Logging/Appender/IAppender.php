<?php
namespace BackBuilder\Logging\Appender;

use BackBuilder\Logging\Formatter\IFormatter;

interface IAppender {
    public function setFormatter(IFormatter $formatter);
    public function write($event);
    public function close();
}