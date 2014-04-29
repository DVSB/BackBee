<?php

namespace BackBuilder\Bundle\Registry;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Bundle
 * @copyright   Lp digital system
 * @author e.chau <eric.chau@lp-digital.fr>
 *
 * @Table(name="registry", indexes={@index(name="IDX_KEY_SCOPE", columns={"key", "scope"})})
 */
class Registry
{
    /**
     * @var integer
     * 
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="`key`", type="string", length=255)
     */
    private $key;

    /**
     * @var string
     * 
     * @Column(name="`value`", type="text")
     */
    private $value;

    /**
     * @var string
     * 
     * @Column(name="`scope`", type="string", length=255)
     */
    private $scope;


    /**
     * Gets the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Sets the value of key.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Gets the value of value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the value of value.
     *
     * @param string $value the value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value of scope.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
    
    /**
     * Sets the value of scope.
     *
     * @param string $scope the scope
     *
     * @return self
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
