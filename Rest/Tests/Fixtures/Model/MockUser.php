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

namespace BackBuilder\Rest\Tests\Fixtures\Model;

use Symfony\Component\Validator\Constraints as Assert;
use BackBuilder\Rest\Controller\Annotations as Rest;
use JMS\Serializer\Annotation as Serializer;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @Entity(repositoryClass="BackBuilder\Security\Repository\UserRepository")
 * @Table(name="user", uniqueConstraints={@UniqueConstraint(name="UNI_LOGIN",columns={"login"})})
 */
class MockUser
{
    /**
     * Unique identifier of the user
     * @var integer
     * @Id @Column(type="integer", name="id")
     *
     * @Serializer\Type('integer'))
     */
    public $_id = 1;

    /**
     * The login of this user
     * @var string
     * @Column(type="string", name="login")
     *
     * @Serializer\Type('string'))
     */
    public $_login = 'userLogin';

     /**
     * The password of this user
     * @var string
     * @Column(type="string", name="password")
     * @Serializer\Exclude()
     */
    public $_password = 'userPassword';
}
