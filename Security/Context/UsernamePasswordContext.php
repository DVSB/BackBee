<?php

namespace BackBuilder\Security\Context;

use BackBuilder\Security\Authentication\Provider\UserAuthenticationProvider;
use BackBuilder\Security\Listeners\UsernamePasswordAuthenticationListener;

/**
 * Description of AnonymousContext
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class UsernamePasswordContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (array_key_exists('form_login', $config)) {
            if (false !== ($default_provider = $this->getDefaultProvider($config))) {
                $login_path = array_key_exists('login_path', $config['form_login']) ? $config['form_login']['login_path'] : null;
                $check_path = array_key_exists('check_path', $config['form_login']) ? $config['form_login']['check_path'] : null;
                $this->_context->getAuthenticationManager()->addProvider(new UserAuthenticationProvider($default_provider, $this->_context->getEncoderFactory()));
                $listeners[] = new UsernamePasswordAuthenticationListener($this->_context, $this->_context->getAuthenticationManager(), $login_path, $check_path, $this->_context->getLogger());

                if (array_key_exists('rememberme', $config) && class_exists($config['rememberme'])) {
                    $classname = $config['rememberme'];
                    $listeners[] = new $classname($this->_context, $this->_context->getAuthenticationManager(), $this->_context->getLogger());
                }
            }
        }

        return $listeners;
    }
}
