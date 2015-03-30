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

namespace BackBee\Site\Metadata;

use BackBee\Installer\Annotation as BB;
use Doctrine\ORM\Mapping as ORM;

/**
 * A metadata entity.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      Nicolas BREMONT <nicolas.bremont@lp-digital.fr>
 * @ORM\Entity
 * @ORM\Table(name="metadata")
 * @BB\Fixtures(qty=20)
 */
class Metadata
{
    /**
     * The unique identifier.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     * @BB\Fixture(type="md5")
     */
    private $uid;

    /**
     * @ORM\Column(type="string")
     * @BB\Fixture(type="word")
     */
    private $attribute;

    /**
     * @ORM\Column(type="string", name="attr_value")
     * @BB\Fixture(type="word")
     */
    private $attrValue;

    /**
     * @ORM\Column(type="string")
     * @BB\Fixture(type="sentence", value=6)
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
     *
     * @return type
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param type $uid
     *
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param type $attribute
     *
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getAttrValue()
    {
        return $this->attrValue;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param type $attrValue
     *
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setAttrValue($attrValue)
    {
        $this->attrValue = $attrValue;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return type
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $content
     *
     * @return \BackBee\Site\Metadata\Metadata
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
}
