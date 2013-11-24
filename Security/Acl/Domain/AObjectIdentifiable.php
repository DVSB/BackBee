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

namespace BackBuilder\Security\Acl\Domain;

use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Abstract class providing methods implementing Object identity interfaces.
 * 
 * This abstract impose a getUid() method definition to classes extending it.
 * 
 * The main domain objects in Backbuilder application are :
 * 
 * * \BackBuilder\Site\Site
 * * \BackBuilder\Site\Layout
 * * \BackBuilder\Site\NestedNode
 * * \BackBuilder\Site\AclassContent
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Acl\Domain
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class AObjectIdentifiable implements IObjectIdentifiable
{

    /**
     * An abstract method to gets the unique id of the object
     */
    abstract public function getUid();

    /**
     * Returns a unique identifier for this domain object.
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * Returns the unique identifier for this object. 
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns the PHP class name of the object.
     * @return string
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     * @param \BackBuilder\Security\Acl\Domain\IObjectIdentifiable $identity
     * @return Boolean
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType()
                && $this->getIdentifier() === $identity->getIdentifier());
    }

}