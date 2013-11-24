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

namespace BackBuilder\Util;

use BackBuilder\BBApplication;
use Doctrine\ORM\EntityManager;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ObjectIdentityRetrieval
{

    private $_identifier;
    private $_class;
    private $_em;
    private static $_pattern = '/\((\w+),(.+)\)/';

    public function __construct(EntityManager $em, $identifier, $class)
    {
        $this->_em = $em;
        $this->_class = $class;
        $this->_identifier = $identifier;
    }

    public static function build(BBApplication $application, $objectIdentity)
    {
        $matches = array();
        preg_match(self::$_pattern, $objectIdentity, $matches);

        return new self($application->getEntityManager(), trim($matches[1]), trim($matches[2]));
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getObject()
    {
        return $this->_em->getRepository($this->_class)->find($this->_identifier);
    }

}