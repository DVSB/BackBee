<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Logging\Appender;

use BackBuilder\Logging\Formatter\IFormatter,
    BackBuilder\Logging\Formatter\Simple,
    BackBuilder\Logging\Exception\LoggingException;

/**
 * @category    BackBuilder
 * @package     BackBuilder/Logging
 * @subpackage  Appender
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class File implements IAppender
{

    private $_fhandler = NULL;
    private $_formatter = NULL;

    public function __construct($options)
    {
        if (!array_key_exists('logfile', $options))
            throw new LoggingException('None log file specified');

        $logfile = $options['logfile'];
        $dirname = dirname($logfile);
        $mode = array_key_exists('mode', $options) ? $options['mode'] : 'a';

        if ('' == $dirname || !is_dir($dirname)) {
            $r = new \ReflectionObject($this);
            $logfile = dirname($r->getFileName()) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
                    str_replace('/', DIRECTORY_SEPARATOR, $logfile);
            $dirname = dirname($logfile);
        }

        if (!is_dir($dirname) && !@mkdir($dirname, 0711, TRUE))
            throw new LoggingException(sprintf('Unable to create log directory `%s`.', $dirname));

        if (!$this->_fhandler = @fopen($logfile, $mode, false)) {
            throw new LoggingException(sprintf('Unable to open the file `%s` with mode `%s`.', $logfile, $mode));
        }

        $this->setFormatter(new Simple());
    }

    public function close()
    {
        if (is_resource($this->_fhandler))
            fclose($this->_fhandler);
    }

    public function setFormatter(IFormatter $formatter)
    {
        $this->_formatter = $formatter;
        return $this;
    }

    public function write($event)
    {
        $log = $this->_formatter->format($event);

        if (FALSE === @fwrite($this->_fhandler, $log))
            throw new LoggingException('Unable to write log entry.');
    }

}