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

namespace BackBuilder\ClassContent\Indexes;

/**
 * Entity class for optimized content table sorted by modified
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @subpackage  Indexes
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\IndexationRepository")
 * @Table(
 *   name="opt_content_modified",
 *   indexes={
 *     @index(name="IDX_CLASSNAMEO", columns={"classname"}),
 *     @index(name="IDX_NODE", columns={"node_uid"}),
 *     @index(name="IDX_MODIFIEDO", columns={"modified"})
 *   }
 * )
 */
class OptContentByModified
{
    /**
     * @var string
     * @Id @Column(type="string", name="uid", length=32, nullable=false)
     */
    protected $_uid;

    /**
     * @var string
     * @Column(type="string", name="label", nullable=true)
     */
    protected $_label;

    /**
     * @var string
     * @Column(type="string", name="classname", nullable=false)
     */
    protected $_classname;

    /**
     * @var string
     * @Column(type="string", length=32, name="node_uid", nullable=false)
     */
    protected $_node_uid;

    /**
     * @var \DateTime
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * @var \DateTime
     * @Column(type="datetime", name="created")
     */
    protected $_created;
}
