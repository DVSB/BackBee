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

namespace BackBee\Site\Metadata;

/**
 * A metadata entity
 *
 * @category    BackBee
 * @package     BackBee\Site
 * @subpackage  Metadata
 * @copyright   Lp digital system
 * @author      Nicolas BREMONT <nicolas.bremont@lp-digital.fr>
 * @Entity
 * @Table(name="metadata")
 * @fixtures(qty=20)
 */
class Metadata
{
    /**
     * The unique identifier
     * @Id
     * @Column(type="string")
     * @fixture(type="md5")
     */
    private $uid;

    /**
     * @Column(type="string")
     * @fixture(type="word")
     */
    private $attribute;

    /**
     * @Column(type="string", name="attr_value")
     * @fixture(type="word")
     */
    private $attrValue;

    /**
     * @Column(type="string")
     * @fixture(type="sentence", value=6)
     */
    private $content;

    public function __construct($uid = null, $attribute = null, $attrValue = null, $content = null)
    {
        $this->uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->attribute = $attribute;
        $this->attrValue = $attrValue;
        $this->content = $content;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                $uid
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                $attribute
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getAttrValue()
    {
        return $this->attrValue;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                $attrValue
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setAttrValue($attrValue)
    {
        $this->attrValue = $attrValue;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @codeCoverageIgnore
     * @param  string                              $content
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
}
