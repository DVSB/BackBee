<?php

namespace BackBuilder\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use BackBuilder\Security\Listeners\UsernamePasswordAuthenticationListener;
use BackBuilder\Security\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Http\HttpUtils;
use BackBuilder\Security\Listeners\LogoutListener;
use BackBuilder\Security\Exception\SecurityException,
    BackBuilder\Security\Listeners\BBAuthenticationListener,
    BackBuilder\Security\Listeners\AnonymousAuthenticationListener,
    BackBuilder\Security\Authentication\AuthenticationManager,
    BackBuilder\Security\Authentication\Provider\BBAuthenticationProvider,
    BackBuilder\Security\Listeners\ContextListener,
    BackBuilder\Security\Logout\LogoutSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\FirewallMap;
use BackBuilder\Routing\Matcher\RequestMatcher;
use BackBuilder\BBApplication,
    BackBuilder\Security\Authentication\TrustResolver,
    BackBuilder\Security\Access\DecisionManager,
    BackBuilder\Security\Role\RoleHierarchy,
    BackBuilder\Security\Authorization\Voter\SudoVoter,
    BackBuilder\Security\Authorization\Voter\BBRoleVoter,
    BackBuilder\Security\Authorization\Adaptator\Yml;
use Symfony\Component\Security\Core\SecurityContext as sfSecurityContext,
    Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface,
    Symfony\Component\Security\Core\Authorization\Voter\RoleVoter,
    Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter,
    Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

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

    public function __construct(BBApplication $application, AuthenticationManagerInterface $authenticationManager = NULL, AccessDecisionManagerInterface $accessDecisionManager = NULL)
    {
        $this->_application = $application;

        if (NULL !== $this->_application) {
            $this->_logger = $this->_application->getLogging();
            $this->_dispatcher = $this->_application->getEventDispatcher();
        }

        if (NULL === $securityConfig = $this->_application->getConfig()->getSecurityConfig()) {
            trigger_error('None security configuration found', E_USER_NOTICE);
            return;
        }

        $this->_authmanager = $authenticationManager;

        if (NULL === $this->_authmanager) {
            $this->_authmanager = new AuthenticationManager(array());
            $this->_authmanager->setEventDispatcher($this->_dispatcher);
        }

        $this->createProviders($securityConfig)
                ->_createACLProvider($securityConfig)
                ->_createFirewallMap($securityConfig)
                ->_registerFirewall();

        if (NULL === $accessDecisionManager) {
            $trustResolver = new TrustResolver('BackBuilder\Security\Token\AnonymousToken', 'BackBuilder\Security\Token\RememberMeToken');

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
//                $voters[] = new RoleVoter();
            }

            $accessDecisionManager = new DecisionManager($voters, 'affirmative', false, true);
        }

        parent::__construct($this->_authmanager, $accessDecisionManager);
    }

    public function createFirewall($name, $config)
    {
        if (NULL === $this->_firewallmap)
            return $this;

        $requestMatcher = new RequestMatcher();

        if (array_key_exists('pattern', $config))
            $requestMatcher->matchPath($config['pattern']);

        if (array_key_exists('requirements', $config)) {
            foreach ($config['requirements'] as $key => $value) {
                if (0 === strpos($key, 'HTTP-'))
                    $requestMatcher->matchHeader(substr($key, 5), $value);
            }
        }

        if (array_key_exists('security', $config) && FALSE === $config['security']) {
            $this->_firewallmap->add($requestMatcher, array(), NULL);
            return $this;
        }

        $defaultProvider = reset($this->_userproviders);
        if (array_key_exists('provider', $config) && array_key_exists($config['provider'], $this->_userproviders))
            $defaultProvider = $this->_userproviders[$config['provider']];

        $listeners = array();

        if (!array_key_exists('stateless', $config) || FALSE === $config['stateless']) {
            $contextKey = array_key_exists('context', $config) ? $config['context'] : $name;
            $listener = new ContextListener($this, $this->_userproviders, $contextKey, $this->_logger, $this->_dispatcher);
            $listeners[] = $listener;
        }

        if (array_key_exists('anonymous', $config)) {
            $key = array_key_exists('key', (array) $config['anonymous']) ? $config['anonymous']['key'] : 'anom';
            $this->_authproviders[] = new AnonymousAuthenticationProvider($key);
            $listener = new AnonymousAuthenticationListener($this, $key, $this->_logger);
            $listeners[] = $listener;
        }

        if (array_key_exists('bb_auth', $config)) {
            $config = array_merge(array('nonce_dir' => 'security/nonces', 'lifetime' => 1200), $config);
            $this->_authproviders['bb_auth'] = new BBAuthenticationProvider($defaultProvider, $this->_application->getCacheDir() . DIRECTORY_SEPARATOR . $config['nonce_dir'], $config['lifetime']);
            $this->_authmanager->addProvider($this->_authproviders['bb_auth']);
            $listener = new BBAuthenticationListener($this, $this->_authmanager, $this->_logger);
            $listeners[] = $listener;

            if (null === $this->_logout_listener) {
                $this->_logout_listener = new LogoutListener($this, $httpUtils = new HttpUtils(), new Logout\BBLogoutSuccessHandler($httpUtils));
            }

            $this->_logout_listener->addHandler(new Logout\BBLogoutHandler($this->_authproviders['bb_auth']));
        }

        if (array_key_exists('form_login', $config)) {
            $login_path = array_key_exists('login_path', $config['form_login']) ? $config['form_login']['login_path'] : null;
            $check_path = array_key_exists('check_path', $config['form_login']) ? $config['form_login']['check_path'] : null;
            $this->_authmanager->addProvider(new UserAuthenticationProvider($defaultProvider));
            $listener = new UsernamePasswordAuthenticationListener($this, $this->_authmanager, $login_path, $check_path, $this->_logger);
            $listeners[] = $listener;

            if (array_key_exists('rememberme', $config) && class_exists($config['rememberme'])) {
                $classname = $config['rememberme'];
                $listener = new $classname($this, $this->_authmanager, $this->_logger);
                $listeners[] = $listener;
            }
        }

        if (array_key_exists('logout', $config)) {
            if (array_key_exists('handlers', $config['logout']) && is_array($handlers = $config['logout']['handlers'])) {
                if (null === $this->_logout_listener) {
                    $this->_logout_listener = new LogoutListener($this, $httpUtils = new HttpUtils(), new LogoutSuccessHandler($httpUtils));
                }

                foreach ($handlers as $handler) {
                    $this->_logout_listener->addHandler(new $handler());
                }
            }
        }

        if (null !== $this->_logout_listener) {
            $this->_dispatcher->addListener('frontcontroller.request.logout', array($this->_logout_listener, 'handle'));
        }

        if (0 == count($listeners))
            throw new SecurityException(sprintf('No authentication listener registered for firewall "%s".', $name));

        $this->_firewallmap->add($requestMatcher, $listeners, NULL);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getACLProvider()
    {
        return $this->_aclprovider;
    }

    private function _createFirewallMap($config)
    {
        $this->_firewallmap = new FirewallMap();

        if (FALSE === array_key_exists('firewalls', $config))
            return $this;

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
                $this->_aclprovider = new \Symfony\Component\Security\Acl\Dbal\MutableAclProvider(
                                $this->getApplication()->getEntityManager()->getConnection(),
                                new \Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy(),
                                array(
                                    'class_table_name' => 'acl_classes',
                                    'entry_table_name' => 'acl_entries',
                                    'oid_table_name' => 'acl_object_identities',
                                    'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
                                    'sid_table_name' => 'acl_security_identities'
                        ));
            }
        }

        return $this;
    }

    public function createProviders($config)
    {
        $this->_userproviders = array();

        if (FALSE === array_key_exists('providers', $config))
            return $this;

        $providers = (array) $config['providers'];
        foreach ($providers as $name => $provider) {
            $key = array_key_exists('secret', $provider) ? $provider['secret'] : 'bb4_secret_key';

            if (array_key_exists('entity', $provider)) {
                if (array_key_exists('class', $provider['entity']))
                    $this->_userproviders[$name] = $this->_application->getEntityManager()->getRepository($provider['entity']['class']);
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
        $this->_firewallmap->add($requestMatcher, $listeners, NULL);
    }

    /**
     * @codeCoverageIgnore
     */
    private function _registerFirewall()
    {
        $this->_firewall = new Firewall($this->_firewallmap, $this->_dispatcher);
        $this->_dispatcher->addListener('frontcontroller.request', array($this->_firewall, 'onKernelRequest'));
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
     * @return type
     */
    public function getAuthenticationManager()
    {
        return $this->_authmanager;
    }

}
