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

use BackBee\Security\Authentication\Provider\PublicKeyAuthenticationProvider;
use BackBee\Security\Listeners\PublicKeyAuthenticationListener;

/**
 * Restful Security Context.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class RestfulContext extends BBAuthContext
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        $listeners = array();

        if (array_key_exists('restful', $config)) {
            $config = array_merge(array(
                'nonce_dir' => 'security/nonces',
                'lifetime' => 1200,
                'use_registry' => false,
            ), (array) $config['restful']);

            if (false !== ($default_provider = $this->getDefaultProvider($config))) {
                $this->_context->getAuthenticationManager()->addProvider(
                    new PublicKeyAuthenticationProvider(
                        $default_provider,
                        $this->getNonceDirectory($config),
                        $config['lifetime'],
                        true === $config['use_registry'] ? $this->getRegistryRepository() : null,
                        $this->_context->getEncoderFactory(),
                        $this->getApiUserRole()
                    )
                );

                $listeners[] = new PublicKeyAuthenticationListener(
                    $this->_context,
                    $this->_context->getAuthenticationManager(),
                    $this->_context->getLogger()
                );
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
}
