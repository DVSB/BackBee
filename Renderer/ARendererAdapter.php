<?php

namespace BackBuilder\Renderer;

use	BackBuilder\NestedNode\Page,
	BackBuilder\Renderer\ARenderer,
	BackBuilder\Renderer\Helper\HelperManager,
	BackBuilder\Renderer\IRenderable,
	BackBuilder\Renderer\IRendererAdapter;

abstract class ARendererAdapter implements IRendererAdapter
{
	/**
	 * @var BackBuilder\Renderer\ARenderer
	 */
	protected $renderer;

	/**
	 * [__construct description]
	 * @param BBApplication $bbapp [description]
	 */
	public function __construct(ARenderer $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * [__call description]
	 * @param  [type] $method [description]
	 * @param  [type] $argv   [description]
	 * @return [type]         [description]
	 */
	public function __call($method, $argv)
	{        
		return call_user_func_array(array($this->renderer, $method), $argv);
	}

	/**
	 * [setHelperManager description]
	 * @param HelperManager $helperManager [description]
	 */
	public function setRenderer(ARenderer $renderer)
	{
		$this->renderer = $renderer;
	}
}
