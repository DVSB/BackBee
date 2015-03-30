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

namespace BackBee\NestedNode\Tests\Mock;

use BackBee\NestedNode\AbstractNestedNode;
use BackBee\Tests\Mock\MockInterface;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * NestedNode mock.
 *
 * @ORM\Entity(repositoryClass="BackBee\NestedNode\Repository\NestedNodeRepository")
 * @ORM\Table(name="nestednode")
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class MockNestedNode extends AbstractNestedNode implements MockInterface
{
    /**
     * Unique identifier of the node.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     *
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The nested node left position.
     *
     * @var int
     * @ORM\Column(type="integer", name="leftnode", nullable=false)
     */
    protected $_leftnode;

    /**
     * The nested node right position.
     *
     * @var int
     * @ORM\Column(type="integer", name="rightnode", nullable=false)
     */
    protected $_rightnode;

    /**
     * The nested node level in the tree.
     *
     * @var int
     * @ORM\Column(type="integer", name="level", nullable=false)
     */
    protected $_level;

    /**
     * The creation datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * The root node, cannot be NULL.
     *
     * @var \BackBee\NestedNode\Tests\Mock\MockNestedNode
     * @ORM\ManyToOne(targetEntity="BackBee\NestedNode\Tests\Mock\MockNestedNode", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     *
     * @var \BackBee\NestdNode\Tests\Mock\MockNestedNode
     * @ORM\ManyToOne(targetEntity="BackBee\NestedNode\Tests\Mock\MockNestedNode", inversedBy="_children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;
}
