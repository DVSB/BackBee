<?php

namespace BackBuilder\Security\Context;

use BackBuilder\Security\Listeners\ContextListener;

/**
 * Description of AnonymousContext
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
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
        if (!array_key_exists('stateless', $config) || FALSE === $config['stateless']) {
            $contextKey = array_key_exists('context', $config) ? $config['context'] : $config['firewall_name'];
            $listeners[] = new ContextListener($this->_context, $this->_context->getUserProviders(), $contextKey, $this->_context->getLogger(), $this->_context->getDispatcher());
        }
        return $listeners;
    }
}
