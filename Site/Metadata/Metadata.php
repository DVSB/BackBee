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
class Metadata {
    
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
    
    
    function __construct($uid = NULL, $attribute = NULL, $attrValue = NULL, $content = NULL) {
        $this->uid          = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->attribute    = $attribute;
        $this->attrValue    = $attrValue;
        $this->content      = $content;
    }

    
    public function getUid() {
        return $this->uid;
    }

    public function setUid($uid) {
        $this->uid = $uid;
    }

    public function getAttribute() {
        return $this->attribute;
    }

    public function setAttribute($attribute) {
        $this->attribute = $attribute;
    }

    public function getAttrValue() {
        return $this->attrValue;
    }

    public function setAttrValue($attrValue) {
        $this->attrValue = $attrValue;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

}