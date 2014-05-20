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

namespace BackBuilder\ClassContent;

/**
 * Indexation entry for content
 * 
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\IndexationRepository")
 * @Table(name="indexation",indexes={@index(name="IDX_OWNER", columns={"owner_uid"}), @index(name="IDX_CONTENT", columns={"content_uid"}), @index(name="IDX_VALUE", columns={"value"}), @index(name="IDX_SEARCH", columns={"field", "value"})})
 */
class Indexation
{

    /**
     * The indexed content
     * @var string
     * @Id
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", inversedBy="_indexation", fetch="EXTRA_LAZY")
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    protected $_content;

    /**
     * The indexed field of the content
     * @var string
     * @Id
     * @Column(type="string", name="field")
     */
    protected $_field;

    /**
     * The owner content of the indexed field
     * @var AClassContent
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", fetch="EXTRA_LAZY")
     * @JoinColumn(name="owner_uid", referencedColumnName="uid")
     */
    protected $_owner;

    /**
     * The value of the indexed field
     * @var string
     * @Column(type="string", name="value")
     */
    protected $_value;

    /**
     * The optional callback to apply while indexing
     * @var string
     * @Column(type="string", name="callback", nullable=true)
     */
    protected $_callback;

    /**
     * Class constructor
     * @param AClassContent $content_uid  The unique identifier of the indexed content
     * @param string        $field		  The indexed field of the indexed content
     * @param AClassContent $owner_uid    The unique identifier of the owner content of the field
     * @param string        $value        The value of the indexed field
     * @param string        $callback     The optional callback to apply while indexing the value
     */
    public function __construct($content = NULL, $field = NULL, $owner = NULL, $value = NULL, $callback = NULL)
    {
        $this->setContent($content)
                ->setField($field)
                ->setOwner($owner)
                ->setValue($value)
                ->setCallback($callback);
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * @codeCoverageIgnore
     * @return function
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * @codeCoverageIgnore
     * @param string $content
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param string $field
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setField($field)
    {
        $this->_field = $field;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface $owner
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setOwner($owner)
    {
        $this->_owner = $owner;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param string $value
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param function $callback
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setCallback($callback)
    {
        $this->_callback = $callback;
        return $this;
    }

}