<?php

namespace BackBuilder\NestedNode\Test\Mock;

use BackBuilder\NestedNode\ANestedNode;

/**
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\NestedNodeRepository")
 * @Table(name="nestednode")
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
