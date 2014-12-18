<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Event;

use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use BackBee\NestedNode\Page;

/**
 * Page Filter Event
 *
 * @category    BackBee
 * @package     BackBee\Event
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class PageFilterEvent extends KernelEvent
{
    /**
     *
     * @var Page
     */
    private $page;

    public function __construct(HttpKernelInterface $kernel, Request $request, $requestType, Page $page)
    {
        parent::__construct($kernel, $request, $requestType);
        $this->page = $page;
    }

    /**
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }
}
