<?php
namespace BackBuilder\Form;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class Validator
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $_validators;
    /**
     * @var \BackBuilder\Form\ExecutionContext
     */
    private $_context;
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $_request;

    public function __construct(\Symfony\Component\HttpFoundation\Request $request)
    {
        $this->_request = $request;
        $this->_context = new ExecutionContext();
        $this->_validators = new ContainerBuilder();
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->_context->getViolations();
    }

    public function addViolation($message, $key)
    {
        $this->_context->addCustomViolation($message, $key);
    }

    /**
     * @param string $key
     * @return \Symfony\Component\Validator\Constraint
     */
    public function getConstraint($key, $options)
    {
        $class = '\Symfony\Component\Validator\Constraints\\' . ucfirst($key);
        return new $class($options);
    }

    /**
     * @param string $key
     * @return \Symfony\Component\Validator\ConstraintValidator
     */
    public function getValidator($key)
    {
        if (!$this->_validators->has($key)) {
            $class = '\Symfony\Component\Validator\Constraints\\' . ucfirst($key) . 'Validator';
            $this->_validators->set($key, new $class());
            $this->_validators->get($key)->initialize($this->_context);
        }
        return $this->_validators->get($key);
    }

    /**
     *
     *
     * @param string $type    Validation type
     * @param string $content Content to validate
     * @param array  $options Constraint options
     * @param string $message Alternative message
     */
    public function validate($type, $name, $message = null, array $options = null)
    {
        $content = $this->_request->get($name);
        $validator = $this->getValidator($type);
        $constraint = $this->getConstraint($type, $options);
        $message = is_null($message) ? $validator->getMessageTemplate() : $message;
        $this->_context->setNext($name, $content, $message);
        $validator->validate($content, $constraint, $name);
    }

    /**
     * @return boolean
     */
    public function isValide()
    {
        if (count($this->getViolations()) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
