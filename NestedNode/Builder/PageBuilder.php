<?php

namespace BackBuilder\NestedNode\Builder;

use BackBuilder\ClassContent\AClassContent,
	BackBuilder\NestedNode\Page,
	BackBuilder\Site\Layout,
	BackBuilder\Site\Site;

/**
 * 
 */
class PageBuilder
{
	/**
	 * @var string
	 */
	private $uid;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var BackBuilder\Site\Site
	 */
	private $site;

	/**
	 * @var BackBuilder\NestedNode\Page
	 */
	private $root;

	/**
	 * BackBuilder\NestedNode\Page
	 */
	private $parent;

	/**
	 * @var BackBuilder\Site\Layout
	 */
	private $layout;

	/**
	 * @var array of BackBuilder\ClassContent\AClassContent
	 */
	private $elements;

	/**
	 * [__construct description]
	 */
	public function __construct()
	{
		$this->reset();
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getPage()
	{
		if (null === $this->site || null === $this->layout || null === $this->title) {
			throw new \Exception();
		}

		$page = new Page($this->uid);
		$page->setTitle($this->title);
		$page->setSite($this->site);
		$page->setLayout($this->layout);

		if (null !== $this->root) {
			$page->setRoot($this->root);
		}

		if (null !== $this->parent) {
			$page->setParent($this->parent);
		}

		if (null !== $this->url) {
			$page->setUrl($this->url);
		}

		if (null !== $this->state) {
			$page->setState($this->state);
		}

		$pageContentSet = $page->getContentSet();
		$this->updateContentRevision($pageContentSet);

		if (0 < count($this->elements)) {
			$firstColumn = $pageContentSet->first();
			$firstColumn->clear();
			foreach ($this->elements as $e) {
				if (true === $e['set_main_node']) {
					$e['content']->setMainNode($page);
				}

				$firstColumn->push($e['content']);
			}

			$pageContentSet->rewind();
		}

		while ($column = $pageContentSet->next()) {
			$this->updateContentRevision($column);
		}

		$this->reset();

		return $page;
	}

	private function reset()
	{
		$this->uid = null;
		$this->title = null;
		$this->url = null;
		$this->site = null;
		$this->root = null;
		$this->parent = null;
		$this->layout = null;
		$this->elements = array();
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setUid($uid)
	{
		$this->uid = $uid;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setSite(Site $site)
	{
			$this->site = $site;

			return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getSite()
	{
		return $this->site;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setRoot(Page $root, $isRoot = false)
	{
		$this->root = $root;

		if (true === $isRoot) {
			$this->setParent($root);
		}

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setParent(Page $parent)
	{
		$this->parent = $parent;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setLayout(Layout $layout)
	{
		$this->layout = $layout;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function setState($state)
	{
		$this->state = $state;

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function putOnlineAndVisible()
	{
		return $this->setState(Page::STATE_ONLINE);
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function putOnlineAndHidden()
	{
		return $this->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function pushElement(AClassContent $element, $setMainNode = false)
	{
		$this->elements[] = array(
			'content' 		=> $element,
			'set_main_node' => $setMainNode
		);

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function addElement(AClassContent $element, $index = null, $setMainNode = false)
	{
		if (null !== $index) {
			$index = intval($index);
			if (false === array_key_exists($index, $this->elements)) {
				throw new \Exception();
			}

			$this->elements[$index] = array(
				'content' 		=> $element,
				'set_main_node' => $setMainNode
			);
		} else {
			$this->pushElement($element, $setMainNode);
		}

		return $this;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function getElement($index)
	{
		return (true === array_key_exists((int) $index, $this->elements) ? $this->elements[$index] : null);
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function elements()
	{
		return $this->elements;
	}

	/**
	 * [getPage description]
	 * @return [type] [description]
	 */
	public function clearElements()
	{
		$this->elements = array();

		return $this;
	}

	private function updateContentRevision(AClassContent $content, $revision = 1, $state = AClassContent::STATE_NORMAL)
	{
		$content->setRevision($revision);
		$content->setState($state);
	}
}
