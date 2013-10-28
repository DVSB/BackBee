<?php

namespace BackBuilder\Site\Metadata;

/**
 * A metadata entity
 *
 * @category    BackBuilder
 * @package     BackBuilder\Site\Metadata
 * @copyright   Lp digital system
 * @author      Nicolas BREMONT <nicolas.bremont@groupe-lp.com>
 * @Entity
 * @Table(name="metadata")
 */
class Metadata
{

    /**
     * The unique identifier
     * @Id
     * @Column(type="string")
     */
    private $uid;

    /**
     * 
     * @Column(type="string")
     */
    private $attribute;

    /**
     *
     * @Column(type="string", name="attr_value") 
     */
    private $attrValue;

    /**
     *
     * @Column(type="string")
     */
    private $content;

    function __construct($uid = NULL, $attribute = NULL, $attrValue = NULL, $content = NULL)
    {
        $this->uid = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->attribute = $attribute;
        $this->attrValue = $attrValue;
        $this->content = $content;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @codeCoverageIgnore
     * @param type $uid
     * @return \BackBuilder\Site\Metadata\Metadata
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @codeCoverageIgnore
     * @param type $attribute
     * @return \BackBuilder\Site\Metadata\Metadata
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getAttrValue()
    {
        return $this->attrValue;
    }

    /**
     * @codeCoverageIgnore
     * @param type $attrValue
     * @return \BackBuilder\Site\Metadata\Metadata
     */
    public function setAttrValue($attrValue)
    {
        $this->attrValue = $attrValue;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @codeCoverageIgnore
     * @param string $content
     * @return \BackBuilder\Site\Metadata\Metadata
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

}