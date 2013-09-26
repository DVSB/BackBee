<?php

namespace BackBuilder\Security\Logout;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Default logout success handler will redirect users to a configured path.
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{

    protected $httpUtils;
    protected $targetUrl;

    /**
     * Class constructor
     * @codeCoverageIgnore
     * @param HttpUtils $httpUtils
     * @param string    $targetUrl
     */
    public function __construct(HttpUtils $httpUtils, $targetUrl = '/')
    {
        $this->httpUtils = $httpUtils;
        $this->targetUrl = $targetUrl;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritDoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        setcookie('rememberme', '', 0, '/');
        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }

}
