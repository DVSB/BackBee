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

namespace BackBuilder\NestedNode;

use BackBuilder\Exception\BBException,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\ContentSet,
    BackBuilder\NestedNode\ANestedNode,
    BackBuilder\Renderer\IRenderable,
    BackBuilder\Site\Layout,
    BackBuilder\Site\Site,
    BackBuilder\MetaData\MetaDataBag,
    BackBuilder\Workflow\State;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * Page object in BackBuilder
 *
 * A page basically is an URI an a set of content defined for a website.
 * A page must have a layout defined to be displayed.
 * 
 * State of a page is bit operation on one or several following values:
 * 
 * * STATE_OFFLINE
 * * STATE_ONLINE
 * * STATE_HIDDEN
 * * STATE_DELETED
 *
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\PageRepository")
 * @Table(name="page",indexes={@index(name="IDX_STATE", columns={"state"}), @index(name="IDX_ARCHIVING", columns={"archiving"}), @index(name="IDX_PUBLISHING", columns={"publishing"}), @index(name="IDX_ROOT", columns={"root_uid"}), @index(name="IDX_PARENT", columns={"parent_uid"}), @index(name="IDX_SELECT_PAGE", columns={"root_uid", "leftnode", "rightnode"}), @index(name="IDX_URL", columns={"site_uid", "url"})})
 * @HasLifecycleCallbacks
 * @fixtures(qty=1)
 */
class Page extends ANestedNode implements IRenderable, DomainObjectInterface
{
    /**
     * State off- line: the page can not be displayed on the website
     * @var int
     */

    const STATE_OFFLINE = 0;

    /**
     * State on-line: the page can be displayed on the website
     * @var int
     */
    const STATE_ONLINE = 1;

    /**
     * State hidden: the page can not appeared in menus
     * @var int
     */
    const STATE_HIDDEN = 2;

    /**
     * State deleted: the page does not appear in the tree of the website
     * @var int
     */
    const STATE_DELETED = 4;

    /**
     * Type static: thez page is an stored and managed entity
     * @var int
     */
    const TYPE_STATIC = 1;

    /**
     * Type dynamic: the page is not a managed entity
     * @var int
     */
    const TYPE_DYNAMIC = 2;

    /**
     * Default target if redirect is defined
     * @var string
     */
    const DEFAULT_TARGET = '_self';

    /**
     * Unique identifier of the page
     * @var string
     * @Id @Column(type="string", name="uid")
     * @fixture(type="md5")
     */
    protected $_uid;

    /**
     * The owner site of this node
     * @var \BackBuilder\Site\Site
     * @ManyToOne(targetEntity="BackBuilder\Site\Site")
     * @JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * The layout associated to the page
     * @var \BackBuilder\Site\Layout
     * @ManyToOne(targetEntity="BackBuilder\Site\Layout", inversedBy="_pages")
     * @JoinColumn(name="layout_uid", referencedColumnName="uid")
     */
    protected $_layout;

    /**
     * The root node, cannot be NULL.
     * @var \BackBuilder\NestedNode\Page
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Page", inversedBy="_descendants")
     * @JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     * @var \BackBuilder\NestedNode\Page
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Page", inversedBy="_children")
     * @JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The title of this page
     * @var string
     * @Column(type="string", name="title", nullable=false)
     * @fixture(type="sentence", value=6)
     */
    protected $_title;
    /**
     * The alternate title of this page
     * @var string
     * @Column(type="string", name="alttitle", nullable=true)
     * @fixture(type="sentence", value=6)
     */
    protected $_alttitle;

    /**
     * The URI of this page
     * @var string
     * @Column(type="string", name="url", nullable=false)
     */
    protected $_url;

    /**
     * Target of this page if redirect defined.
     * @var string
     * @column(type="string", name="target", nullable=false)
     */
    protected $_target;

    /**
     * Permanent redirect.
     * @var string
     * @column(type="string", name="redirect", nullable=true)
     */
    protected $_redirect;

    /**
     * Metadatas associated to the page
     * @var \BackBuilder\MetaData\MetaDataBag
     * @Column(type="object", name="metadata", nullable=true)
     */
    protected $_metadata;

    /**
     * The associated ContentSet
     * @var \BackBuilder\ClassContent\ContentSet
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\ContentSet", inversedBy="_pages", cascade={"persist", "remove"})
     * @JoinColumn(name="contentset", referencedColumnName="uid")
     */
    protected $_contentset;

    /**
     * The publication datetime
     * @var \DateTime
     * @Column(type="datetime", name="date", nullable=true)
     * @fixture(type="dateTime")
     */
    protected $_date;

    /**
     * The state of the page
     * @var int
     * @Column(type="smallint", name="state", nullable=false)
     * @fixture(type="boolean")
     */
    protected $_state;

    /**
     * The auto publishing datetime
     * @var \DateTime
     * @Column(type="datetime", name="publishing", nullable=true)
     */
    protected $_publishing;

    /**
     * The auto-archiving datetime
     * @var \DateTime
     * @Column(type="datetime", name="archiving", nullable=true)
     */
    protected $_archiving;

    /**
     * The optional workflow state.
     * @var \BackBuilder\Workflow\State
     * @ManyToOne(targetEntity="BackBuilder\Workflow\State")
     * @JoinColumn(name="workflow_state", referencedColumnName="uid")
     */
    protected $_workflow_state;

    /**
     * Descendants nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Page", mappedBy="_root")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\Page", mappedBy="_parent")
     */
    protected $_children;

    /**
     * Revisions of the current page
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\PageRevision", mappedBy="_page")
     */
    protected $_revisions;

    /**
     * The type of the page
     * @var int
     */
    protected $_type;

    /**
     * An array of ascendants
     * @var array
     */
    protected $_breadcrumb = null;

    /**
     * Associated array of available states for the page
     * @var array
     */
    public static $STATES = array(
        'Offline' => self::STATE_OFFLINE,
        'Online' => self::STATE_ONLINE,
        'Hidden' => self::STATE_HIDDEN,
        'Deleted' => self::STATE_DELETED,
    );

    /**
     * Utility property used on cloning page
     * @var array
     */
    public $cloning_datas;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the page
     * @param array $options Initial options for the page:
     *                         - title      the default title
     *                         - url        the default url
     */
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid);

        if (true === is_array($options)) {
            if (true === array_key_exists('title', $options)) {
                $this->setTitle($options['title']);
            }
            if (true === array_key_exists('url', $options)) {
                $this->setUrl($options['url']);
            }
        }

        $this->_contentset = new ContentSet();
        $this->_revisions = new ArrayCollection();
        $this->_state = self::STATE_HIDDEN;
        $this->_type = self::TYPE_DYNAMIC;
        $this->_target = self::DEFAULT_TARGET;
    }

    /**
     * Magical cloning method
     */
    public function __clone()
    {
        $current_uid = $this->_uid;

        $this->cloning_datas = array(
            'pages' => array(),
            'contents' => array()
        );

        if ($this->_uid) {
            if (null !== $this->_contentset) {
                $this->_contentset = $this->_contentset->createClone($this);
            } else {
                $this->_contentset = new ContentSet();
            }
            
            $this->_uid = md5(uniqid('', true));
            $this->_leftnode = 1;
            $this->_rightnode = $this->_leftnode + 1;
            $this->_level = 0;
            $this->_created = new \DateTime();
            $this->_modified = new \DateTime();
            $this->_parent = null;
            $this->_root = $this;
            $this->_state = Page::STATE_OFFLINE;
            $this->_type = Page::TYPE_DYNAMIC;

            $this->_children->clear();
            $this->_descendants->clear();
            $this->_revisions->clear();

            $this->cloning_datas['pages'][$current_uid] = $this;
        }
    }

    /**
     * Returns the parent node, NULL if this node is root
     * @codeCoverageIgnore
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Returns the owner site of this node.
     * @codeCoverageIgnore
     * @return \Backbuilder\Site\Site
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Returns the main contentset associated to the node.
     * @return \BackBuilder\ClassContent\ContentSet
     */
    public function getContentSet()
    {
        if (null === $this->_contentset) {
            $this->_contentset = new ContentSet();
        }

        return $this->_contentset;
    }

    /**
     * Return sthe layout of the page.
     * @codeCoverageIgnore
     * @return \BackBuilder\Site\Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the alternate title of the page.
     * @codeCoverageIgnore
     * @return string
     */
    public function getAltTitle()
    {
        return $this->_alttitle;
    }
    /**
     * Returns the title of the page.
     * @codeCoverageIgnore
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns the URL of the page.
     * @codeCoverageIgnore
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Returns the URL with extension of the page
     * @return string
     */
    public function getNormalizeUri()
    {
        if (null === $this->getSite()) {
            return $this->getUrl();
        }

        return $this->getUrl() . $this->getSite()->getDefaultExtension();
    }

    /**
     * Returns the target.
     * @codeCoverageIgnore
     * @return string
     */
    public function getTarget()
    {
        return ((null === $this->_target) ? self::DEFAULT_TARGET : $this->_target);
    }

    /**
     * Returns the premanent redirect URL if defined
     * @codeCoverageIgnore
     * @return string|NULL
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * Returns the associated metadata if defined
     * @codeCoverageIgnore
     * @return \BackBuilder\MetaData\MetaDataBag|NULL
     */
    public function getMetaData()
    {
        return $this->_metadata;
    }

    /**
     * Returns the state of the page.
     * @codeCoverageIgnore
     * @return int
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Returns the date
     * @codeCoverageIgnore
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Returns the publishing date if defined.
     * @codeCoverageIgnore
     * @return \DateTime|NULL
     */
    public function getPublishing()
    {
        return $this->_publishing;
    }

    /**
     * Returns the archiving date if defined.
     * @codeCoverageIgnore
     * @return \DateTime|NULL
     */
    public function getArchiving()
    {
        return $this->_archiving;
    }

    /**
     * Returns the collection of revisions.
     * @codeCoverageIgnore
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getData($var = null)
    {
        $data = $this->toArray();

        if (null !== $var) {
            if (false === array_key_exists($var, $data)) {
                return null;
            }

            return $data[$var];
        }

        return $data;
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param string $var
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel()
        );

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return null;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns the worflow state if defined, NULL otherwise
     * @return \BackBuilder\Workflow\State
     * @codeCoverageIgnore
     */
    public function getWorkflowState()
    {
        return $this->_workflow_state;
    }

    /**
     * Returns TRUE if the page can be rendered.
     * @return Boolean
     */
    public function isRenderable()
    {
        return $this->isOnline();
    }

    /**
     * Is the publishing state of the page is scheduled ?
     * @return Boolean TRUE if the publishing state is scheduled, FALSE otherwise
     */
    public function isScheduled()
    {
        return (null !== $this->getPublishing() || null !== $this->getArchiving());
    }

    /**
     * Is the page is visible (ie online and not hidden) ?
     * @return Boolean TRUE if the page is visible, FALSE otherwise
     */
    public function isVisible()
    {
        return ($this->isOnline()
                && !($this->getState() & self::STATE_HIDDEN));
    }

    /**
     * Is the page online ?
     * @param Boolean $ignoreSchedule
     * @return Boolean TRUE if the page is online, FALSE otherwise
     */
    public function isOnline($ignoreSchedule = false)
    {
        if (true === $ignoreSchedule) {
            return (($this->getState() & self::STATE_ONLINE)
                    && !($this->getState() & self::STATE_DELETED));
        } else {
            return (($this->getState() & self::STATE_ONLINE)
                    && !($this->getState() & self::STATE_DELETED)
                    && (null === $this->getPublishing() || 0 === $this->getPublishing()->diff(new \DateTime())->invert)
                    && (null === $this->getArchiving() || 1 === $this->getArchiving()->diff(new \DateTime())->invert));
        }
    }

    /**
     * Is the page deleted ?
     * @return Boolean TRUE if the page has been deleted
     */
    public function isDeleted()
    {
        return ($this->getState() & self::STATE_DELETED);
    }

    /**
     * Is the page is static ?
     * @return boolean
     */
    public function isStatic()
    {
        return (Page::TYPE_STATIC === $this->_type);
    }

    /**
     * Sets the associated site
     * @codeCoverageIgnore
     * @param \BackBuilder\NestedNode\Site $site
     * @return \BackBuilder\NestedNode\Page
     */
    public function setSite(Site $site = null)
    {
        $this->_site = $site;
        return $this;
    }

    /**
     * Sets the main contentset associated to the node.
     * @codeCoverageIgnore
     * @param \BackBuilder\ClassContent\ContentSet $contentset
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setContentset(ContentSet $contentset)
    {
        $this->_contentset = $contentset;
        return $this;
    }

    /**
     * Sets the date of the page
     * @codeCoverageIgnore
     * @param \DateTime $date
     * @return \BackBuilder\NestedNode\Page
     */
    public function setDate(\DateTime $date)
    {
        $this->_date = $date;
        return $this;
    }

    /**
     * Sets the layout for the page.
     * Adds as much ContentSet to the page main ContentSet than defined zones in layout
     * @param \BackBuilder\Site\Layout $layout
     * @param \BackBuilder\ClassContent\AClassContent $toPushInMainZone
     * @return \BackBuilder\NestedNode\Page
     */
    public function setLayout(Layout $layout, AClassContent $toPushInMainZone = null)
    {
        $this->_layout = $layout;

        // add as much ContentSet to the page main ContentSet than defined zones in layout
        for ($i = $this->getContentSet()->count(); $i < count($layout->getZones()); $i++) {
            $contentset = new ContentSet();

            if (null !== $zone = $layout->getZone($i)) {
                $contentset = new ContentSet(null, $zone->options);

                if (null !== $toPushInMainZone && true === $zone->mainZone) {
                    // New content push in the main zone
                    $toPushInMainZone->setMainNode($this);
                    $contentset->push($toPushInMainZone);
                } else if ('inherited' === $zone->defaultClassContent) {
                    // Inherited zone => same ContentSet than parent if exist
                    if (null !== $this->getParent() && $i < $this->getParent()->getContentSet()->count()) {
                        if (null !== $herited = $this->getParent()->getContentSet()->item($i)) {
                            $contentset = $herited;
                        }
                    }
                } else if ($zone->defaultClassContent) {
                    // New default content push
                    $classname = 'BackBuilder\ClassContent\\' . $zone->defaultClassContent;
                    if (true === class_exists($classname)) {
                        $content = new $classname();

                        if (null !== $content->getProperty('labelized-by')) {
                            try {
                                eval('$content->' . $content->getProperty('labelized-by') . '="' . str_replace('"', '\\"', $this->getTitle()) . '";');
                            } catch (\Exception $e) {
                                // Nothing to do
                            }
                        }

                        if (true === $zone->mainZone) {
                            $content->setMainNode($this);
                        }

                        $contentset->push($content);
                    }
                }
            }

            $this->getContentSet()->push($contentset);
        }

        return $this;
    }

    /**
     * Sets the alternate title of the page.
     * @codeCoverageIgnore
     * @param string $alttitle
     * @return \BackBuilder\NestedNode\Page
     */
    public function setAltTitle($alttitle)
    {
        $this->_alttitle = $alttitle;
        return $this;
    }
    /**
     * Sets the title of the page.
     * @codeCoverageIgnore
     * @param string $title
     * @return \BackBuilder\NestedNode\Page
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * Sets the URL of the page
     * @codeCoverageIgnore
     * @param string $url
     * @return \BackBuilder\NestedNode\Page
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * Sets the target if a permanent redirect is defined
     * @codeCoverageIgnore
     * @param string $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function setTarget($target)
    {
        $this->_target = $target;
        return $this;
    }

    /**
     * Sets a permanent redirect
     * @codeCoverageIgnore
     * @param string $redirect
     * @return \BackBuilder\NestedNode\Page
     */
    public function setRedirect($redirect)
    {
        $this->_redirect = $redirect;
        return $this;
    }

    /**
     * Sets the associated metadata
     * @codeCoverageIgnore
     * @param \BackBuilder\MetaData\MetaDataBag $metadata
     * @return \BackBuilder\NestedNode\Page
     */
    public function setMetaData(MetaDataBag $metadata = null)
    {
        $this->_metadata = $metadata;
        return $this;
    }

    /**
     * Sets the state
     * @codeCoverageIgnore
     * @param int $state
     * @return \BackBuilder\NestedNode\Page
     */
    public function setState($state)
    {
        $this->_state = $state;
        return $this;
    }

    /**
     * Sets the publishing date
     * @codeCoverageIgnore
     * @param \DateTime $publishing
     * @return \BackBuilder\NestedNode\Page
     */
    public function setPublishing(\DateTime $publishing = null)
    {
        $this->_publishing = $publishing;
        return $this;
    }

    /**
     * Sets the archiving date
     * @codeCoverageIgnore
     * @param \DateTime $archiving
     * @return \BackBuilder\NestedNode\Page
     */
    public function setArchiving(\DateTime $archiving = null)
    {
        $this->_archiving = $archiving;
        return $this;
    }

    /**
     * Sets a collection of revisions for the page
     * @codeCoverageIgnore
     * @param \Doctrine\Common\Collections\ArrayCollection $revisions
     * @return \BackBuilder\NestedNode\Page
     */
    public function setRevisions(ArrayCollection $revisions)
    {
        $this->_revision = $revisions;
        return $this;
    }

    /**
     * Sets the workflow state
     * @param \BackBuilder\Workflow\State $state
     * @return \BackBuilder\NestedNode\Page
     * @codeCoverageIgnore
     */
    public function setWorkflowState(State $state = null)
    {
        $this->_workflow_state = $state;
        return $this;
    }

    /**
     * Returns the inherited zone according to the provided ContentSet
     * @param \BackBuilder\ClassContent\ContentSet $contentSet
     * @return \StdClass The inherited zone
     */
    public function getInheritedContensetZoneParams(ContentSet $contentSet)
    {
        $zone = null;
        if (null !== $layoutZones = $this->getLayout()->getZones()) {
            $contentSetCount = $this->getContentSet()->count();

            for ($i = 0; $i < count($contentSetCount); $i++) {
                $parentContentset = $this->getParent()->getContentSet()->item($i);
                if ($contentSet->getUid() === $parentContentset->getUid()) {
                    $zone = $layoutZones[$i];
                }
            }
        }

        return $zone;
    }

    /**
     * Returns the index of the provided ContentSet in the main ContentSet
     * @param \BackBuilder\ClassContent\ContentSet $contentSet
     * @return int
     */
    public function getRootContentSetPosition(ContentSet $contentSet)
    {
        return $this->getContentSet()->indexOfByUid($contentSet, true);
    }

    /**
     * Returns the parent ContentSet in the same zone, FALSE if it is not found
     * @param \BackBuilder\ClassContent\ContentSet $contentSet
     * @return \BackBuilderClassContent\ContentSet|FALSE
     */
    public function getParentZoneAtSamePositionIfExists(ContentSet $contentSet)
    {
        $indexOfContent = $this->getContentSet()->indexOfByUid($contentSet, true);
        if (false === $indexOfContent) {
            return false;
        }

        $parent = $this->getParent();
        if (null === $parent) {
            return false;
        }

        $parentContentSet = $parent->getContentSet()->item($indexOfContent);
        if ($parentContentSet) {
            return $parentContentSet;
        }

        return false;
    }

    /**
     * Tells which "rootContentset" is inherited from currentpage's parent
     * @param type $uidOnly 
     * @return array Array of contentset uids
     */
    public function getInheritedZones($uidOnly = false)
    {
        $inheritedZones = array();
        $uidOnly = (isset($uidOnly) && is_bool($uidOnly)) ? $uidOnly : false;
        if (null !== $this->getParent()) {
            $parentZones = $this->getParent()->getContentSet();
            $cPageRootZoneContainer = $this->getContentSet();
            foreach ($cPageRootZoneContainer as $currentpageRootZone) {
                $result = $parentZones->indexOfByUid($currentpageRootZone);
                if ($result)
                    $inheritedZones[$currentpageRootZone->getUid()] = $currentpageRootZone;
            }
            if ($uidOnly) {
                $inheritedZones = array_keys($inheritedZones);
            }
        }
        return $inheritedZones;
    }

    /**
     * Returns the main zones of the page
     * Page's mainzone can't be unlinked
     * @return array
     */
    public function getPageMainZones()
    {
        $currentpageRootZones = $this->getContentSet();
        $layoutZones = $this->getLayout()->getZones();
        $result = array();
        for ($i = 0; $i < count($currentpageRootZones); $i++) {
            $currentZone = $currentpageRootZones->item($i);
            if (count($layoutZones) > $i) {
                $zoneInfos = $layoutZones[$i];
                if ($zoneInfos && property_exists($zoneInfos, 'mainZone') && true === $zoneInfos->mainZone) {
                    $result[$currentZone->getUid()] = $currentZone;
                }
            }
        }
        return $result;
    }

    /**
     * Is the ContentSet is linked to his parent
     * @param \BackBuilder\ClassContent\ContentSet $contentset
     * @return Boolean
     */
    private function _isLinkedToHisParentBy(ContentSet $contentset = null)
    {
        $contentset = (!is_null($contentset)) ? $contentset : false;
        $result = false;
        if (false !== $contentset) {
            $inheritedZones = $this->getInheritedZones();
            if (array_key_exists($contentset->getUid(), $inheritedZones)) {
                $result = true; //$inheritedZones[$contentset->getUid()];
            }
        }
        return $result;
    }

    /**
     * Replaces the ContentSet of the page
     * @param \BackBuilder\ClassContent\ContentSet $contentToReplace
     * @param \BackBuilder\ClassContent\ContentSet $newContentSet
     * @param Boolean $checkContentsLinkToParent
     * @return \BackBuilder\ClassContent\ContentSet
     */
    public function replaceRootContentSet(ContentSet $contentToReplace, ContentSet $newContentSet, $checkContentsLinkToParent = true)
    {
        /* check link with parent */
        $result = false;
        $checkContentsLinkToParent = (is_bool($checkContentsLinkToParent)) ? $checkContentsLinkToParent : false;
        $contentIsLinked = ($checkContentsLinkToParent) ? $this->_isLinkedToHisParentBy($contentToReplace) : true;

        if ($contentIsLinked) {
            $mainContentSet = $this->getContentSet();
            if ($mainContentSet) {
                $result = $mainContentSet->replaceChildBy($contentToReplace, $newContentSet);
            }
        }
        return $newContentSet;
    }

    /**
     * Returns an array representation of the page.
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();

        $result['siteuid'] = $this->getSite()->getUid();
        $result['title'] = $this->getTitle();
        $result['alttitle'] = $this->getAltTitle();
        $result['url'] = $this->getUrl();
        $result['target'] = $this->getTarget();
        $result['redirect'] = $this->getRedirect();
        $result['state'] = $this->getState();
        $result['date'] = (null !== $this->getDate()) ? $this->getDate()->getTimestamp() : null;
        $result['publishing'] = (null !== $this->getPublishing()) ? $this->getPublishing()->getTimestamp() : null;
        $result['archiving'] = (null !== $this->getArchiving()) ? $this->getArchiving()->getTimestamp() : null;
        $result['metadata'] = (null !== $this->getMetaData()) ? $this->getMetaData()->toArray() : null;
        $result['layout_uid'] = (null !== $this->getLayout()) ? $this->getLayout()->getUid() : null;
        $result['workflow_state'] = (null !== $this->getWorkflowState()) ? $this->getWorkflowState()->getCode() : null;

        return $result;
    }

    /**
     * Constructs the node from a string or object
     * @param mixed $serialized The string representation of the object.
     * @return \BackBuilder\NestedNode\ANestedNode
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the serialized data can not be decode or, 
     *                                                         with strict mode, if a property does not exists
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized))
            $serialized = json_decode($serialized);

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;
            if (in_array($property, array('_created', '_modified', '_publishing', '_archiving', '_metadata', '_workflow_state'))) {
                continue;
            } else if (TRUE === property_exists($this, $property)) {
                $this->$property = $value;
            } else if (TRUE === $strict)
                throw new BBException(sprintf('Unknown property `%s` in %s.', $property, get_class($this)));
        }

        if (true === property_exists($serialized, 'publishing')) {
            $publishing = null;
            if (0 < $serialized->publishing) {
                $publishing = new \DateTime();
                $publishing->setTimestamp($serialized->publishing);
            }
            $this->setPublishing($publishing);
        }

        if (true === property_exists($serialized, 'archiving')) {
            $archiving = null;
            if (0 < $serialized->archiving) {
                $archiving = new \DateTime();
                $archiving->setTimestamp($serialized->archiving);
            }
            $this->setArchiving($archiving);
        }

        if (true === property_exists($serialized, 'metadata') && null !== $this->getMetaData()) {
            $this->setMetaData($this->getMetaData()->fromStdClass($serialized->metadata));
        }

        if (true === property_exists($serialized, 'workflow_state')) {
            if (null === $serialized->workflow_state) {
                $this->setWorkflowState(null);
            } elseif ($serialized->workflow_state instanceof State) {
                $this->setWorkflowState($serialized->workflow_state);
            }
        }

        return $this;
    }

    /**
     * Returns states except deleted
     * @codeCoverageIgnore
     * @return array
     */
    public static function getUndeletedStates()
    {
        return array(
            Page::STATE_OFFLINE,
            Page::STATE_ONLINE,
            Page::STATE_HIDDEN,
            Page::STATE_ONLINE + Page::STATE_HIDDEN
        );
    }

    /**
     * Looks for at least one online children
     * @return boolean TRUE if at least one children of the page is online
     * @deprecated
     */
    public function hasChildrenVisible()
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getState() == static::STATE_ONLINE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of the ascendants
     * @param \BackBuilder\NestedNode\Page $page
     * @param array $breadcrumb
     * @return array
     * @deprecated
     */
    public function getBreadcrumb(Page $page = null, $breadcrumb = array())
    {
        if (null === $this->_breadcrumb) {
            $page = (null !== $page) ? $page : $this;
            $breadcrumb[] = $page;
            if (null !== $page->getParent())
                return $this->getBreadcrumb($page->getParent(), $breadcrumb);
            else
                $this->_breadcrumb = $breadcrumb;
        }

        return $this->_breadcrumb;
    }

    /**
     * Returns an array of the unique identifiers of the ascendants
     * @return array
     * @deprecated
     */
    public function getBreadcrumb_uids()
    {
        $breadcrumb_uids = array();
        foreach ($this->getBreadcrumb() as $page) {
            $breadcrumb_uids[] = $page->getUid();
        }

        return array_reverse($breadcrumb_uids);
    }

}