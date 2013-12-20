<?php

namespace BackBuilder\Renderer\Helper;

use BackBuilder\Renderer\ARenderer;

use Symfony\Component\HttpFoundation\ParameterBag;

class HelperManager
{
	/**
	 * [$renderer description]
	 * @var [type]
	 */
	private $renderer;

	/**
	 * [$bbapp description]
	 * @var [type]
	 */
	private $bbapp;

	/**
	 * [$helpers description]
	 * @var [type]
	 */
	private $helpers;

	/**
	 * [__construct description]
	 * @param ARenderer $renderer [description]
	 */
	public function __construct(ARenderer $renderer)
	{
		$this->renderer = $renderer;
		$this->bbapp = $this->renderer->getApplication();
		$this->helpers = new ParameterBag();
	}

	/**
	 * [get description]
	 * @param  [type] $method [description]
	 * @return [type]         [description]
	 */
	public function get($method)
	{
		$helper = null;
		if (true === $this->helpers->has($method)) {
			$helper = $this->helpers->get($method);
		} 

		return $helper;
	}

	/**
	 * [create description]
	 * @param  [type] $method [description]
	 * @param  [type] $argv   [description]
	 * @return [type]         [description]
	 */
	public function create($method, $argv)
	{
		$helperClass = '\BackBuilder\Renderer\Helper\\' . $method;
		if (true === class_exists($helperClass)) {
			$this->helpers->set($method, new $helperClass($this->renderer, $argv));
		}
		
		return $this->helpers->get($method);
	}

	/**
	 * [updateRenderer description]
	 * @param  ARenderer $renderer [description]
	 * @return [type]              [description]
	 */
	public function updateRenderer(ARenderer $renderer)
	{
		$this->renderer = $renderer;
		foreach ($this->helpers->all() as $h) {
			$h->setRenderer($renderer);
		}
	}
}
