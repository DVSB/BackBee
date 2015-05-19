<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Security\Context;

use BackBee\Security\Authentication\Provider\BBAuthenticationProvider;
use BackBee\Security\Authentication\Provider\PublicKeyAuthenticationProvider;
use BackBee\Security\Listeners\LogoutListener;
use BackBee\Security\Listeners\PublicKeyAuthenticationListener;
use BackBee\Security\Logout\BBLogoutHandler;
use BackBee\Security\Logout\BBLogoutSuccessHandler;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Restful Security Context.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
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
            $config = array_merge([
                'nonce_dir' => 'security/nonces',
                'lifetime' => 1200,
                'use_registry' => false,
            ], (array) $config['restful']);

            if (false !== ($defaultProvider = $this->getDefaultProvider($config))) {

                $this->_context->getAuthenticationManager()
                    ->addProvider(
                        new PublicKeyAuthenticationProvider(
                            $defaultProvider,
                            $this->getNonceDirectory($config),
                            $config['lifetime'],
                            true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                            $this->_context->getEncoderFactory(),
                            $this->getApiUserRole()
                        )
                    )
                    ->addProvider(
                        $bbProvider = new BBAuthenticationProvider(
                            $defaultProvider,
                            $this->getNonceDirectory($config),
                            $config['lifetime'],
                            true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                            $this->_context->getEncoderFactory()
                        )
                    )
                ;

                $listeners[] = new PublicKeyAuthenticationListener(
                    $this->_context,
                    $this->_context->getAuthenticationManager(),
                    $this->_context->getLogger()
                );

                $this->loadLogoutListener($bbProvider);
            }
        }

        return $listeners;
    }

    /**
     * Gets the API user role from container
     * @return string
     */
    private function getApiUserRole()
    {
        $apiUserRole = null;

        $container = $this->_context->getApplication()->getContainer();
        if ($container->hasParameter('bbapp.securitycontext.role.apiuser')) {
            $apiUserRole = $container->getParameter('bbapp.securitycontext.role.apiuser');

            if ($container->hasParameter('bbapp.securitycontext.roles.prefix')) {
                $apiUserRole = $container->getParameter('bbapp.securitycontext.roles.prefix') . $apiUserRole;
            }
        }

        return $apiUserRole;
    }

    /**
     * Load LogoutListener into security context.
     *
     * @param AuthenticationProviderInterface $bbProvider
     */
    private function loadLogoutListener(AuthenticationProviderInterface $bbProvider)
    {
        if (null === $this->_context->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $this->_context->setLogoutListener(
                new LogoutListener($this->_context, $httpUtils, new BBLogoutSuccessHandler($httpUtils))
            );
        }

        $this->_context->getLogoutListener()->addHandler(new BBLogoutHandler($bbProvider));
    }

    /**
     * Returns the nonce directory path.
     *
     * @param array $config
     *
     * @return string the nonce directory path
     */
    private function getNonceDirectory(array $config)
    {
        return $this->_context->getApplication()->getCacheDir().DIRECTORY_SEPARATOR.$config['nonce_dir'];
    }

    /**
     * Returns the repository to Registry entities.
     *
     * @return \BackBuillder\Bundle\Registry\Repository
     */
    private function getRegistryRepository()
    {
        $repository = null;
        if (null !== $em = $this->_context->getApplication()->getEntityManager()) {
            $repository = $em->getRepository('BackBee\Bundle\Registry');
        }

        return $repository;
    }
}
