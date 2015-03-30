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

namespace BackBee\NestedNode;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\Renderer\RenderableInterface;

/**
 * A keywords entry of a tree in BackBee
 *
 * @category    BackBee
 * @package     BackBee\NestedNode
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 * @Entity(repositoryClass="BackBee\NestedNode\Repository\KeyWordRepository")
 * @Table(name="keyword",indexes={@index(name="IDX_ROOT", columns={"root_uid"}), @index(name="IDX_PARENT", columns={"parent_uid"}), @index(name="IDX_SELECT_KEYWORD", columns={"root_uid", "leftnode", "rightnode"}), @index(name="IDX_KEYWORD", columns={"keyword"})})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class KeyWord extends AbstractNestedNode implements RenderableInterface
{
    /**
     * Unique identifier of the content
     * @var string
     * @Id @Column(type="string", name="uid")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("id")
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBee\NestedNode\KeyWord
     * @ManyToOne(targetEntity="BackBee\NestedNode\KeyWord", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @JoinColumn(name="root_uid", referencedColumnName="uid", onDelete="SET NULL")
     * @Serializer\Exclude
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBee\NestedNode\KeyWord
     * @ManyToOne(targetEntity="BackBee\NestedNode\KeyWord", inversedBy="_children", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The keyword
     * @var string
     * @Column(type="string", name="keyword")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("keyword")
     * @Serializer\Type("string")
     */
    protected $_keyWord;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBee\NestedNode\KeyWord", mappedBy="_root", fetch="EXTRA_LAZY")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBee\NestedNode\KeyWord", mappedBy="_parent", fetch="EXTRA_LAZY")
     */
    protected $_children;

    /**
     * A collection of AbstractClassContent indexed by this keyword
     * @ManyToMany(targetEntity="BackBee\ClassContent\AbstractClassContent", fetch="EXTRA_LAZY")
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
    public function __construct($uid = null)
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
     * Returns a collection of indexed AbstractClassContent
     * @return Doctrine\Common\Collections\Collection
     * @codeCoverageIgnore
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Sets the keyword
     * @param  string                      $keyWord
     * @return \BackBee\NestedNode\KeyWord
     */
    public function setKeyWord($keyWord)
    {
        $this->_keyWord = $keyWord;

        return $this;
    }

    /**
     * Adds a content to the collection
     * @param  BackBee\ClassContent\AbstractClassContent $content
     * @return \BackBee\NestedNode\KeyWord
     */
    public function addContent(AbstractClassContent $content)
    {
        $this->_content->add($content);

        return $this;
    }

    /**
     * Removes a content from the collection
     * @param \BackBee\ClassContent\AbstractClassContent $content
     */
    public function removeContent(AbstractClassContent $content)
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
     * @param  string            $var
     * @return string|array|null
     */
    public function getData($var = null)
    {
        $data = $this->toArray();

        if (null !== $var) {
            if (false === array_key_exists($var, $data)) {
                return;
            }

            return $data[$var];
        }

        return $data;
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param  string            $var
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel(),
        );

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns TRUE if the page can be rendered.
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return true;
    }

    /**
     * Returns default template name
     * @return string
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return str_replace(array("BackBee".NAMESPACE_SEPARATOR."NestedNode".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
    }

    /**
     * Returns a stdObj representation of the node
     * @return \stdClass
     */
    public function toStdObject()
    {
        $object = new \stdClass();
        $object->uid = $this->getUid();
        $object->level = $this->getLevel();
        $object->keyword = $this->getKeyword();
        $object->children = array();

        return $object;
    }
}
