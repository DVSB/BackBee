<?php

namespace BackBuilder\Renderer;

use BackBuilder\BBApplication,
	BackBuilder\Renderer\IRendererAdapter,
	BackBuilder\Renderer\Helper\HelperManager;

abstract class ARendererAdapter implements IRendererAdapter
{
	/**
	 * @var BBApplication
	 */
	protected $bbapp;

	/**
	 * @var BackBuilder\Renderer\Helper\HelperManager
	 */
	protected $helperManager;

	/**
	 * @var string
	 */
	private $defaultExt;

	/**
	 * [__construct description]
	 * @param BBApplication $bbapp [description]
	 */
	public function __construct(BBApplication $bbapp)
	{
		$this->bbapp = $bbapp;
	}

	/**
	 * [__call description]
	 * @param  [type] $method [description]
	 * @param  [type] $argv   [description]
	 * @return [type]         [description]
	 */
	public function __call($method, $argv)
	{
		$helper = $this->helperManager->get($method);
		if (null === $helper) {
			$helper = $this->helperManager->create($method, $argv);
		}

        if (is_callable($helper)) {
            return call_user_func_array($helper, $argv);
        }

        return $helper;
	}

	/**
	 * [setHelperManager description]
	 * @param HelperManager $helperManager [description]
	 */
	public function setHelperManager(HelperManager $helperManager)
	{
		$this->helperManager = $helperManager;
	}

	/**
	 * [getUri description]
	 * @param  [type] $pathinfo [description]
	 * @return [type]           [description]
	 */
	public function getUri($pathinfo = null)
    {
        if (null !== $pathinfo && preg_match('/^http[s]?:\/\//', $pathinfo)) {
            return $pathinfo;
        }

        if ('/' !== substr($pathinfo, 0, 1)) {
            $pathinfo = '/' . $pathinfo;
        }

        if (true === $this->bbapp->isStarted() && null !== $this->bbapp->getRequest()) {
            $request = $this->bbapp->getRequest();

            if (null === $pathinfo) {
                $pathinfo = $request->getBaseUrl();
            }

            if (basename($request->getBaseUrl()) == basename($request->server->get('SCRIPT_NAME'))) {
                return $request->getSchemeAndHttpHost() . substr($request->getBaseUrl(), 0, -1 * (1 + strlen(basename($request->getBaseUrl())))) . $pathinfo;
            } else {
                return $request->getUriForPath($pathinfo);
            }
        }

        if (false === strpos(basename($pathinfo), '.') && '/' != substr($pathinfo, -1)) {
            if (null === $this->defaultExt && true === $this->bbapp->getContainer()->has('site')) {
            	$this->defaultExt = $this->bbapp->getContainer()->get('site')->getDefaultExtension();
            }

            $pathinfo .= $this->defaultExt;
        }

        return $pathinfo;
    }
}
