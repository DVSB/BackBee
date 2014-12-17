<?php

namespace BackBee\Security\Context;

use BackBee\Security\Listeners\AnonymousAuthenticationListener;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;

/**
 * Description of AnonymousContext
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class AnonymousContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (array_key_exists('anonymous', $config)) {
            $key = array_key_exists('key', (array) $config['anonymous']) ? $config['anonymous']['key'] : 'anom';
            $this->_context->addAuthProvider(new AnonymousAuthenticationProvider($key));
            $listeners[] = new AnonymousAuthenticationListener($this->_context, $key, $this->_context->getLogger());
        }

        return $listeners;
    }
}
