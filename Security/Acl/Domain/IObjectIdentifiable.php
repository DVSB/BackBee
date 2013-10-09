<?php

namespace BackBuilder\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * This methods should be implemented by objects to be stored in ACLs.
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security\Acl\Domain
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
interface IObjectIdentifiable extends DomainObjectInterface
{

//    /**
//     * Returns a unique identifier for this domain object.
//     * @return string
//     */
//    public function getObjectIdentifier();

    /**
     * Checks for an explicit objects equality.
     * @param \BackBuilder\Security\Acl\Domain\IObjectIdentifiable $identity
     * @return Boolean
     */
    public function equals(IObjectIdentifiable $identity);

    /**
     * Returns the unique identifier for this object. 
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns a type for the domain object. Typically, this is the PHP class name.
     * @return string cannot return null
     */
    public function getType();
}