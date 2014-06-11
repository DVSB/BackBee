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

namespace BackBuilder\Profiler;

use BackBuilder\BBApplication;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;

class Profiler extends \Symfony\Component\HttpKernel\Profiler\Profiler{

    /**
     * @param BBApplication $bbapp
     */
    public function __construct($bbapp, ProfilerStorageInterface $storage, LoggerInterface $logger = null){
        var_dump($bbapp->isDebugMode());
        die();

            if(true === $bbapp->isDebugMode()){
                parent::__construct($storage, $logger);
            }
        }

}