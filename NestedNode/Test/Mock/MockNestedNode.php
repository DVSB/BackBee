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

namespace BackBuilder\NestedNode\Test\Mock;

use BackBuilder\NestedNode\ANestedNode;

/**
 * NestedNode mock
 * 
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\NestedNodeRepository")
 * @Table(name="nestednode")
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Test\Mock
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MockNestedNode extends ANestedNode
{

    /**
     * Unique identifier of the node
     * @var string
     * @Id @Column(type="string", name="uid")
     * 
     * @Serializer\Type("string")
     */
    protected $_uid;

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
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\Test\Mock\MockNestedNode
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Test\Mock\MockNestedNode", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestdNode\Test\Mock\MockNestedNode
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Test\Mock\MockNestedNode", inversedBy="_children", fetch="EXTRA_LAZY")
     * @JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

}
