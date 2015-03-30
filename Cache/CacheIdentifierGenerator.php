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

namespace BackBee\Cache;

use BackBee\Cache\IdentifierAppender\IdentifierAppenderInterface;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Renderer\RendererInterface;

/**
 * CacheIdentifierGenerator allows you to easily customize cache identifier by adding appenders
 *
 * @category    BackBee
 * @package     BackBee\Cache
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class CacheIdentifierGenerator
{
    const APPENDER_SERVICE_TAG = 'cache.identifier.appender';

    /**
     * contains every declared appenders
     *
     * @var array
     */
    private $appenders;

    /**
     * constructor
     *
     * @param ContainerInterface $container service container from where we will retrieve every identifier appenders
     */
    public function __construct(ContainerInterface $container)
    {
        $this->appenders = array();
        foreach (array_keys($container->findTaggedServiceIds(self::APPENDER_SERVICE_TAG)) as $appender_id) {
            $this->addAppender($container->get($appender_id));
        }
    }

    /**
     * Add appender to current CacheIdentifierGenerator, sorted it by groups names
     *
     * @param IdentifierAppenderInterface $appender the appender to add
     */
    public function addAppender(IdentifierAppenderInterface $appender)
    {
        foreach ((array) $appender->getGroups() as $group_name) {
            if (false === array_key_exists($group_name, $this->appenders)) {
                $this->appenders[$group_name] = array();
            }

            $this->appenders[$group_name][] = $appender;
        }
    }

    /**
     * This method will compute cache identifier with every appenders that belong to group name
     *
     * @param string    $group_name the group name of appenders to apply
     * @param string    $identifier identifier we want to update
     * @param RendererInterface $renderer   the current renderer, can be null
     *
     * @return string the identifier new computed with appenders of group name
     */
    public function compute($group_name, $identifier, RendererInterface $renderer = null)
    {
        if (false === $this->isValidGroup($group_name)) {
            throw new InvalidArgumentException("$group_name is not a valid cache identifier appender group.");
        }

        foreach ($this->appenders[$group_name] as $appender) {
            $identifier = $appender->computeIdentifier($identifier, $renderer);
        }

        return $identifier;
    }

    /**
     * Define if provided group name is associated to any appenders or not
     *
     * @return boolean true if the provided group name is associated to one appender atleast, else false
     */
    public function isValidGroup($group_name)
    {
        return array_key_exists($group_name, $this->appenders);
    }
}
