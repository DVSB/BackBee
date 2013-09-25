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
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function getViolations()
    {
        return $this->globalContext;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function addViolationAt($subPath, $message, array $params = array(), $invalidValue = null, $pluralization = null, $code = null){}
    /**
     * @codeCoverageIgnore
     */
    public function getRoot(){}
    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function getPropertyPath($subPath = ''){}
    /**
     * @codeCoverageIgnore
     */
    public function getClassName(){}
    /**
     * @codeCoverageIgnore
     */
    public function getPropertyName(){}
    /**
     * @codeCoverageIgnore
     */
    public function getValue(){}
    /**
     * @codeCoverageIgnore
     */
    public function getGroup(){}
    /**
     * @codeCoverageIgnore
     */
    public function getMetadata(){}
    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function validate($value, $subPath = '', $groups = null, $traverse = false, $deep = false){}
    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function validateValue($value, $constraints, $subPath = '', $groups = null){}
    /**
     * @codeCoverageIgnore
     */
    public function getMetadataFactory(){}
}
