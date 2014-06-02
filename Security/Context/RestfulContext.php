<?php

namespace BackBuilder\Security\Context;

use BackBuilder\Security\Authentication\Provider\PublicKeyAuthenticationProvider,
    BackBuilder\Security\Listeners\PublicKeyAuthenticationListener;

/**
 * Restful Security Context
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class RestfulContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (array_key_exists('restful', $config)) {
            if(false !== ($default_provider = $this->getDefaultProvider($config))) {
                $this->_context->getAuthenticationManager()->addProvider(new PublicKeyAuthenticationProvider($default_provider, $this->_context->getEncoderFactory()));
                $listeners[] = new PublicKeyAuthenticationListener($this->_context, $this->_context->getAuthenticationManager(), $this->_context->getLogger());
            }
        }
        return $listeners;
    }
}
