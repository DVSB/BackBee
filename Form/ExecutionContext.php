<?php

namespace BackBuilder\Form;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class ExecutionContext implements \Symfony\Component\Validator\ExecutionContextInterface
{
    /**
     * @var array
     */
    private $globalContext = array();

    /**
     * @var string
     */
    private $_key;

    /**
     * @var string
     */
    private $_value;

    /**
     * @var string
     */
    private $_message;

    /**
     * Creates a new execution context.
     */
    public function __construct(){}

    /**
     * {@inheritdoc}
     */
    public function addViolation($message, array $params = array(), $invalidValue = null, $pluralization = null, $code = null)
    {
        $report = new \stdClass();
        $report->message = $this->_message;
        $report->params = $params;
        $report->invalidKey = $this->_key;
        $report->invalidValue = $this->_value;
        $report->pluralization = $pluralization;
        $report->code = $code;

        $this->globalContext[] = $report;
    }

    public function addCustomViolation($message, $key)
    {
        $report = new \stdClass();
        $report->message = $message;
        $report->invalidKey = $key;
        $this->globalContext[] = $report;
    }

    public function setNext($key, $value, $message)
    {
        $this->_key = $key;
        $this->_value = $value;
        $this->_message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getViolations()
    {
        return $this->globalContext;
    }

    public function addViolationAt($subPath, $message, array $params = array(), $invalidValue = null, $pluralization = null, $code = null){}
    public function getRoot(){}
    public function getPropertyPath($subPath = ''){}
    public function getClassName(){}
    public function getPropertyName(){}
    public function getValue(){}
    public function getGroup(){}
    public function getMetadata(){}
    public function validate($value, $subPath = '', $groups = null, $traverse = false, $deep = false){}
    public function validateValue($value, $constraints, $subPath = '', $groups = null){}
    public function getMetadataFactory(){}
}
