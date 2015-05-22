<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\NestedNode;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\ContentSet;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Installer\Annotation as BB;
use BackBee\MetaData\MetaDataBag;
use BackBee\Renderer\RenderableInterface;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Workflow\State;

/**
 * Page object in BackBee.
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
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\NestedNode\Repository\PageRepository")
 * @ORM\Table(name="page",indexes={
 *     @ORM\Index(name="IDX_STATEP", columns={"state"}),
 *     @ORM\Index(name="IDX_ARCHIVING", columns={"archiving"}),
 *     @ORM\Index(name="IDX_PUBLISHING", columns={"publishing"}),
 *     @ORM\Index(name="IDX_ROOT", columns={"root_uid"}),
 *     @ORM\Index(name="IDX_PARENT", columns={"parent_uid"}),
 *     @ORM\Index(name="IDX_SELECT_PAGE",
 *        columns={"root_uid", "leftnode", "rightnode", "state", "publishing", "archiving", "modified"}),
 *        @ORM\Index(name="IDX_URL", columns={"site_uid", "url"}),
 *        @ORM\Index(name="IDX_ROOT_RIGHT", columns={"root_uid", "rightnode"})
 * })
 * @ORM\HasLifecycleCallbacks
 * @BB\Fixtures(qty=1)
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Page extends AbstractNestedNode implements RenderableInterface, DomainObjectInterface
{
    /**
     * State off-line: the page can not be displayed on the website.
     *
     * @var int
     */
    const STATE_OFFLINE = 0;

    /**
     * State on-line: the page can be displayed on the website.
     *
     * @var int
     */
    const STATE_ONLINE = 1;

    /**
     * State hidden: the page can not appeared in menus.
     *
     * @var int
     */
    const STATE_HIDDEN = 2;

    /**
     * State deleted: the page does not appear in the tree of the website.
     *
     * @var int
     */
    const STATE_DELETED = 4;

    /**
     * Type static: thez page is an stored and managed entity.
     *
     * @var int
     */
    const TYPE_STATIC = 1;

    /**
     * Type dynamic: the page is not a managed entity.
     *
     * @var int
     */
    const TYPE_DYNAMIC = 2;

    /**
     * Default target if redirect is defined.
     *
     * @var string
     */
    const DEFAULT_TARGET = '_self';

    /**
     * Unique identifier of the page.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="uid")
     * @BB\Fixtures(type="md5")
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     * @Serializer\ReadOnly
     */
    protected $_uid;

    /**
     * The owner site of this node.
     *
     * @var \BackBee\Site\Site
     * @ORM\ManyToOne(targetEntity="BackBee\Site\Site", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="site_uid", referencedColumnName="uid")
     */
    protected $_site;

    /**
     * The layout associated to the page.
     *
     * @var \BackBee\Site\Layout
     * @ORM\ManyToOne(targetEntity="BackBee\Site\Layout", inversedBy="_pages", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="layout_uid", referencedColumnName="uid")
     */
    protected $_layout;

    /**
     * The root node, cannot be NULL.
     *
     * @var \BackBee\NestedNode\Page
     * @ORM\ManyToOne(targetEntity="BackBee\NestedNode\Page", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="root_uid", referencedColumnName="uid")
     */
    protected $_root;

    /**
     * The parent node.
     *
     * @var \BackBee\NestedNode\Page
     * @ORM\ManyToOne(targetEntity="BackBee\NestedNode\Page", inversedBy="_children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The title of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="title", nullable=false)
     * @BB\Fixtures(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_title;

    /**
     * The alternate title of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="alttitle", nullable=true)
     * @BB\Fixtures(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_alttitle;

    /**
     * The URI of this page.
     *
     * @var string
     * @ORM\Column(type="string", name="url", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_url;

    /**
     * Target of this page if redirect defined.
     *
     * @var string
     * @ORM\Column(type="string", name="target", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_target;

    /**
     * Permanent redirect.
     *
     * @var string
     * @ORM\Column(type="string", name="redirect", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_redirect;

    /**
     * Metadatas associated to the page.
     *
     * @var \BackBee\MetaData\MetaDataBag
     * @ORM\Column(type="object", name="metadata", nullable=true)
     */
    protected $_metadata;

    /**
     * The associated ContentSet.
     *
     * @var \BackBee\ClassContent\ContentSet
     * @ORM\ManyToOne(targetEntity="BackBee\ClassContent\ContentSet", inversedBy="_pages", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="contentset", referencedColumnName="uid")
     */
    protected $_contentset;

    /**
     * The publication datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="date", nullable=true)
     * @BB\Fixture(type="dateTime")
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_date;

    /**
     * The state of the page.
     *
     * @var int
     * @ORM\Column(type="smallint", name="state", nullable=false)
     * @BB\Fixture(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    protected $_state;

    /**
     * The auto publishing datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="publishing", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_publishing;

    /**
     * The auto-archiving datetime.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", name="archiving", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_archiving;

    /**
     * The optional workflow state.
     *
     * @var \BackBee\Workflow\State
     * @ORM\ManyToOne(targetEntity="BackBee\Workflow\State", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="workflow_state", referencedColumnName="uid")
     */
    protected $_workflow_state;

    /**
     * Descendants nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\Page", mappedBy="_root", fetch="EXTRA_LAZY")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\Page", mappedBy="_parent", fetch="EXTRA_LAZY")
     */
    protected $_children;

    /**
     * Revisions of the current page.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="BackBee\NestedNode\PageRevision", mappedBy="_page", fetch="EXTRA_LAZY")
     */
    protected $_revisions;

    /**
     * The type of the page.
     *
     * @var int
     */
    protected $_type;

    /**
     * An array of ascendants.
     *
     * @var array
     */
    protected $_breadcrumb = null;

    /**
     * Associated array of available states for the page.
     *
     * @var array
     */
    public static $STATES = array(
        'Offline' => self::STATE_OFFLINE,
        'Online' => self::STATE_ONLINE,
        'Hidden' => self::STATE_HIDDEN,
        'Deleted' => self::STATE_DELETED,
    );

    /**
     * Utility property used on cloning page.
     *
     * @var array
     */
    public $cloning_datas;

    /**
     * Whether redirect url should be returned by getUrl() method.
     *
     * @var bool
     */
    private $_use_url_redirect = true;

    /**
     * Properties ignored while unserializing object.
     *
     * @var array
     */
    protected $_unserialized_ignored = array('_created', '_modified', '_date', '_publishing', '_archiving', '_metadata', '_workflow_state');

    /**
     * Class constructor.
     *
     * @param string $uid     The unique identifier of the page
     * @param array  $options Initial options for the page:
     *                        - title      the default title
     *                        - url        the default url
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
        $this->old_state = null;
    }

    /**
     * Magical cloning method.
     */
    public function __clone()
    {
        $current_uid = $this->_uid;

        $this->cloning_datas = array(
            'pages' => array(),
            'contents' => array(),
        );

        if ($this->_uid) {
            if (null !== $this->_contentset && null !== $this->getLayout()) {
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
     * Returns the owner site of this node.
     *
     * @return \BackBee\Site\Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Returns the main contentset associated to the node.
     *
     * @return \BackBee\ClassContent\ContentSet
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
     *
     * @return \BackBee\Site\Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the alternate title of the page.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAltTitle()
    {
        return $this->_alttitle;
    }

    /**
     * Returns the title of the page.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns the URL of the page.
     *
     * @param bool $doRedirect : if true - returns redirect url (if exists), otherwise - current page url
     *
     * @return string
     */
    public function getUrl($doRedirect = null)
    {
        if (null === $doRedirect) {
            $doRedirect = $this->_use_url_redirect;
        }

        if ($this->isRedirect() && $doRedirect) {
            return $this->getRedirect();
        }

        return $this->_url;
    }

    /**
     * Returns the URL with extension of the page.
     *
     * @return string
     */
    public function getNormalizeUri()
    {
        if (null === $this->getSite()) {
            return $this->getUrl();
        }

        return $this->getUrl().$this->getSite()->getDefaultExtension();
    }

    /**
     * Returns the target.
     *
     * @return string
     */
    public function getTarget()
    {
        return ((null === $this->_target) ? self::DEFAULT_TARGET : $this->_target);
    }

    /**
     * Returns the premanent redirect URL if defined.
     *
     * @return string|NULL
     * @codeCoverageIgnore
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * Determines if page is a redirect.
     *
     * @return bool
     */
    public function isRedirect()
    {
        return null !== $this->_redirect;
    }

    /**
     * Returns the associated metadata if defined.
     *
     * @return \BackBee\MetaData\MetaDataBag|NULL
     * @codeCoverageIgnore
     */
    public function getMetaData()
    {
        return $this->_metadata;
    }

    /**
     * Returns the state of the page.
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Returns the date.
     *
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Returns the publishing date if defined.
     *
     * @return \DateTime|NULL
     * @codeCoverageIgnore
     */
    public function getPublishing()
    {
        return $this->_publishing;
    }

    /**
     * Returns the archiving date if defined.
     *
     * @return \DateTime|NULL
     * @codeCoverageIgnore
     */
    public function getArchiving()
    {
        return $this->_archiving;
    }

    /**
     * Returns the collection of revisions.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string $var
     *
     * @return string|array|null
     */
    public function getData($var = null)
    {
        $data = $this->toArray();

        if (null !== $var) {
            if (false === array_key_exists($var, $data)) {
                return;
            }

            return $data[$var];
        }

        return $data;
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string $var
     *
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel(),
        );

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns the worflow state if defined, NULL otherwise.
     *
     * @return \BackBee\Workflow\State
     * @codeCoverageIgnore
     */
    public function getWorkflowState()
    {
        return $this->_workflow_state;
    }

    /**
     * Returns TRUE if the page can be rendered.
     *
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return $this->isOnline();
    }

    /**
     * Is the publishing state of the page is scheduled ?
     *
     * @return Boolean TRUE if the publishing state is scheduled, FALSE otherwise
     */
    public function isScheduled()
    {
        return (null !== $this->getPublishing() || null !== $this->getArchiving());
    }

    /**
     * Is the page is visible (ie online and not hidden) ?
     *
     * @return Boolean TRUE if the page is visible, FALSE otherwise
     */
    public function isVisible()
    {
        return ($this->isOnline() && !($this->getState() & self::STATE_HIDDEN));
    }

    /**
     * Is the page online ?
     *
     * @param Boolean $ignoreSchedule
     *
     * @return Boolean TRUE if the page is online, FALSE otherwise
     */
    public function isOnline($ignoreSchedule = false)
    {
        $onlineByState = ($this->getState() & self::STATE_ONLINE) && !($this->getState() & self::STATE_DELETED);

        if (true === $ignoreSchedule) {
            return $onlineByState;
        } else {
            return $onlineByState
                && (null === $this->getPublishing() || 0 === $this->getPublishing()->diff(new \DateTime())->invert)
                && (null === $this->getArchiving() || 1 === $this->getArchiving()->diff(new \DateTime())->invert)
            ;
        }
    }

    /**
     * Is the page deleted ?
     *
     * @return Boolean TRUE if the page has been deleted
     */
    public function isDeleted()
    {
        return 0 < ($this->getState() & self::STATE_DELETED);
    }

    /**
     * Is the page is static ?
     *
     * @return boolean
     * @codeCoverageIgnore
     */
    public function isStatic()
    {
        return (Page::TYPE_STATIC === $this->_type);
    }

    /**
     * Sets the associated site.
     *
     * @param \BackBee\NestedNode\Site $site
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setSite(Site $site = null)
    {
        $this->_site = $site;

        return $this;
    }

    /**
     * Sets the main contentset associated to the node.
     * @param  \BackBee\ClassContent\ContentSet $contentset
     * @return \BackBee\NestedNode\AbstractNestedNode
     */
    public function setContentset(ContentSet $contentset)
    {
        $this->_contentset = $contentset;

        return $this;
    }

    /**
     * Sets the date of the page.
     *
     * @param \DateTime $date
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setDate(\DateTime $date = null)
    {
        $this->_date = $date;

        return $this;
    }

    /**
     * Sets the layout for the page.
     * Adds as much ContentSet to the page main ContentSet than defined zones in layout
     *
     * @param  \BackBee\Site\Layout                $layout
     * @param  \BackBee\ClassContent\AbstractClassContent $toPushInMainZone
     * @return \BackBee\NestedNode\Page
     */
    public function setLayout(Layout $layout, AbstractClassContent $toPushInMainZone = null)
    {
        $this->_layout = $layout;

        $count = count($layout->getZones());
        // Add as much ContentSet to the page main ContentSet than defined zones in layout
        for ($i = $this->getContentSet()->count(); $i < $count; $i++) {
            // Do this case really exists ?
            if (null === $zone = $layout->getZone($i)) {
                $this->getContentSet()->push(new ContentSet());
                continue;
            }

            // Create a new column
            $contentset = new ContentSet(null, $zone->options);

            if (null !== $toPushInMainZone && true === $zone->mainZone) {
                // Existing content push in the main zone
                $contentset->push($toPushInMainZone->setMainNode($this));
            } elseif ('inherited' === $zone->defaultClassContent) {
                // Inherited zone => same ContentSet than parent if exist
                $contentset = $this->getInheritedContent($i, $contentset);
            } elseif ($zone->defaultClassContent) {
                // New default content push
                $contentset->push($this->createNewDefaultContent('BackBee\ClassContent\\'.$zone->defaultClassContent, $zone->mainZone));
            }

            $this->getContentSet()->push($contentset);
        }

        return $this;
    }

    /**
     * Sets the alternate title of the page.
     *
     * @param string $alttitle
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setAltTitle($alttitle)
    {
        $this->_alttitle = $alttitle;

        return $this;
    }
    /**
     * Sets the title of the page.
     *
     * @param string $title
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * Sets the URL of the page.
     *
     * @param string $url
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setUrl($url)
    {
        $this->_url = $url;

        return $this;
    }

    /**
     * Sets the target if a permanent redirect is defined.
     *
     * @param string $target
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setTarget($target)
    {
        $this->_target = $target;

        return $this;
    }

    /**
     * Sets a permanent redirect.
     *
     * @param string $redirect
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setRedirect($redirect)
    {
        $this->_redirect = $redirect;

        return $this;
    }

    /**
     * Sets the associated metadata.
     *
     * @param \BackBee\MetaData\MetaDataBag $metadata
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setMetaData(MetaDataBag $metadata = null)
    {
        $this->_metadata = $metadata;

        return $this;
    }

    /**
     * Sets the state.
     *
     * @param int $state
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setState($state)
    {
        $state = (int) $state;
        if ($this->isRoot() && !($state & Page::STATE_ONLINE)) {
            throw new \LogicException("Root page state must be online.");
        }

        $this->_state = $state;

        return $this;
    }

    /**
     * Sets the publishing date.
     *
     * @param \DateTime $publishing
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setPublishing($publishing = null)
    {
        if ($this->isRoot() && $publishing !== null && $this->_publishing !== null) {
            throw new \LogicException("Root page is already published.");
        }
        $this->_publishing = null !== $publishing ? $this->convertTimestampToDateTime($publishing) : null;

        return $this;
    }

    /**
     * Sets the archiving date.
     *
     * @param \DateTime $archiving
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setArchiving($archiving = null)
    {
        if ($this->isRoot() && $archiving !== null) {
            throw new \LogicException("Root page can't be archived.");
        }
        $this->_archiving = null !== $archiving ? $this->convertTimestampToDateTime($archiving) : null;

        return $this;
    }

    /**
     * Sets a collection of revisions for the page.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $revisions
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setRevisions(ArrayCollection $revisions)
    {
        $this->_revision = $revisions;

        return $this;
    }

    /**
     * Sets the workflow state.
     *
     * @param \BackBee\Workflow\State $state
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setWorkflowState(State $state = null)
    {
        $this->_workflow_state = $state;

        return $this;
    }

    /**
     * Returns the inherited zone according to the provided ContentSet.
     *
     * @param \BackBee\ClassContent\ContentSet $contentSet
     *
     * @return \StdClass|null The inherited zone if found
     */
    public function getInheritedContensetZoneParams(ContentSet $contentSet)
    {
        $zone = null;

        if (
            null === $this->getLayout()
            || null === $this->getParent()
            || false === is_array($this->getLayout()->getZones())
        ) {
            return $zone;
        }

        $layoutZones = $this->getLayout()->getZones();
        $count = $this->getParent()->getContentSet()->count();
        for ($i = 0; $i < $count; $i++) {
            $parentContentset = $this->getParent()->getContentSet()->item($i);

            if ($contentSet->getUid() === $parentContentset->getUid()) {
                $zone = $layoutZones[$i];
            }
        }

        return $zone;
    }

    /**
     * Returns the index of the provided ContentSet in the main ContentSetif found, false otherwise.
     *
     * @param \BackBee\ClassContent\ContentSet $contentSet
     *
     * @return int|bool
     */
    public function getRootContentSetPosition(ContentSet $contentSet)
    {
        return $this->getContentSet()->indexOfByUid($contentSet, true);
    }

    /**
     * Returns the parent ContentSet in the same zone, false if it is not found.
     * @param  \BackBee\ClassContent\ContentSet      $contentSet
     * @return \BackBee\ClassContent\ContentSet|false
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
     * Tells which "rootContentset" is inherited from currentpage's parent.
     *
     * @param type $uidOnly
     *
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
                if ($result) {
                    $inheritedZones[$currentpageRootZone->getUid()] = $currentpageRootZone;
                }
            }
            if ($uidOnly) {
                $inheritedZones = array_keys($inheritedZones);
            }
        }

        return $inheritedZones;
    }

    /**
     * Returns the main zones of the page
     * Page's mainzone can't be unlinked.
     *
     * @return array
     */
    public function getPageMainZones()
    {
        $result = array();

        if (null === $this->getLayout()) {
            return $result;
        }

        $currentpageRootZones = $this->getContentSet();
        $layoutZones = $this->getLayout()->getZones();
        $count = count($layoutZones);
        for ($i = 0; $i < $count; $i++) {
            $zoneInfos = $layoutZones[$i];
            $currentZone = $currentpageRootZones->item($i);

            if (
                null !== $currentZone
                && null !== $zoneInfos
                && true === property_exists($zoneInfos, 'mainZone')
                && true === $zoneInfos->mainZone
            ) {
                $result[$currentZone->getUid()] = $currentZone;
            }
        }

        return $result;
    }

    /**
     * Is the ContentSet is linked to his parent.
     *
     * @param \BackBee\ClassContent\ContentSet $contentset
     *
     * @return Boolean
     */
    public function isLinkedToHisParentBy(ContentSet $contentset = null)
    {
        if (
            null !== $contentset &&
            true === array_key_exists($contentset->getUid(), $this->getInheritedZones())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Replaces the ContentSet of the page.
     *
     * @param \BackBee\ClassContent\ContentSet $contentToReplace
     * @param \BackBee\ClassContent\ContentSet $newContentSet
     * @param Boolean                          $checkContentsLinkToParent
     *
     * @return \BackBee\ClassContent\ContentSet
     */
    public function replaceRootContentSet(ContentSet $contentToReplace, ContentSet $newContentSet, $checkContentsLinkToParent = true)
    {
        $checkContentsLinkToParent = (true === is_bool($checkContentsLinkToParent)) ? $checkContentsLinkToParent : false;
        $contentIsLinked = (true === $checkContentsLinkToParent) ? $this->isLinkedToHisParentBy($contentToReplace) : true;

        if (true === $contentIsLinked) {
            if (null !== $this->getContentSet()) {
                $this->getContentSet()->replaceChildBy($contentToReplace, $newContentSet);
            }
        }

        return $newContentSet;
    }

    /**
     * Returns an array representation of the page.
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();

        $result['siteuid'] = (null !== $this->getSite()) ? $this->getSite()->getUid() : null;
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
     * Constructs the node from a string or object.
     *
     * @param mixed $serialized The string representation of the object.
     *
     * @return \BackBee\NestedNode\AbstractNestedNode
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if the serialized data can not be decode or,
     *                                                     with strict mode, if a property does not exists
     */
    public function unserialize($serialized, $strict = false)
    {
        if (false === is_object($serialized)) {
            if (null === $serialized = json_decode($serialized)) {
                throw new InvalidArgumentException('The serialized value can not be unserialized to node object.');
            }
        }

        parent::unserialize($serialized, $strict);

        if (true === property_exists($serialized, 'date')) {
            $this->setDateTimeValue('_date', $serialized->date);
        }

        if (true === property_exists($serialized, 'publishing')) {
            $this->setDateTimeValue('_publishing', $serialized->publishing);
        }

        if (true === property_exists($serialized, 'archiving')) {
            $this->setDateTimeValue('_archiving', $serialized->archiving);
        }

        if (true === property_exists($serialized, 'metadata')) {
            $meta = new MetaDataBag();
            $meta->fromStdClass((object) $serialized->metadata);
            $this->setMetaData($meta);
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
     * Returns states except deleted.
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public static function getUndeletedStates()
    {
        return array(
            Page::STATE_OFFLINE,
            Page::STATE_ONLINE,
            Page::STATE_HIDDEN,
            Page::STATE_ONLINE + Page::STATE_HIDDEN,
        );
    }

    /**
     * Looks for at least one online children.
     *
     * @return boolean TRUE if at least one children of the page is online
     *
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
     * Returns an array of the ascendants.
     *
     * @param \BackBee\NestedNode\Page $page
     * @param array                    $breadcrumb
     *
     * @return array
     *
     * @deprecated
     */
    public function getBreadcrumb(Page $page = null, $breadcrumb = array())
    {
        if (null === $this->_breadcrumb) {
            $page = (null !== $page) ? $page : $this;
            $breadcrumb[] = $page;
            if (null !== $page->getParent()) {
                return $this->getBreadcrumb($page->getParent(), $breadcrumb);
            } else {
                $this->_breadcrumb = $breadcrumb;
            }
        }

        return $this->_breadcrumb;
    }

    /**
     * Returns an array of the unique identifiers of the ascendants.
     *
     * @return array
     *
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

    /**
     * Tells whether getUrl() should return the redirect url or BB5 url.
     *
     * @param bool $useUrlRedirect
     *
     * @return \BackBee\NestedNode\Page
     */
    public function setUseUrlRedirect($useUrlRedirect)
    {
        $this->_use_url_redirect = $useUrlRedirect;

        return $this;
    }

    /**
     * Should getUrl() return the redirect url or bb5 url ?
     *
     * @return bool
     */
    public function getUseUrlRedirect()
    {
        return $this->_use_url_redirect;
    }

    /**
     * Returns default template name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return str_replace(array("BackBee".NAMESPACE_SEPARATOR."NestedNode".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("layout_uid")
     */
    public function getLayoutUid()
    {
        return null !== $this->getlayout() ? $this->getlayout()->getUid() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("layout_label")
     */
    public function getLayoutLabel()
    {
        return null !== $this->getlayout() ? $this->getlayout()->getLabel() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_uid")
     */
    public function getSiteUid()
    {
        return null !== $this->_site ? $this->_site->getUid() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("site_label")
     */
    public function getSiteLabel()
    {
        return null !== $this->_site ? $this->_site->getLabel() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("states")
     */
    public function getStates()
    {
        $states = [];

        if (self::STATE_OFFLINE === $this->_state) {
            $states[] = self::STATE_OFFLINE;
        } elseif (self::STATE_HIDDEN === $this->_state) {
            $states[] = self::STATE_OFFLINE;
            $states[] = self::STATE_HIDDEN;
        } else {
            foreach (self::$STATES as $value) {
                if (0 !== ($this->_state & $value)) {
                    $states[] = $value;
                }
            }
        }

        return $states;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflow_uid")
     */
    public function getWorkflowStateUid()
    {
        return null !== $this->_workflow_state ? $this->_workflow_state->getUid() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("workflow_label")
     */
    public function getWorkflowStateLabel()
    {
        return null !== $this->_workflow_state ? $this->_workflow_state->getLabel() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("string")
     */
    public function getStateCode()
    {
        $code = $this->isOnline(true) ? '1' : '0';
        $code .= null !== $this->_workflow_state
            ? '_'.$this->_workflow_state->getCode()
            : ''
        ;

        return $code;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("boolean")
     */
    public function isHidden()
    {
        return 0 !== ($this->getState() & self::STATE_HIDDEN);
    }

    /**
     * Convert provided date to DateTime.
     *
     * @param mixed date the date to convert to \DateTime
     *
     * @throws InvalidArgumentException raises if provided date is not an integer or an instance of \DateTime
     *
     * @return DateTime
     */
    private function convertTimestampToDateTime($date)
    {
        if (false === ($date instanceof \DateTime) && false === is_int($date)) {
            throw new InvalidArgumentException(
                'Page::convertTimestampToDateTime() expect date argument to be an integer or an instance of \DateTime'
            );
        } elseif (is_int($date)) {
            $date = new \DateTime(date('c', $date));
        }

        return $date;
    }

    /**
     * Assign DateTime object to a property giving a time stamp.
     *
     * @param string $property
     * @param int    $timestamp
     *
     * @return \BackBee\NestedNode\Page
     */
    private function setDateTimeValue($property, $timestamp = null)
    {
        $date = null;
        if (null !== $timestamp && 0 < $timestamp) {
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
        }

        $this->$property = $date;

        return $this;
    }

    /**
     * Returns the inherited content from parent, $default if not found.
     *
     * @param int                                 $index
     * @param  \BackBee\ClassContent\AbstractClassContent $default
     * @return \BackBee\ClassContent\AbstractClassContent
     */
    private function getInheritedContent($index, AbstractClassContent $default)
    {
        if (
                null !== $this->getParent() &&
                $index < $this->getParent()->getContentSet()->count() &&
                null !== $this->getParent()->getContentSet()->item($index)
        ) {
            return $this->getParent()->getContentSet()->item($index);
        }

        return $default;
    }

    /**
     * Creates a new default content to be pushed in layout columns.
     *
     * @param string  $classname
     * @param boolean $mainzone
     *
     * @return \BackBee\ClassContent\AbstractClassContent
     */
    private function createNewDefaultContent($classname, $mainzone = false)
    {
        $content = new $classname();
        if (null !== $content->getProperty('labelized-by')) {
            try {
                $label = $content;
                foreach (explode('->', (string) $label->getProperty('labelized-by')) as $property) {
                    if (is_object($label->$property)) {
                        $label = $label->$property;
                    } else {
                        break;
                    }
                }

                $label->$property = $this->getTitle();
            } catch (\Exception $e) {}
        }

        if (true === $mainzone) {
            $content->setMainNode($this);
        }

        return $content;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("boolean")
     */
    public function hasChildren()
    {
        return parent::hasChildren();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("created")
     */
    public function getCreatedTimestamp()
    {
        return $this->_created ? $this->_created->format('U') : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("modified")
     */
    public function getModifiedTimestamp()
    {
        return $this->_modified ? $this->_modified->format('U') : null;
    }
}
