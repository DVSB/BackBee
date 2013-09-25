<?php
namespace BackBuilder\ClassContent;

/**
 * Indexation entry for content
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\IndexationRepository")
 * @Table(name="indexation")
 */
class Indexation {
	/**
	 * The indexed content
	 * @var string
	 * @Id
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", inversedBy="_indexation")
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
	 */
    protected $_content;
    
    /**
	 * The indexed field of the content
	 * @var string
	 * @Id
	 * @Column(type="string", name="field")
	 */
    protected $_field;
    
	/**
	 * The owner content of the indexed field
	 * @var AClassContent
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent")
     * @JoinColumn(name="owner_uid", referencedColumnName="uid")
	 */
    protected $_owner;
    
    /**
	 * The value of the indexed field
	 * @var string
	 * @Column(type="string", name="value")
	 */
    protected $_value;
    
    /**
	 * The optional callback to apply while indexing
	 * @var string
	 * @Column(type="string", name="callback")
	 */
    protected $_callback;
    
    /**
     * Class constructor
     * @param AClassContent $content_uid  The unique identifier of the indexed content
     * @param string        $field		  The indexed field of the indexed content
     * @param AClassContent $owner_uid    The unique identifier of the owner content of the field
     * @param string        $value        The value of the indexed field
     * @param string        $callback     The optional callback to apply while indexing the value
     */
    public function __construct($content = NULL, $field = NULL, $owner = NULL, $value = NULL, $callback = NULL) {
        $this->setContent($content)
             ->setField($field)
             ->setOwner($owner)
             ->setValue($value)
             ->setCallback($callback);
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getField() {
        return $this->_field;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCallback() {
        return $this->_callback;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getValue() {
        return $this->_value;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getContent()
    {
        return $this->_content;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $content
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $field
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setField($field) {
        $this->_field = $field;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $owner
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setOwner($owner) {
        $this->_owner = $owner;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $value
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $callback
     * @return \BackBuilder\ClassContent\Indexation
     */
    public function setCallback($callback) {
        $this->_callback = $callback;
        return $this;
    }
}