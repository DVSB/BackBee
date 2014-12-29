<?php

namespace BackBuilder\NestedNode\Builder;

use BackBuilder\ClassContent\AClassContent;
use BackBuilder\NestedNode\Page;
use BackBuilder\NestedNode\Section;
use BackBuilder\Site\Layout;
use BackBuilder\Site\Site;
use Doctrine\ORM\EntityManager;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class PageBuilder
{
    const NO_PERSIST = 0;
    const PERSIST_AS_FIRST_CHILD = 1;
    const PERSIST_AS_LAST_CHILD = 2;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

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
     * @var string
     */
    private $redirect;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $alt_title;

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
     * @var BackBuilder\ClassContent\AClassContent
     */
    private $itemToPushInMainZone;

    /**
     * @var array of BackBuilder\ClassContent\AClassContent
     */
    private $elements;

    /**
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $archiving;

    /**
     * @var integer
     */
    private $state;

    /**
     * @var integer
     */
    private $persist;

    /**
     * Is the built page a root?
     * @var boolean
     */
    private $isRoot = false;

    /**
     * Is the built page a section?
     * @var boolean
     */
    private $isSection = false;

    /**
     * [__construct description]
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->reset();
    }

    /**
     * [getPage description]
     * @return [type] [description]
     */
    public function getPage()
    {
        if (null === $this->site || null === $this->layout || null === $this->title) {
            throw new \Exception("Required data missing");
        }

        $page = new Page($this->uid);
        $page->setTitle($this->title);
        $page->setSite($this->site);
        $page->setLayout($this->layout, $this->itemToPushInMainZone);

        if (true === $this->isRoot) {
            $root_section = new Section($page->getUid(), array('page' => $page, 'site' => $page->getSite()));
            $page->setSection($root_section);
        } elseif (true === $this->isSection) {
            $page->setParent($this->parent);
        }
        $this->doPersistIfValid($page);

        if (null !== $this->parent) {
            $page->setParent($this->parent);
        }
        $page->setLayout($this->layout, $this->itemToPushInMainZone);

        if (null !== $this->url) {
            $page->setUrl($this->url);
        }

        if (null !== $this->redirect) {
            $page->setUrl($this->redirect);
        }

        if (null !== $this->target) {
            $page->setUrl($this->target);
        }

        if (null !== $this->alt_title) {
            $page->setUrl($this->alt_title);
        }

        if (null !== $this->state) {
            $page->setState($this->state);
        }

        if (null !== $this->publishedAt) {
            $page->setPublishing($this->publishedAt);
        }

        if (null !== $this->createdAt) {
            $page->setCreated($this->createdAt);
        }

        if (null !== $this->archiving) {
            $page->setArchiving($this->archiving);
        }

        $pageContentSet = $page->getContentSet();
        $this->updateContentRevision($pageContentSet);

        if (0 < count($this->elements)) {
            foreach ($this->elements as $e) {
                $column = $pageContentSet->item($e['content_set_position']);
                if (true === $e['set_main_node']) {
                    $e['content']->setMainNode($page);
                }

                $column->push($e['content']);
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
        $this->publishedAt = null;
        $this->state = null;
        $this->persist = null;
        $this->isRoot = false;
        $this->isSection = false;
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
        $this->url = preg_replace('/\/+/', '/', $url);

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
     * Is the built page a root?
     * @param boolean $isRoot
     * @return \BackBuilder\NestedNode\Builder\PageBuilder
     */
    public function isRoot($isRoot = true)
    {
        $this->isRoot = (true === $isRoot);
        return $this;
    }

    /**
     * Is the built page a section?
     * @param boolean $isSection
     * @return \BackBuilder\NestedNode\Builder\PageBuilder
     */
    public function isSection($isSection = true)
    {
        $this->isSection = (true == $isSection);
        return $this;
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
    public function setLayout(Layout $layout, AClassContent $toPushInMainZone = null)
    {
        $this->layout = $layout;
        $this->itemToPushInMainZone = $toPushInMainZone;

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
    public function pushElement(AClassContent $element, $setMainNode = false, $contentSetPos = 0)
    {
        $this->elements[] = array(
            'content'               => $element,
            'set_main_node'         => $setMainNode,
            'content_set_position'  => $contentSetPos,
        );

        return $this;
    }

    /**
     * [getPage description]
     * @return [type] [description]
     */
    public function addElement(AClassContent $element, $index = null, $setMainNode = false, $contentSetPos = 0)
    {
        if (null !== $index) {
            $index = intval($index);
            if (false === array_key_exists($index, $this->elements)) {
                throw new \Exception();
            }

            $this->elements[$index] = array(
                'content'               => $element,
                'set_main_node'         => $setMainNode,
                'content_set_position'  => $contentSetPos,
            );
        } else {
            $this->pushElement($element, $setMainNode, $contentSetPos);
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

    /**
     * Gets the value of publishedAt.
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Sets the value of publishedAt.
     *
     * @param \DateTime $publishedAt the published at
     *
     * @return self
     */
    public function publishedAt(\DateTime $publishedAt = null)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Alias of publishedAt
     *
     * @see self::publishedAt
     */
    public function setPublishing(\DateTime $publishing = null)
    {
        return $this->publishedAt($publishing);
    }

    /**
     * Gets the value of createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the value of createdAt.
     *
     * @param \DateTime $createdAt the created at
     *
     * @return self
     */
    public function createdAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets the value of archiving.
     *
     * @return \DateTime
     */
    public function getArchiving()
    {
        return $this->archiving;
    }

    /**
     * Sets the value of archiving.
     *
     * @param \DateTime $archiving the created at
     *
     * @return self
     */
    public function setArchiving(\DateTime $archiving = null)
    {
        $this->archiving = $archiving;

        return $this;
    }

    /**
     * Gets the value of target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Sets the value of target.
     *
     * @param string $target the target
     *
     * @return self
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Gets the value of redirect.
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Sets the value of redirect.
     *
     * @param string $redirect the redirect
     *
     * @return self
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * Gets the value of alt_title.
     *
     * @return string
     */
    public function getAltTitle()
    {
        return $this->alt_title;
    }

    /**
     * Sets the value of alt_title.
     *
     * @param string $alt_title the alt_title
     *
     * @return self
     */
    public function setAltTitle($alt_title)
    {
        $this->alt_title = $alt_title;

        return $this;
    }

    /**
     * Sets the persist mode;
     * /!\ if you set a valid persist mode (SELF::INSERT_AS_FIRST_CHILD or SELF::INSERT_AS_LAST_CHILD),
     * this page will be persist for you, it also modified the left and right node of the tree
     *
     * @param integer $mode
     */
    public function setPersistMode($mode)
    {
        $this->persist = $mode;
    }

    /**
     * Call
     * @param  Page   $page [description]
     * @return [type] [description]
     */
    private function doPersistIfValid(Page $page)
    {
        if (null === $page->getParent()) {
            return;
        }

        $method = '';
        if (self::PERSIST_AS_FIRST_CHILD === $this->persist) {
            $method = 'insertNodeAsFirstChildOf';
        } elseif (self::PERSIST_AS_LAST_CHILD === $this->persist) {
            $method = 'insertNodeAsLastChildOf';
        }

        if (false === empty($method)) {
            $this->em->getRepository('BackBuilder\NestedNode\Page')->$method($page, $page->getParent(), $this->isSection);
        }
    }
}
