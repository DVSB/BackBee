<?php

namespace BackBuilder\Security\Acl\Domain;

use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Abstract class providing methods implementing Object identity interfaces.
 * 
 * This abstract impose a getUid() method definition to classes extending it.
 * 
 * The main domain objects in Backbuilder application are :
 * 
 * * \BackBuilder\Site\Site
 * * \BackBuilder\Site\Layout
 * * \BackBuilder\Site\NestedNode
 * * \BackBuilder\Site\AclassContent
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security\Acl\Domain
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
abstract class AObjectIdentifiable implements IObjectIdentifiable
{

    /**
     * An abstract method to gets the unique id of the object
     */
    abstract public function getUid();

    /**
     * Returns a unique identifier for this domain object.
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->getType() . '(' . $this->getIdentifier() . ')';
    }

    /**
     * Returns the unique identifier for this object. 
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns the PHP class name of the object.
     * @return string
     */
    public function getType()
    {
        return ClassUtils::getRealClass($this);
    }

    /**
     * Checks for an explicit objects equality.
     * @param \BackBuilder\Security\Acl\Domain\IObjectIdentifiable $identity
     * @return Boolean
     */
    public function equals(IObjectIdentifiable $identity)
    {
        return ($this->getType() === $identity->getType()
                && $this->getIdentifier() === $identity->getIdentifier());
    }

}