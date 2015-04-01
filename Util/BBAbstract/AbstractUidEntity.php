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

namespace BackBee\Util\BBAbstract;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AbstractUidEntity implements DomainObjectInterface
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * @var boolean
     */
    private $_is_new = false;

    public function __construct($uid = null)
    {
        if (is_null($uid)) {
            $uid = md5(uniqid('', true));
            $this->_is_new = true;
        }

        $this->_uid = $uid;
    }

    public function cloneEntity()
    {
        $clone = $this;
        $clone->_uid = md5(uniqid('', true));
        $clone->_is_new = true;

        return $clone;
    }

    /**
     * return the unique identifier.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function isNew()
    {
        return $this->_is_new;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getObjectIdentifier()
    {
        return $this->_uid;
    }
}
