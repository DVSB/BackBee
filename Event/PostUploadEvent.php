<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
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

namespace BackBee\Event;

/**
 * A event dispatch after a file was uploaded in BB application.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PostUploadEvent extends Event
{
    /**
     * Class constructor.
     *
     * @param string $source_file Path to the uploaded file
     * @param string $target_file Target path to copy uploaded file
     */
    public function __construct($source_file, $target_file = null)
    {
        parent::__construct($source_file, $target_file);
    }

    /**
     * Returns the path to the uploaded file.
     *
     * @return string
     */
    public function getSourceFile()
    {
        return $this->_target;
    }

    /**
     * Returns the target path if exists, NULL otherwise.
     *
     * @return string|NULL
     */
    public function getTargetFile()
    {
        return $this->_args;
    }

    /**
     * Is the source file is a valid readable file ?
     *
     * @return boolean
     */
    public function hasValidSourceFile()
    {
        $sourcefile = $this->getSourceFile();

        return (true === is_readable($sourcefile) && false === is_dir($sourcefile));
    }
}
