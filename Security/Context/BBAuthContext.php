<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Security\Context;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Listeners\BBAuthenticationListener;
use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\Logout\BBLogoutHandler;
use BackBee\Security\Logout\BBLogoutSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Description of AnonymousContext
 *
 * @category    BackBee
 * @package     BackBee\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>, e.chau <eric.chau@lp-digital.fr>
 */
class BBAuthContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();
        if (array_key_exists('bb_auth', $config)) {
            $config = array_merge(array(
                'nonce_dir' => 'security/nonces',
                'lifetime' => 1200,
                'use_registry' => false,
            ), $config['bb_auth']);

            if (false !== ($default_provider = $this->getDefaultProvider($config))) {
                $bb_provider = new BBAuthenticationProvider(
                    $default_provider,
                    $this->getNonceDirectory($config),
                    $config['lifetime'],
                    (true === $config['use_registry']) ? $this->getRegistryRepository() : null,
                    $this->_context->getEncoderFactory()
                );

                $this->_context->addAuthProvider($bb_provider, 'bb_auth');
                $this->_context->getAuthenticationManager()->addProvider($bb_provider);
                $listeners[] = new BBAuthenticationListener(
                    $this->_context,
                    $this->_context->getAuthenticationManager(),
                    $this->_context->getLogger()
                );

                $this->loadLogoutListener($bb_provider);
            }
        }

        return $listeners;
    }

    /**
     * Load LogoutListener into security context
     *
     * @param AuthenticationProviderInterface $bb_provider
     */
    protected function loadLogoutListener(AuthenticationProviderInterface $bb_provider)
    {
        if (null === $this->_context->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $logout_listener = new LogoutListener($this->_context, $httpUtils, new BBLogoutSuccessHandler($httpUtils));
            $this->_context->setLogoutListener($logout_listener);
        }

        $this->_context->getLogoutListener()->addHandler(new BBLogoutHandler($bb_provider));
    }

    /**
     * Returns the nonce directory path
     *
     * @param array $config
     *
     * @return string the nonce directory path
     */
    protected function getNonceDirectory(array $config)
    {
        return $this->_context->getApplication()->getCacheDir().DIRECTORY_SEPARATOR.$config['nonce_dir'];
    }

    /**
     * Returns the repository to Registry entities
     *
     * @return \BackBuillder\Bundle\Registry\Repository
     */
    protected function getRegistryRepository()
    {
        $repository = null;
        if (null !== $em = $this->_context->getApplication()->getEntityManager()) {
            $repository = $em->getRepository('BackBee\Bundle\Registry');
        }

        return $repository;
    }
}
