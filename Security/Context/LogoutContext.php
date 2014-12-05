<?php

namespace BackBuilder\Security\Context;

use BackBuilder\Security\Listeners\LogoutListener;
use BackBuilder\Security\Logout\LogoutSuccessHandler;
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
class LogoutContext extends AbstractContext implements ContextInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadListeners($config)
    {
        if (array_key_exists('logout', $config)) {
            if (array_key_exists('handlers', $config['logout']) && is_array($handlers = $config['logout']['handlers'])) {
                $this->initLogoutListener();
                $this->setHandlers($handlers);
            }
        }

        return array();
    }

    public function initLogoutListener()
    {
        if (null === $this->_context->getLogoutListener()) {
            $httpUtils = new HttpUtils();
            $this->_context->setLogoutListener(new LogoutListener($this->_context, $httpUtils, new LogoutSuccessHandler($httpUtils)));
        }
    }

    public function setHandlers($handlers)
    {
        foreach ($handlers as $handler) {
            $this->_context->getLogoutListener()->addHandler(new $handler());
        }
    }
}
