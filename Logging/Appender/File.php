<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Logging\Appender;

use BackBee\Logging\Exception\LoggingException;
use BackBee\Logging\Formatter\FormatterInterface;
use BackBee\Logging\Formatter\Simple;

/**
 * @category    BackBee
 * @package     BackBee/Logging
 * @subpackage  Appender
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class File implements AppenderInterface
{
    private $_fhandler = null;
    private $_formatter = null;

    public function __construct($options)
    {
        if (!array_key_exists('logfile', $options)) {
            throw new LoggingException('None log file specified');
        }

        $logfile = $options['logfile'];
        $dirname = dirname($logfile);
        $mode = array_key_exists('mode', $options) ? $options['mode'] : 'a';

        if (!is_dir($dirname) && !@mkdir($dirname, 0711, true)) {
            throw new LoggingException(sprintf('Unable to create log directory `%s`.', $dirname));
        }

        if (!$this->_fhandler = @fopen($logfile, $mode, false)) {
            throw new LoggingException(sprintf('Unable to open the file `%s` with mode `%s`.', $logfile, $mode));
        }

        $this->setFormatter(new Simple());
    }

    public function close()
    {
        if (is_resource($this->_fhandler)) {
            fclose($this->_fhandler);
        }
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        $this->_formatter = $formatter;

        return $this;
    }

    public function write($event)
    {
        if (is_resource($this->_fhandler)) {
            $log = $this->_formatter->format($event);

            if (false === @fwrite($this->_fhandler, $log)) {
                throw new LoggingException('Unable to write log entry.');
            }
        }
    }
}
