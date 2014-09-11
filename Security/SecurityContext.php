<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Security;

use BackBuilder\BBApplication;
use BackBuilder\Event\DispatcherProxy;
use BackBuilder\Routing\Matcher\RequestMatcher;
use BackBuilder\Security\Access\DecisionManager;
use BackBuilder\Security\Authentication\AuthenticationManager;
use BackBuilder\Security\Authentication\TrustResolver;
use BackBuilder\Security\Authorization\Adaptator\Yml;
use BackBuilder\Security\Authorization\Voter\BBRoleVoter;
use BackBuilder\Security\Authorization\Voter\SudoVoter;
use BackBuilder\Security\Context\ContextInterface;
use BackBuilder\Security\Exception\SecurityException;
use BackBuilder\Security\Listeners\LogoutListener;
use BackBuilder\Security\Role\RoleHierarchy;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\SecurityContext as sfSecurityContext;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;


/**
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class SecurityContext extends sfSecurityContext
{

    private $_application;
    private $_logger;
    private $_dispatcher;
    private $_firewall;
    private $_firewallmap;
    private $_authmanager;
    private $_authproviders;
    private $_userproviders;
    private $_aclprovider;
    private $_logout_listener;
    private $_config;
    private $_logout_listener_added = false;

    /**
     * An encoder factory
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    private $_encoderfactory;

    public function __construct(BBApplication $application, AuthenticationManagerInterface $authenticationManager, AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->_application = $application;
        $this->_logger = $this->_application->getLogging();
        $this->_dispatcher = $this->_application->getEventDispatcher();

        if (null === $securityConfig = $this->_application->getConfig()->getSecurityConfig()) {
            trigger_error('None security configuration found', E_USER_NOTICE);
            return;
        }
        $this->_config = $securityConfig;

        $this->_authmanager = $authenticationManager;

        if (null === $this->_authmanager) {
            $this->_authmanager = new AuthenticationManager(array());
            $this->_authmanager->setEventDispatcher($this->_dispatcher);
        }

        $this->_createEncoderFactory($securityConfig)
             ->createProviders($securityConfig)
             ->_createACLProvider($securityConfig)
             ->_createFirewallMap($securityConfig)
             ->_registerFirewall()
        ;

        if (null === $accessDecisionManager) {
            $trustResolver = new TrustResolver(
                'BackBuilder\Security\Token\AnonymousToken',
                'BackBuilder\Security\Token\RememberMeToken'
            );

            $voters = array();
            $voters[] = new SudoVoter($this->getApplication());
            $voters[] = new BBRoleVoter(new Yml($this->_application));
            $voters[] = new RoleVoter();
            $voters[] = new AuthenticatedVoter($trustResolver);

            if (null !== $this->_aclprovider) {
                $voters[] = new Authorization\Voter\BBAclVoter(
                    $this->_aclprovider,
                    new \Symfony\Component\Security\Acl\Domain\ObjectIdentityRetrievalStrategy(),
                    new \BackBuilder\Security\Acl\Domain\SecurityIdentityRetrievalStrategy(
                            new RoleHierarchy(array()),
                            $trustResolver
                    ),
                    new Acl\Permission\PermissionMap(),
                    $this->getApplication()->getLogging(),
                    false,
                    $this->getApplication()
                );
            }

            $accessDecisionManager = new DecisionManager($voters, 'affirmative', false, true);
        }

        parent::__construct($this->_authmanager, $accessDecisionManager);
    }

    /**
     * Create an encoders factory if need
     * @param array $config
     * @return \BackBuilder\Security\SecurityContext
     */
    public function _createEncoderFactory(array $config)
    {
        if (true === array_key_exists('encoders', $config)) {
            $this->_encoderfactory = new \Symfony\Component\Security\Core\Encoder\EncoderFactory($config['encoders']);
        }
        return $this;
    }

    /**
     * Returns the encoder factory or null if not defined
     * @return \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface|null
     */
    public function getEncoderFactory()
    {
        return $this->_encoderfactory;
    }

    public function createFirewall($name, $config)
    {
        $config['firewall_name'] = $name;
        $listeners = array();

        if (null === $this->_firewallmap) {
            return $this;
        }

        $requestMatcher = new RequestMatcher();

        if (array_key_exists('pattern', $config)) {
            $requestMatcher->matchPath($config['pattern']);
        }

        if (array_key_exists('requirements', $config)) {
            foreach ($config['requirements'] as $key => $value) {
                if (0 === strpos($key, 'HTTP-')) {
                    $requestMatcher->matchHeader(substr($key, 5), $value);
                }
            }
        }

        if (array_key_exists('security', $config) && false === $config['security']) {
            $this->_firewallmap->add($requestMatcher, array(), null);

            return $this;
        }

        $defaultProvider = reset($this->_userproviders);
        if (array_key_exists('provider', $config) && array_key_exists($config['provider'], $this->_userproviders)) {
            $defaultProvider = $this->_userproviders[$config['provider']];
        }

        if (array_key_exists('contexts', $this->_config)) {
            $listeners = $this->loadContexts($config);
        }

        if (null !== $this->_logout_listener && false === $this->_logout_listener_added) {
            $this->_application->getContainer()->set('security.logout_listener', $this->_logout_listener);
            if (false === ($this->_dispatcher instanceof DispatcherProxy) || !$this->_dispatcher->isRestored()) {
                $this->_dispatcher->addListener(
                    'frontcontroller.request.logout',
                    array('@security.logout_listener', 'handle')
                );
            }

            $this->_logout_listener_added = true;
        }

        if (0 == count($listeners)) {
            throw new SecurityException(sprintf('No authentication listener registered for firewall "%s".', $name));
        }

        $this->_firewallmap->add($requestMatcher, $listeners, null);

        return $this;
    }

    public function loadContexts($config)
    {
        $listeners = array();
        foreach ($this->_config['contexts'] as $namespace => $classnames) {
            foreach ($classnames as $classname) {
                $class = implode(NAMESPACE_SEPARATOR, array($namespace, $classname));
                $context = new $class($this);
                if ($context instanceof ContextInterface) {
                    $listeners = array_merge($listeners, $context->loadListeners($config));
                }
            }
        }

        return $listeners;
    }

    /**
     * @codeCoverageIgnore
     * @return \Symfony\Component\Security\Acl\Dbal\MutableAclProvider
     */
    public function getACLProvider()
    {
        return $this->_aclprovider;
    }

    private function _createFirewallMap($config)
    {
        $this->_firewallmap = new FirewallMap();

        if (false === array_key_exists('firewalls', $config)) {
            return $this;
        }

        $firewalls = (array) $config['firewalls'];
        foreach ($firewalls as $name => $firewall) {
            $this->createFirewall($name, $firewall);
        }

        return $this;
    }

    private function _createACLProvider($config)
    {
        if (true === array_key_exists('acl', $config)) {
            if (true === array_key_exists('connection', $config['acl']) && 'default' === $config['acl']['connection']) {
                if (false === $this->_application->getContainer()->has('security.acl_provider')) {
                    $this->_aclprovider = new \Symfony\Component\Security\Acl\Dbal\MutableAclProvider(
                        $this->getApplication()->getEntityManager()->getConnection(),
                        new \Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy(),
                        array(
                            'class_table_name'         => 'acl_classes',
                            'entry_table_name'         => 'acl_entries',
                            'oid_table_name'           => 'acl_object_identities',
                            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
                            'sid_table_name'           => 'acl_security_identities'
                    ));
                } else {
                    $this->_aclprovider = $this->_application->getContainer()->has('security.acl_provider');
                }

            }
        }

        return $this;
    }

    public function createProviders($config)
    {
        $this->_userproviders = array();

        if (false === array_key_exists('providers', $config)) {
            return $this;
        }

        $providers = (array) $config['providers'];
        foreach ($providers as $name => $provider) {
            $key = array_key_exists('secret', $provider) ? $provider['secret'] : 'bb4_secret_key';

            if (array_key_exists('entity', $provider)) {
                $manager = $this->_application->getEntityManager();
                if (null !== $manager) {
                    if (array_key_exists('manager_name', $provider['entity'])) {
                        $manager = $provider['entity']['manager_name']->getEntityManager();
                    }

                    if (array_key_exists('class', $provider['entity']) && array_key_exists('provider', $provider['entity'])) {
                        $providerClass = $provider['entity']['provider'];
                        $this->_userproviders[$name] = new $providerClass($manager->getRepository($provider['entity']['class']));
                    } elseif (array_key_exists('class', $provider['entity'])) {
                        $this->_userproviders[$name] = $manager->getRepository($provider['entity']['class']);
                    }
                }
            }

            if (array_key_exists('webservice', $provider)) {
                if (array_key_exists('class', $provider['webservice'])) {
                    $userprovider = $provider['webservice']['class'];
                    $this->_userproviders[$name] = new $userprovider($this->getApplication());
                }
            }
        }

        return $this;
    }

    public function addAuthProvider(AuthenticationProviderInterface $provider, $key = null)
    {
        if (is_null($key)) {
            $this->_authproviders[] = $provider;
        } else {
            $this->_authproviders[$key] = $provider;
        }
    }

    /**
     * Get auth provider
     *
     * @param string $key
     * @return AuthenticationProviderInterface
     * @throws InvalidArgumentException if provider not found
     */
    public function getAuthProvider($key)
    {
        if (array_key_exists($key, $this->_authproviders)) {
            return $this->_authproviders[$key];
        }

        throw \InvalidArgumentException(sprintf("Auth provider doesn't exists", $key));
    }

    /**
     * @codeCoverageIgnore
     * @param string $name
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $provider
     */
    public function addUserProvider($name, UserProviderInterface $provider)
    {
        $this->_userproviders[$name] = $provider;
    }

    /**
     * @codeCoverageIgnore
     * @param type $requestMatcher
     * @param type $listeners
     */
    public function addFirewall($requestMatcher, $listeners)
    {
        $this->_firewallmap->add($requestMatcher, $listeners, null);
    }

    /**
     * @codeCoverageIgnore
     */
    public function _registerFirewall()
    {
        $this->_firewall = new Firewall($this->_firewallmap, $this->_dispatcher);
        $this->_application->getContainer()->set('security.firewall', $this->_firewall);
        if (false === ($this->_dispatcher instanceof DispatcherProxy) || false === $this->_dispatcher->isRestored()) {
            $this->_dispatcher->addListener(
                'frontcontroller.request',
                array('@security.firewall', 'onKernelRequest')
            );
        }
    }

    /**
     * @codeCoverageIgnore
     * @return BBApplication
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getUserProviders()
    {
        return $this->_userproviders;
    }

    /**
     * @codeCoverageIgnore
     * @return AuthenticationManager
     */
    public function getAuthenticationManager()
    {
        return $this->_authmanager;
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    public function getLogoutListener()
    {
        return $this->_logout_listener;
    }

    public function setLogoutListener(LogoutListener $listener)
    {
        if (null === $this->_logout_listener) {
            $this->_logout_listener = $listener;
        }
    }

    public function getDispatcher()
    {
        return $this->_dispatcher;
    }
}
