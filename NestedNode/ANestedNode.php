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

use BackBuilder\Util\Numeric,
    BackBuilder\Security\Acl\Domain\AObjectIdentifiable,
    BackBuilder\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Abstract class for nested node object in BackBuilder.
 *
 * A nested node is used to build nested tree.
 * Nested nodes are used by:
 * 
 * * \BackBuilder\NestedNode\Page        The page tree of a website
 * * \BackBuilder\NestedNode\Mediafolder The folder tree of the library
 * * \BackBuilder\NestedNode\KeyWord     The keywords trees
 * 
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @MappedSuperclass
 */
abstract class ANestedNode extends AObjectIdentifiable implements \Serializable
{

    /**
     * Unique identifier of the node
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\ANestedNode
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestedNode\ANestedNode
     */
    protected $_parent;

    /**
     * The nested node left position.
     * @var int
     * @Column(type="integer", name="leftnode", nullable=false)
     */
    protected $_leftnode;

    /**
     * The nested node right position.
     * @var int
     * @Column(type="integer", name="rightnode", nullable=false)
     */
    protected $_rightnode;

    /**
     * The nested node level in the tree.
     * @var int
     * @Column(type="integer", name="level", nullable=false)
     */
    protected $_level;

    /**
     * The creation datetime
     * @var \DateTime
     * @Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var \DateTime
     * @Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $_children;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the node
     * @param array $options Initial options for the node
     */
    public function __construct($uid = null, $options = null)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', true)) : $uid;
        $this->_leftnode = 1;
        $this->_rightnode = $this->_leftnode + 1;
        $this->_level = 0;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_root = $this;

        $this->_children = new ArrayCollection();
        $this->_descendants = new ArrayCollection();
    }

    /**
     * Returns te unique identifier.
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the root node.
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function getRoot()
    {
        return $this->_root;
    }

    /**
     * Returns the parent node, NULL if this node is root
     * @return \BackBuilder\NestedNode\ANestedNode|NULL
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Returns the nested node left position.
     * @return int
     */
    public function getLeftnode()
    {
        return $this->_leftnode;
    }

    /**
     * Returns the nested node right position.
     * @return int
     */
    public function getRightnode()
    {
        return $this->_rightnode;
    }

    /**
     * Returns the weight of the node, ie the number of descendants plus itself.
     * @return int
     */
    public function getWeight()
    {
        return $this->_rightnode - $this->_leftnode + 1;
    }

    /**
     * Returns the level of the node in the tree, 0 for root node
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * Returns the creation date.
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the last modified date.
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Returns a collection of descendant nodes
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDescendants()
    {
        return $this->_descendants;
    }

    /**
     * Returns a collection of direct children nodes
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren()
    {
        return $this->_children;
    }

    /**
     * Is the node is a root ?
     * @return Boolean TRUE if the node is root of tree, FALSE otherwise
     */
    public function isRoot()
    {
        return (1 === $this->_leftnode && null === $this->_parent);
    }

    /**
     * Is the node is a leaf ?
     * @return Boolean TRUE if the node if a leaf of tree, FALSE otherwise
     */
    public function isLeaf()
    {
        return (1 === ($this->_rightnode - $this->_leftnode));
    }

    /**
     * Is this node is an ancestor of the provided one ?
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param Boolean $strict Optional, if TRUE (default) this node is excluded of ancestors list
     * @return Boolean TRUE if this node is an anscestor or provided node, FALSE otherwise
     */
    public function isAncestorOf(ANestedNode $node, $strict = true)
    {
        if (true === $strict) {
            return (($node->getRoot() === $this->getRoot())
                    && ($node->getLeftnode() > $this->getLeftnode())
                    && ($node->getRightnode() < $this->getRightnode()));
        } else {
            return (($node->getRoot() === $this->getRoot())
                    && ($node->getLeftnode() >= $this->getLeftnode())
                    && ($node->getRightnode() <= $this->getRightnode()));
        }
    }

    /**
     * Is this node is a descendant of the provided one ?
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @param Boolean $strict Optional, if TRUE (default) this node is excluded of descendants list
     * @return Boolean TRUE if this node is a descendant or provided node, FALSE otherwise
     */
    public function isDescendantOf(ANestedNode $node, $strict = true)
    {
        if (true === $strict) {
            return (($this->getLeftnode() > $node->getLeftnode())
                    && ($this->getRightnode() < $node->getRightnode())
                    && ($this->getRoot() === $node->getRoot()));
        } else {
            return (($this->getLeftnode() >= $node->getLeftnode())
                    && ($this->getRightnode() <= $node->getRightnode())
                    && ($this->getRoot() === $node->getRoot()));
        }
    }

    /**
     * Sets the unique identifier of the node
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->_uid = $uid;
        return $this;
    }

    /**
     * Sets the root node.
     * @param \BackBuilder\NestedNode\ANestedNode $root
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setRoot(ANestedNode $root)
    {
        $this->_root = $root;
        return $this;
    }

    /**
     * Sets the parent node.
     * @param \BackBuilder\NestedNode\ANestedNode $parent
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setParent(ANestedNode $parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * Sets the left position.
     * @param int $leftnode
     * @return \BackBuilder\NestedNode\ANestedNode
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setLeftnode($leftnode)
    {
        if (false === Numeric::isPositiveInteger($leftnode)) {
            throw new InvalidArgumentException('A nested node position must be a strictly positive integer.');
        }

        $this->_leftnode = $leftnode;
        return $this;
    }

    /**
     * Sets the right position.
     * @param int $rightnode
     * @return \BackBuilder\NestedNode\ANestedNode
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setRightnode($rightnode)
    {
        if (false === Numeric::isPositiveInteger($rightnode)) {
            throw new InvalidArgumentException('A nested node position must be a strictly positive integer.');
        }

        $this->_rightnode = $rightnode;
        return $this;
    }

    /**
     * Sets the level.
     * @param type $level
     * @return \BackBuilder\NestedNode\ANestedNode
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the value can not be cast to positive integer
     */
    public function setLevel($level)
    {
        if (false === Numeric::isPositiveInteger($level, false)) {
            throw new InvalidArgumentException('A nested level must be a positive integer.');
        }

        $this->_level = $level;
        return $this;
    }

    /**
     * Sets the creation date
     * @param \DateTime $created
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setCreated(\DateTime $created)
    {
        $this->_created = $created;
        return $this;
    }

    /**
     * Sets the last modified date
     * @param \DateTime $modified
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }

    /**
     * Returns an array representation of the node
     * @return string
     */
    public function toArray()
    {
        return array(
            'id' => 'node_' . $this->getUid(),
            'rel' => (true === $this->isLeaf()) ? 'leaf' : 'folder',
            'uid' => $this->getUid(),
            'rootuid' => $this->getRoot()->getUid(),
            'parentuid' => (null !== $this->getParent()) ? $this->getParent()->getUid() : null,
            'created' => $this->getCreated()->getTimestamp(),
            'modified' => $this->getModified()->getTimestamp(),
            'isleaf' => $this->isLeaf()
        );
    }

    /**
     * Returns a string representation of node
     * @return string
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        foreach ($this->toArray() as $key => $value) {
            $serialized->$key = $value;
        }

        return json_encode($serialized);
    }

    /**
     * Constructs the node from a string or object
     * @param mixed $serialized The string representation of the object.
     * @return \BackBuilder\NestedNode\ANestedNode
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the serialized data can not be decode or, 
     *                                                         with strict mode, if a property does not exists
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized)) {
            if (null === $serialized = json_decode($serialized)) {
                throw new InvalidArgumentException('The serialized value can not be unserialized to node object.');
            }
        }

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;
            if (true === in_array($property, array('_created', '_modified'))) {
                continue;
            } else if (true === property_exists($this, $property)) {
                $this->$property = $value;
            } else if (true === $strict) {
                throw new InvalidArgumentException(sprintf('Unknown property `%s` in %s.', $property, ClassUtils::getRealClass($this)));
            }
        }

        return $this;
    }

    public function getTemplateName()
    {
        return str_replace(array("BackBuilder" . NAMESPACE_SEPARATOR . "NestedNode" . NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
    }
}