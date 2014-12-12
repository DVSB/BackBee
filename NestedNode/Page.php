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

use BackBuilder\Security\Acl\Domain\AObjectIdentifiable;
use BackBuilder\ClassContent\AClassContent;
use BackBuilder\ClassContent\ContentSet;
use BackBuilder\Exception\InvalidArgumentException;
use BackBuilder\MetaData\MetaDataBag;
use BackBuilder\Renderer\IRenderable;
use BackBuilder\Site\Layout;
use BackBuilder\Site\Site;
use BackBuilder\Workflow\State;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
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
 * @author      Micael Malta <mmalta@nextinteractive.fr>
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\PageRepository")
 * @Table(name="page",indexes=
 * {
 * @index(columns={"state"}),
 * @index(columns={"level", "state", "publishing", "archiving", "modified"}),
 * @index(columns={"url"}),
 * @index(columns={"modified"}),
 * }
 * )
 * @HasLifecycleCallbacks
 * @fixtures(qty=1)
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Page extends AObjectIdentifiable implements IRenderable, DomainObjectInterface
{

    /**
     * State off-line: the page can not be displayed on the website
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
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     * @Serializer\ReadOnly
     */
    protected $_uid;

    /**
     * The layout associated to the page
     * @var \BackBuilder\Site\Layout
     * @ManyToOne(targetEntity="BackBuilder\Site\Layout", inversedBy="_pages", fetch="EXTRA_LAZY")
     * @JoinColumn(name="layout_uid", referencedColumnName="uid", nullable=false)
     */
    protected $_layout;

    /**
     * The title of this page
     * @var string
     * @Column(type="string", name="title", nullable=false)
     * @fixture(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_title;

    /**
     * The alternate title of this page
     * @var string
     * @Column(type="string", name="alttitle", nullable=true)
     * @fixture(type="sentence", value=6)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_alttitle;

    /**
     * The URI of this page
     * @var string
     * @Column(type="string", name="url", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_url;

    /**
     * Target of this page if redirect defined.
     * @var string
     * @column(type="string", name="target", nullable=false)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    protected $_target;

    /**
     * Permanent redirect.
     * @var string
     * @column(type="string", name="redirect", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("string")
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
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\ContentSet", inversedBy="_pages", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @JoinColumn(name="contentset", referencedColumnName="uid")
     */
    protected $_contentset;

    /**
     * The publication datetime
     * @var \DateTime
     * @Column(type="datetime", name="date", nullable=true)
     * @fixture(type="dateTime")
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_date;

    /**
     * The state of the page
     * @var int
     * @Column(type="smallint", name="state", nullable=false)
     * @fixture(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    protected $_state;

    /**
     * The auto publishing datetime
     * @var \DateTime
     * @Column(type="datetime", name="publishing", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_publishing;

    /**
     * The auto-archiving datetime
     * @var \DateTime
     * @Column(type="datetime", name="archiving", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Type("DateTime<'U'>")
     */
    protected $_archiving;

    /**
     * The optional workflow state.
     * @var \BackBuilder\Workflow\State
     * @ManyToOne(targetEntity="BackBuilder\Workflow\State", fetch="EXTRA_LAZY")
     * @JoinColumn(name="workflow_state", referencedColumnName="uid")
     */
    protected $_workflow_state;

    /**
     * Revisions of the current page
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\NestedNode\PageRevision", mappedBy="_page", fetch="EXTRA_LAZY")
     */
    protected $_revisions;

    /**
     * The nested node level in the tree.
     * @var int
     * @Column(type="integer", name="level", nullable=false)
     */
    protected $_level;

    /**
     * The order position in the section.
     * @var int
     * @Column(type="integer", name="position", nullable=false)
     */
    protected $_position;

    /**
     * The creation datetime
     * @var \DateTime
     * @Column(type="datetime", name="created", nullable=false)
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var \DateTime
     * @Column(type="datetime", name="modified", nullable=false)
     */
    protected $_modified;

    /**
     * The section node.
     * @var \BackBuilder\NestedNode\Section
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Section", inversedBy="_pages", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @JoinColumn(name="section_uid", referencedColumnName="uid", nullable=false)
     */
    public $_section;

    /**
     * The associated page of this section
     * @var \BackBuilder\NestedNode\Section
     * @OneToOne(targetEntity="BackBuilder\NestedNode\Section", mappedBy="_page", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $_mainsection;

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
     * old state of current object (equals to null if it's not updated);
     * this property is not persisted
     * @var integer
     */
    public $old_state;

    /**
     * Whether redirect url should be returned by getUrl() method
     *
     * @var bool
     */
    private $_use_url_redirect = true;

    /**
     * Properties ignored while unserializing object
     * @var array
     */
    protected $_unserialized_ignored = array('_section', '_created', '_modified', '_date', '_publishing', '_archiving', '_metadata', '_workflow_state');

    /**
     * Class constructor
     * @param string $uid The unique identifier of the page
     * @param array $options Initial options for the page:
     *                         - main_section   the main section associated to the page
     *                         - title          the default title
     *                         - url            the default url
     */
    public function __construct($uid = null, $options = null)
    {
        $default_values = array_merge(array('main_section' => null, 'title' => null, 'url' => null), (array) $options);
        $this->setDefaultProperties($uid, $default_values['main_section'], $default_values['title'], $default_values['url']);

        $this->_contentset = new ContentSet();
        $this->_revisions = new ArrayCollection();
    }

    /**
     * Sets the default values to properties
     * @param string $uid
     * @param \BackBuilder\NestedNode\Section $section
     * @param string $title
     * @param string $url
     * @return \BackBuilder\NestedNode\Page
     */
    private function setDefaultProperties($uid = null, Section $main_section = null, $title = null, $url = null, $target = self::DEFAULT_TARGET)
    {
        $this->_state = Page::STATE_HIDDEN;
        $this->_type = Page::TYPE_DYNAMIC;
        $this->_target = $target;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_title = $title;
        $this->_url = $url;

        if (null === $main_section) {
            $main_section = new Section($uid, array('page' => $this));
        }
        $this->setMainSection($main_section);
        $this->_uid = $main_section->getUid();

        return $this;
    }

    /**
     * Magical cloning method
     */
    public function __clone()
    {
        $source_uid = $this->_uid;
        $this->cloning_datas = array(
            'pages' => array(),
            'contents' => array()
        );

        if (true === $this->hasMainSection()) {
            // Main section has to be cloned also
            $this->setDefaultProperties(null, clone $this->_mainsection, $this->_title, $this->_url, $this->_target);
        } else {
            // The new page keeps the same section
            $section = $this->getSection();
            $this->setDefaultProperties(null, null, $this->_title, $this->_url, $this->_target);
            $this->setSection($section);
        }

        if (null !== $this->_contentset && null !== $this->getLayout()) {
            $this->_contentset = $this->_contentset->createClone($this);
        } else {
            $this->_contentset = new ContentSet();
        }

        $this->_revisions = new ArrayCollection();

        $this->cloning_datas['pages'][$source_uid] = $this;
    }

    /**
     * Returns the owner site of this node.
     * @return \Backbuilder\Site\Site
     * @codeCoverageIgnore
     */
    public function getSite()
    {
        return $this->getSection()->getSite();
    }

    /**
     * Returns the main contentset associated to the node.
     * @return \BackBuilder\ClassContent\ContentSet
     * @codeCoverageIgnore
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
     * @return \BackBuilder\Site\Layout
     * @codeCoverageIgnore
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns the alternate title of the page.
     * @return string
     * @codeCoverageIgnore
     */
    public function getAltTitle()
    {
        return $this->_alttitle;
    }

    /**
     * Returns the title of the page.
     * @return string
     * @codeCoverageIgnore
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns the URL of the page.
     * @params bool $doRedirect : if true - returns redirect url (if exists), otherwise - current page url
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
     * Returns the URL with extension of the page
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
     * @return string
     */
    public function getTarget()
    {
        return ((null === $this->_target) ? self::DEFAULT_TARGET : $this->_target);
    }

    /**
     * Returns the premanent redirect URL if defined
     * @return string|NULL
     * @codeCoverageIgnore
     */
    public function getRedirect()
    {
        return $this->_redirect;
    }

    /**
     * Determines if page is a redirect
     * @return bool
     */
    public function isRedirect()
    {
        return null !== $this->_redirect;
    }

    /**
     * Returns the associated metadata if defined
     * @return \BackBuilder\MetaData\MetaDataBag|NULL
     * @codeCoverageIgnore
     */
    public function getMetaData()
    {
        return $this->_metadata;
    }

    /**
     * Returns the state of the page.
     * @return int
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Returns the date
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * Returns the publishing date if defined.
     * @return \DateTime|NULL
     * @codeCoverageIgnore
     */
    public function getPublishing()
    {
        return $this->_publishing;
    }

    /**
     * Returns the archiving date if defined.
     * @return \DateTime|NULL
     * @codeCoverageIgnore
     */
    public function getArchiving()
    {
        return $this->_archiving;
    }

    /**
     * Returns the collection of revisions.
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided
     * @param  string            $var
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
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided
     * @param  string            $var
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel(),
            'position' => $this->getPosition()
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
     * @codeCoverageIgnore
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
        return ($this->isOnline() && !($this->getState() & self::STATE_HIDDEN));
    }

    /**
     * Is the page online ?
     * @param  Boolean $ignoreSchedule
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
     * @return Boolean TRUE if the page has been deleted
     */
    public function isDeleted()
    {
        return 0 < ($this->getState() & self::STATE_DELETED);
    }

    /**
     * Is the page is static ?
     * @return boolean
     * @codeCoverageIgnore
     */
    public function isStatic()
    {
        return (Page::TYPE_STATIC === $this->_type);
    }

    /**
     * Sets the associated site
     * @param  \BackBuilder\NestedNode\Site $site
     * @return \BackBuilder\NestedNode\Page
     */
    public function setSite(Site $site = null)
    {
        $this->getSection()->setSite($site);
        return $this;
    }

    /**
     * Sets the main contentset associated to the node.
     * @param  \BackBuilder\ClassContent\ContentSet $contentset
     * @return \BackBuilder\NestedNode\ANestedNode
     */
    public function setContentset(ContentSet $contentset)
    {
        $this->_contentset = $contentset;

        return $this;
    }

    /**
     * Sets the date of the page
     * @param  \DateTime                    $date
     * @return \BackBuilder\NestedNode\Page
     */
    public function setDate(\DateTime $date = null)
    {
        $this->_date = $date;

        return $this;
    }

    /**
     * Sets the layout for the page.
     * Adds as much ContentSet to the page main ContentSet than defined zones in layout
     * @param  \BackBuilder\Site\Layout                $layout
     * @param  \BackBuilder\ClassContent\AClassContent $toPushInMainZone
     * @return \BackBuilder\NestedNode\Page
     */
    public function setLayout(Layout $layout, AClassContent $toPushInMainZone = null)
    {
        $this->_layout = $layout;

        $count = count($layout->getZones());
        // Add as much ContentSet to the page main ContentSet than defined zones in layout
        for ($i = $this->getContentSet()->count(); $i < $count; $i++) {
            // Do this case really exist ?
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
                $contentset->push($this->createNewDefaultContent('BackBuilder\ClassContent\\'.$zone->defaultClassContent, $zone->mainZone));
            }

            $this->getContentSet()->push($contentset);
        }

        return $this;
    }

    /**
     * Sets the alternate title of the page.
     * @param  string                       $alttitle
     * @return \BackBuilder\NestedNode\Page
     */
    public function setAltTitle($alttitle)
    {
        $this->_alttitle = $alttitle;

        return $this;
    }

    /**
     * Sets the title of the page.
     * @param  string                       $title
     * @return \BackBuilder\NestedNode\Page
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * Sets the URL of the page
     * @param  string                       $url
     * @return \BackBuilder\NestedNode\Page
     */
    public function setUrl($url)
    {
        $this->_url = $url;

        return $this;
    }

    /**
     * Sets the target if a permanent redirect is defined
     * @param  string                       $target
     * @return \BackBuilder\NestedNode\Page
     */
    public function setTarget($target)
    {
        $this->_target = $target;

        return $this;
    }

    /**
     * Sets a permanent redirect
     * @param  string                       $redirect
     * @return \BackBuilder\NestedNode\Page
     */
    public function setRedirect($redirect)
    {
        $this->_redirect = $redirect;

        return $this;
    }

    /**
     * Sets the associated metadata
     * @param  \BackBuilder\MetaData\MetaDataBag $metadata
     * @return \BackBuilder\NestedNode\Page
     */
    public function setMetaData(MetaDataBag $metadata = null)
    {
        $this->_metadata = $metadata;

        return $this;
    }

    /**
     * Sets the state
     * @param  int                          $state
     * @return \BackBuilder\NestedNode\Page
     */
    public function setState($state)
    {
        $this->_state = $state;

        return $this;
    }

    /**
     * Sets the publishing date
     * @param  \DateTime                    $publishing
     * @return \BackBuilder\NestedNode\Page
     */
    public function setPublishing($publishing = null)
    {
        $this->_publishing = null !== $publishing ? $this->convertTimestampToDateTime($publishing) : null;

        return $this;
    }

    /**
     * Sets the archiving date
     * @param  \DateTime                    $archiving
     * @return \BackBuilder\NestedNode\Page
     */
    public function setArchiving($archiving = null)
    {
        $this->_archiving = null !== $archiving ? $this->convertTimestampToDateTime($archiving) : null;

        return $this;
    }

    /**
     * Sets a collection of revisions for the page
     * @param  \Doctrine\Common\Collections\ArrayCollection $revisions
     * @return \BackBuilder\NestedNode\Page
     */
    public function setRevisions(ArrayCollection $revisions)
    {
        $this->_revision = $revisions;

        return $this;
    }

    /**
     * Sets the workflow state
     * @param  \BackBuilder\Workflow\State  $state
     * @return \BackBuilder\NestedNode\Page
     */
    public function setWorkflowState(State $state = null)
    {
        $this->_workflow_state = $state;

        return $this;
    }

    /**
     * Returns the inherited zone according to the provided ContentSet
     * @param  \BackBuilder\ClassContent\ContentSet $contentSet
     * @return \StdClass|NULL                       The inherited zone if found
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
     * Returns the index of the provided ContentSet in the main ContentSetif found, FALSE otherwise
     * @param  \BackBuilder\ClassContent\ContentSet $contentSet
     * @return int|FALSE
     */
    public function getRootContentSetPosition(ContentSet $contentSet)
    {
        return $this->getContentSet()->indexOfByUid($contentSet, true);
    }

    /**
     * Returns the parent ContentSet in the same zone, FALSE if it is not found
     * @param  \BackBuilder\ClassContent\ContentSet      $contentSet
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
     * @param  type  $uidOnly
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
     * Page's mainzone can't be unlinked
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
     * Is the ContentSet is linked to his parent
     * @param  \BackBuilder\ClassContent\ContentSet $contentset
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
     * Replaces the ContentSet of the page
     * @param  \BackBuilder\ClassContent\ContentSet $contentToReplace
     * @param  \BackBuilder\ClassContent\ContentSet $newContentSet
     * @param  Boolean                              $checkContentsLinkToParent
     * @return \BackBuilder\ClassContent\ContentSet
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
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => 'node_' . $this->getUid(),
            'rel' => (true === $this->isLeaf()) ? 'leaf' : 'folder',
            'uid' => $this->getUid(),
            'rootuid' => $this->getRoot()->getUid(),
            'parentuid' => (null !== $this->getParent()) ? $this->getParent()->getUid() : null,
            'created' => $this->getCreated()->getTimestamp(),
            'modified' => $this->getModified()->getTimestamp(),
            'isleaf' => $this->isLeaf(),
            'siteuid' => (null !== $this->getSite()) ? $this->getSite()->getUid() : null,
            'title' => $this->getTitle(),
            'alttitle' => $this->getAltTitle(),
            'url' => $this->getUrl(),
            'target' => $this->getTarget(),
            'redirect' => $this->getRedirect(),
            'state' => $this->getState(),
            'date' => (null !== $this->getDate()) ? $this->getDate()->getTimestamp() : null,
            'publishing' => (null !== $this->getPublishing()) ? $this->getPublishing()->getTimestamp() : null,
            'archiving' => (null !== $this->getArchiving()) ? $this->getArchiving()->getTimestamp() : null,
            'metadata' => (null !== $this->getMetaData()) ? $this->getMetaData()->toArray() : null,
            'layout_uid' => (null !== $this->getLayout()) ? $this->getLayout()->getUid() : null,
            'workflow_state' => (null !== $this->getWorkflowState()) ? $this->getWorkflowState()->getCode() : null,
            'section' => $this->hasMainSection()
        );
    }

    /**
     * Returns a string representation of page
     * @return string
     * @codeCoverageIgnore
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        foreach ($this->toArray() as $key => $value) {
            $serialized->$key = $value;
        }

        return json_encode($serialized);
    }

    /**
     * Constructs the page from a string or object
     * @param mixed $serialized The string representation of the object.
     * @return \BackBuilder\NestedNode\Page
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the serialized data can not be decode
     */
    public function unserialize($serialized)
    {
        if (false === is_object($serialized)) {
            if (null === $serialized = json_decode($serialized)) {
                throw new InvalidArgumentException('The serialized value can not be unserialized to node object.');
            }
        }

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;
            if (true === in_array($property, $this->_unserialized_ignored)) {
                continue;
            } else if (true === property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        $this->setSection($this->getSection()->unserialize($serialized, false));

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
            Page::STATE_ONLINE + Page::STATE_HIDDEN,
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
     * @param  \BackBuilder\NestedNode\Page $page
     * @param  array                        $breadcrumb
     * @return array
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

    /**
     * old_state property getter
     * @return null|integer
     */
    public function getOldState()
    {
        return $this->old_state;
    }

    /**
     * old_state property setter
     * @return \BackBuilder\NestedNode\Page
     */
    public function setOldState($v)
    {
        $this->old_state = $v;

        return $this;
    }

    /**
     * Tells whether getUrl() should return the redirect url or BB5 url
     * @param  bool                         $useUrlRedirect
     * @return \BackBuilder\NestedNode\Page
     */
    public function setUseUrlRedirect($useUrlRedirect)
    {
        $this->_use_url_redirect = $useUrlRedirect;

        return $this;
    }

    /**
     * Should getUrl() return the redirect url or bb5 url ?
     * @return bool
     */
    public function getUseUrlRedirect()
    {
        return $this->_use_url_redirect;
    }

    /**
     * Returns default template name
     * @return string
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return str_replace(array("BackBuilder".NAMESPACE_SEPARATOR."NestedNode".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
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
     * @Serializer\SerializedName("state_label")
     */
    public function getStateLabel()
    {
        $states = array_flip(self::$STATES);
        $label = true === isset($states[$this->_state]) ? $states[$this->_state] : null;
        if (null === $label) {
            $labels = array();
            foreach ($states as $value => $label) {
                if (0 !== ($this->_state & $value)) {
                    $labels[] = $label;
                }
            }

            $label = implode(', ', $labels);
        }

        return $label;
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
     * Convert provided date to DateTime
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
     * Assign DateTime object to a property giving a time stamp
     * @param  string                       $property
     * @param  int                          $timestamp
     * @return \BackBuilder\NestedNode\Page
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
     * Returns the inherited content from parent, $default if not found
     * @param  int                                     $index
     * @param  \BackBuilder\ClassContent\AClassContent $default
     * @return \BackBuilder\ClassContent\AClassContent
     */
    private function getInheritedContent($index, AClassContent $default)
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
     * Creates a new default content to be pushed in layout columns
     * @param  string                                  $classname
     * @param  boolean                                 $mainzone
     * @return \BackBuilder\ClassContent\AClassContent
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
            } catch (\Exception $e) {
                // Nothing to do
            }
        }

        if (true === $mainzone) {
            $content->setMainNode($this);
        }

        return $content;
    }

    /**
     * Returns te unique identifier.
     * @return string
     * @codeCoverageIgnore
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the level
     * @return int
     * @codeCoverageIgnore
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * Returns the order position
     * @return int
     * @codeCoverageIgnore
     */
    public function getPosition()
    {
        return $this->_position;
    }

    /**
     * Returns the creation date.
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Returns the last modified date.
     * @return \DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return $this->_modified;
    }

    /**
     * Is this page has an associated section
     * @return boolean
     */
    public function hasMainSection()
    {
        return null !== $this->getMainSection();
    }

    /**
     * Return the associated main section if exists, NULL otherwise
     * @return \BackBuilder\NestedNode\Section|NULL
     * @codeCoverageIgnore
     */
    public function getMainSection()
    {
        return $this->_mainsection;
    }

    /**
     * Sets the main section for this page
     * @param \BackBuilder\NestedNode\Section $section
     * @return \BackBuilder\NestedNode\Page
     */
    public function setMainSection(Section $section)
    {
        if ($section === $this->_mainsection) {
            return $this;
        }

        $this->_mainsection = $section;
        $this->_position = 0;
        $this->_level = $section->getLevel();
        $section->setPage($this);

        return $this->setSection($section);
    }

    /**
     * Sets the section for this page
     * @param \BackBuilder\NestedNode\Section $section
     * @return \BackBuilder\NestedNode\Page
     */
    public function setSection(Section $section)
    {
        if ($section !== $this->_mainsection) {
            $this->_mainsection = null;
            $this->_position = 1;
            $this->_level = $section->getLevel() + 1;
        }

        $this->_section = $section;
        return $this;
    }

    /**
     * Returns the section of this page
     * @return \BackBuilder\NestedNode\Section
     */
    public function getSection()
    {
        if (null === $this->_section) {
            $this->setSection(new Section($this->getUid(), array('page' => $this)));
        }

        return $this->_section;
    }

    /**
     * Is the page is a leaf ?
     * @return Boolean TRUE if the node if a leaf of tree, FALSE otherwise
     */
    public function isLeaf()
    {
        return (true === $this->hasMainSection()) ? $this->getSection()->isLeaf() : true;
    }

    /**
     * Returns the root page.
     * @return \BackBuilder\NestedNode\Page
     */
    public function getRoot()
    {
        return $this->getSection()->getRoot()->getPage();
    }

    /**
     * Is the page a root ?
     * @return Boolean TRUE if the page is root of tree, FALSE otherwise
     */
    public function isRoot()
    {
        return (true === $this->hasMainSection() && null === $this->getSection()->getParent());
    }

    /**
     * Sets the parent node.
     * @param \BackBuilder\NestedNode\Page $parent
     * @return \BackBuilder\NestedNode\Page
     */
    public function setParent(Page $parent)
    {
        $this->getSection()->setParent($parent->getSection());
        return $this;
    }

    /**
     * Returns the parent page, NULL if this page is root
     * @return \BackBuilder\NestedNode\Page|NULL
     */
    public function getParent()
    {
        $section = $this->getSection();
        if (true === $this->hasMainSection()) {
            return (true === $section->isRoot()) ? null : $section->getParent()->getPage();
        }

        return $section->getPage();
    }

    /**
     * Returns the nested node left position.
     * @return int
     */
    public function getLeftnode()
    {
        return $this->getSection()->getLeftnode();
    }

    /**
     * Returns the nested node right position.
     * @return int
     */
    public function getRightnode()
    {
        return $this->getSection()->getRightnode();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Type("boolean")
     * @codeCoverageIgnore
     */
    public function hasChildren()
    {
        return $this->getSection()->hasChildren();
    }

}
