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

namespace BackBuilder\NestedNode;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\NestedNode\ANestedNode,
    BackBuilder\Renderer\IRenderable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * A keywords entry of a tree in BackBuilder
 *
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\KeyWordRepository")
 * @Table(name="keyword",indexes={@index(name="keyword_idx", columns={"keyword"})})
 */
class KeyWord extends ANestedNode implements IRenderable
{

    /**
     * Unique identifier of the content
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\KeyWord
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\KeyWord", inversedBy="_descendants")
     * @JoinColumn(name="root_uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestedNode\KeyWord
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\KeyWord", inversedBy="_children", cascade={"persist"})
     * @JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The keyword
     * @var string
     * @Column(type="string", name="keyword")
     */
    protected $_keyWord;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\KeyWord", mappedBy="_root")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\KeyWord", mappedBy="_parent")
     */
    protected $_children;

    /**
     * A collection of AClassContent indexed by this keyword
     * @ManyToMany(targetEntity="BackBuilder\ClassContent\AClassContent")
     * @JoinTable(name="keywords_contents",
     *      joinColumns={@JoinColumn(name="keyword_uid", referencedColumnName="uid")},
     *      inverseJoinColumns={@JoinColumn(name="content_uid", referencedColumnName="uid")}
     *      )
     */
    protected $_content;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the keyword
     */
    public function __construct($uid = NULL)
    {
        parent::__construct($uid);

        $this->_content = new ArrayCollection();
    }

    /**
     * Returns the keyword
     * @return string
     */
    public function getKeyWord()
    {
        return $this->_keyWord;
    }

    /**
     * Returns a collection of indexed AClassContent
     * @return Doctrine\Common\Collections\Collection
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Sets the keyword
     * @param string $keyWord
     * @return \BackBuilder\NestedNode\KeyWord
     */
    public function setKeyWord($keyWord)
    {
        $this->_keyWord = $keyWord;
        return $this;
    }

    /**
     * Adds a content to the collection
     * @param BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\NestedNode\KeyWord
     */
    public function addContent(AClassContent $content)
    {
        $this->_content->add($content);
        return $this;
    }

    /**
     * Removes a content from the collection
     * @param \BackBuilder\ClassContent\AClassContent $content
     */
    public function removeContent(AClassContent $content)
    {
        $this->_content->removeElement($content);
    }

    /**
     * Returns an array representation of the keyword.
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['keyword'] = $this->getKeyWord();

        return $result;
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getData($var = null)
    {
        $data = $this->toArray();

        if (null !== $var) {
            if (false === array_key_exists($var, $data)) {
                return null;
            }

            return $data[$var];
        }

        return $data;
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getParam($var = NULL)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel()
        );

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return null;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns TRUE if the page can be rendered.
     * @return Boolean
     */
    public function isRenderable()
    {
        return true;
    }

}
