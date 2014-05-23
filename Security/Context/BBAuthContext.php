<?php

namespace BackBuilder\Security\Context;

use BackBuilder\Security\Listeners\BBAuthenticationListener,
    BackBuilder\Security\Authentication\Provider\BBAuthenticationProvider,
    BackBuilder\Security\Listeners\LogoutListener,
    BackBuilder\Security\Logout\BBLogoutSuccessHandler,
    BackBuilder\Security\Logout\BBLogoutHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Description of AnonymousContext
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @subpackage  Context
 * @copyright   Lp digital system
 * @author      nicolas.dufreche <nicolas.dufreche@lp-digital.fr>
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
            $config = array_merge(array('nonce_dir' => 'security/nonces', 'lifetime' => 1200, 'use_registry' => false), $config['bb_auth']);
            $bb_provider = new BBAuthenticationProvider($this->getDefaultProvider($config), $this->getNonceDir($config), $config['lifetime'], (true === $config['use_registry']) ? $this->_getRegistryRepository() : null);
            $this->_context->addAuthProvider($bb_provider, 'bb_auth');
            $this->_context->getAuthenticationManager()->addProvider($bb_provider);
            $listeners[] = new BBAuthenticationListener($this->_context, $this->_context->getAuthenticationManager(), $this->_context->getLogger());
            $this->loadLogoutListener($bb_provider);
        }
        return $listeners;
    }

    public function loadLogoutListener($bb_provider)
    {
        if (null === $this->_context->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $logout_listener = new LogoutListener($this->_context, $httpUtils, new BBLogoutSuccessHandler($httpUtils));
            $this->_context->setLogoutListener($logout_listener);
        }

        $this->_context->getLogoutListener()->addHandler(new BBLogoutHandler($bb_provider));
    }

    public function getNonceDir($config)
    {
        return $this->_context->getApplication()->getCacheDir() . DIRECTORY_SEPARATOR . $config['nonce_dir'];
    }

    /**
     * Returns the repository to Registry entities
     * @return \BackBuillder\Bundle\Registry\Repository
     */
    private function _getRegistryRepository()
    {
        return $this->_context
                        ->getApplication()
                        ->getEntityManager()
                        ->getRepository('BackBuilder\Bundle\Registry');
    }

}
