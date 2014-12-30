<?php

namespace BackBee\Security\Context;

use BackBee\Security\Listeners\ContextListener;

/**
 * Description of AnonymousContext
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class StatelessContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (!array_key_exists('stateless', $config) || false === $config['stateless']) {
            $contextKey = array_key_exists('context', $config) ? $config['context'] : $config['firewall_name'];
            $listeners[] = new ContextListener($this->_context, $this->_context->getUserProviders(), $contextKey, $this->_context->getLogger(), $this->_context->getDispatcher());
        }

        return $listeners;
    }
}
