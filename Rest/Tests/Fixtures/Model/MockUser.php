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

namespace BackBee\Rest\Tests\Fixtures\Model;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use BackBee\Rest\Controller\Annotations as Rest;

use Doctrine\ORM\Mapping as ORM;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 *
 * @ORM\Entity(repositoryClass="BackBee\Security\Repository\UserRepository")
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="UNI_LOGIN",columns={"login"})})
 */
class MockUser
{
    /**
     * Unique identifier of the user.
     *
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     *
     * @Serializer\Type("integer"))
     */
    public $_id = 1;

    /**
     * The login of this user.
     *
     * @var string
     * @ORM\Column(type="string", name="login")
     *
     * @Serializer\Type("string"))
     */
    public $_login = 'userLogin';

    /**
     * The password of this user.
     *
     * @var string
     * @ORM\Column(type="string", name="password")
     * @Serializer\Exclude()
     */
    public $_password = 'userPassword';
}
