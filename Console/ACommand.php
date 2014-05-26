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

namespace BackBuilder\Console;

use Symfony\Component\Console\Command\Command;

/**
 * Abstract Command
 *
 * @category    BackBuilder
 * @package     BackBuilder\Console
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ACommand extends Command
{
    
    /**
     * @var ContainerInterface|null
     */
    private $container;
    
    /**
     *
     * @var \BackBuilder\Bundle\ABundle
     */
    protected $bundle;

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $this->container = $this->getApplication()->getApplication()->getContainer();
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * 
     * @param \BackBuilder\Bundle\ABundle $bundle
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }
    
    /**
     * 
     * @return \BackBuilder\Bundle\ABundle
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
