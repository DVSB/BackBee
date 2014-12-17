<?php

namespace BackBee\Security\Context;

use BackBee\Security\SecurityContext;

/**
 * Description of AbstractContext
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AbstractContext
{
    /**
     * @var SecurityContext
     */
    protected $_context;

    public function __construct(SecurityContext $context)
    {
        $this->_context = $context;
    }

    public function getDefaultProvider($config)
    {
        $user_provider = $this->_context->getUserProviders();
        $default_provider = reset($user_provider);
        if (array_key_exists('provider', $config) && array_key_exists($config['provider'], $this->_context->getUserProviders())) {
            $user_provider = $this->_context->getUserProviders();
            $default_provider = $user_provider[$config['provider']];
        }

        return $default_provider;
    }
}
