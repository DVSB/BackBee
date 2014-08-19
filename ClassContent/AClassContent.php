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

namespace BackBuilder\ClassContent;

use BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\Exception\ClassContentException;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Abstract class for content object in BackBuilder
 *
 * Basicaly every BackBuilder content extends AClassContent
 * A content is also an persistant Doctrine entity
 *
 * A content has several states :
 * 
 * * STATE_NEW : new content, revision number to 0
 * * STATE_NORMAL : last commited content
 * * STATE_LOCKED : content locked on writing
 * 
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\ClassContentRepository")
 * @Table(
 *   name="content",
 *   indexes={
 *     @index(name="IDX_MODIFIED", columns={"modified"}), 
 *     @index(name="IDX_STATE", columns={"state"}), 
 *     @index(name="IDX_NODEUID", columns={"node_uid"}), 
 *     @index(name="IDX_CLASSNAME", columns={"classname"})
 *   }
 * )
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="classname", type="string")
 * @DiscriminatorMap({"BackBuilder\ClassContent\ContentSet" = "BackBuilder\ClassContent\ContentSet"})
 */
abstract class AClassContent extends AContent
{
    /**
     * New content, revision number to 0
     * @var int
     */

    const STATE_NEW = 1000;

    /**
     * Last commited content
     * @var int
     */
    const STATE_NORMAL = 1001;

    /**
     * Content locked on writing
     * @var int
     */
    const STATE_LOCKED = 1002;

    /**
     * The many to many association between this content and its subcontent
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ManyToMany(targetEntity="BackBuilder\ClassContent\AClassContent", inversedBy="_parentcontent", cascade={"persist", "detach", "merge", "refresh"}, fetch="EXTRA_LAZY")
     * @JoinTable(name="content_has_subcontent",
     *   joinColumns={@JoinColumn(name="parent_uid", referencedColumnName="uid")},
     *   inverseJoinColumns={@JoinColumn(name="content_uid", referencedColumnName="uid")}
     * )
     */
    public $_subcontent;

    /**
     * The main nested node (page)
     * @var \BackBuilder\NestedNode\Page
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Page", fetch="EXTRA_LAZY")
     * @JoinColumn(name="node_uid", referencedColumnName="uid")
     */
    protected $_mainnode;

    /**
     * The many to many association between this content and its parent content
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ManyToMany(targetEntity="BackBuilder\ClassContent\AClassContent", mappedBy="_subcontent", fetch="EXTRA_LAZY")
     */
    protected $_parentcontent;

    /**
     * The revisions of the content
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\ClassContent\Revision", mappedBy="_content", fetch="EXTRA_LAZY")
     * @OrderBy({"_version" = "DESC"})
     */
    protected $_revisions;

    /**
     * The indexed values of elements
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="BackBuilder\ClassContent\Indexation", mappedBy="_content", cascade={"all"}, fetch="EXTRA_LAZY")
     */
    protected $_indexation;

    /**
     * The content's properties as defined in yaml file
     * @var array
     */
    protected $_properties = array();

    /**
     * Default parameters as defined in yaml file
     * @var array
     */
    protected $_defaultparameters = array();

    /**
     * Store the map associating content uid to subcontent index
     * @var array;
     */
    protected $_subcontentmap = array();

    /**
     * The optionnal personnal draft of this content
     * @var \BackBuilder\ClassContent\Revision
     */
    protected $_draft;

    /**
     * Is this content persisted
     * @var boolean
     */
    protected $_isloaded;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the content
     * @param array $options Initial options for the content:
     *                         - accept      array Acceptable class names for the value
     *                         - maxentry    int The maxentry in value
     *                         - default     array default value for datas
     */
    public function __construct($uid = null, $options = null)
    {
        parent::__construct($uid, $options);

        $this->_indexation = new ArrayCollection();
        $this->_subcontent = new ArrayCollection();
        $this->_parentcontent = new ArrayCollection();
        $this->_revisions = new ArrayCollection();
        $this->_isloaded = false;
        $this->_state = self::STATE_NEW;

        $this->_setOptions($options);
    }

    /**
     * Returns the associated page of the content if exists
     * @return \BackBuilder\NestedNode\Page|NULL
     * @codeCoverageIgnore
     */
    public function getMainNode()
    {
        return $this->_mainnode;
    }

    /**
     * Set the main page to this content
     * @param \BackBuilder\NestedNode\Page $node
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     * @codeCoverageIgnore
     */
    public function setMainNode(Page $node = null)
    {
        $this->_mainnode = $node;
        return $this;
    }

    /**
     * Returns the parent collection of the content if exists
     * @return \Doctrine\Common\Collections\ArrayCollection|NULL
     * @codeCoverageIgnore
     */
    public function getParentContent()
    {
        return $this->_parentcontent;
    }

    /**
     * Returns the collection of the subcontent if exists
     * @return \Doctrine\Common\Collections\ArrayCollection|NULL
     * @codeCoverageIgnore
     */
    public function getSubcontent()
    {
        return $this->_subcontent;
    }

    /**
     * Returns the collection of revisions of the content
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Add a new revision to the collection
     * @param \BackBuilder\ClassContent\Revision $revisions
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     * @codeCoverageIgnore
     */
    public function addRevision(Revision $revision)
    {
        $this->_revisions[] = $revision;
        return $this;
    }

    /**
     * Returns the collection of indexed values
     * @return \Doctrine\Common\Collections\ArrayCollection
     * @codeCoverageIgnore
     */
    public function getIndexation()
    {
        return $this->_indexation;
    }

    /**
     * Returns defined property of the content or all the properties
     * @param $var string the property to be return, if NULL, all properties are returned
     * @return mixed The property value or NULL if unfound
     */
    public function getProperty($var = null)
    {
        if (null == $var) {
            return $this->_properties;
        }

        if (true === isset($this->_properties[$var])) {
            return $this->_properties[$var];
        }

        return null;
    }

    /**
     * Updates a non persistent property value for the current instance
     * @param string $var the name of the property
     * @param mixed $value the value of the property
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     */
    public function setProperty($var, $value)
    {
        if (false === is_array($this->_properties)) {
            $this->_properties = array();
        }

        $this->_properties[$var] = $value;
        return $this;
    }

    /**
     * Returns the parameters as defined in Yaml
     * @return array
     * @codeCoverageIgnore
     */
    public function getDefaultParameters()
    {
        return $this->_defaultparameters;
    }

    /**
     * Returns the user draft of this content if exists
     * @return \BackBuilder\ClassContent\Revision The current draft if exists, NULL otherwise
     * @codeCoverageIgnore
     */
    public function getDraft()
    {
        return $this->_draft;
    }

    /**
     * Unsets current user draft
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     * @codeCoverageIgnore
     */
    public function releaseDraft()
    {
        $this->_draft = null;
        return $this;
    }

    /**
     * Associates an user's draft to this content
     * @param \BackBuilder\ClassContent\Revision $draft
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     * @codeCoverageIgnore
     */
    public function setDraft(Revision $draft = null)
    {
        $this->_draft = $draft;
        return $this;
    }

    /**
     * Prepares to commit an user's draft data for current content
     * @return \BackBuilder\ClassContent\AClassContent the current instance
     * @throws \BackBuilder\ClassContent\Exception\RevisionMissingException Occurs if none draft is defined
     * @throws \BackBuilder\ClassContent\Exception\RevisionConflictedException Occurs if the revision is conlicted
     * @throws \BackBuilder\ClassContent\Exception\RevisionUptodateException Occurs if the revision is already up to date
     */
    public function prepareCommitDraft()
    {
        if (null === $revision = $this->getDraft()) {
            throw new Exception\RevisionMissingException('Enable to commit: missing draft');
        }

        switch ($revision->getState()) {
            case Revision::STATE_ADDED :
            case Revision::STATE_MODIFIED :
                $revision->setRevision($revision->getRevision() + 1);
                $revision->setState(Revision::STATE_COMMITTED);

                $this->releaseDraft();

                $this->_label = $revision->getLabel();
                $this->_accept = $revision->getAccept();
                $this->_maxentry = $revision->getMaxEntry();
                $this->_minentry = $revision->getMinEntry();
                $this->_parameters = $revision->getParam();

                $this->setRevision($revision->getRevision())
                        ->setState(AClassContent::STATE_NORMAL)
                        ->addRevision($revision);

                return $this;
                break;

            case Revision::STATE_CONFLICTED :
                throw new Exception\RevisionConflictedException('Content is in conflict, please resolve or revert it');
                break;
        }

        throw new Exception\RevisionUptodateException(sprintf('Content can not be commited (state : %s)', $revision->getState()));
    }

    /**
     * Returns TRUE if the content is an entity managed by doctrine
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isLoaded()
    {
        return $this->_isloaded;
    }

    /**
     * Initialized datas on postLoad doctrine event
     * @codeCoverageIgnore
     */
    public function postLoad()
    {
        $tmp_modified = $this->_modified;
        $this->_isloaded = true;
        $this->_initData();
        parent::postLoad();
        $this->_modified = $tmp_modified;
    }

    /**
     * Alternative recursive clone method, created because of problems related to doctrine clone method
     * @param \BackBuilder\NestedNode\Page $origin_page
     * @return \BackBuilder\ClassContent\ContentSet
     */
    public function createClone(Page $origin_page = null)
    {
        $class = ClassUtils::getRealClass($this);
        $clone = new $class(null, null);
        $clone->_accept = $this->_accept;
        $clone->_maxentry = $this->_maxentry;
        $clone->_minentry = $this->_minentry;
        $clone->_parameters = $this->_parameters;
        $clone->_mainnode = $this->_mainnode;

        if (null !== $origin_page
                && true === is_array($origin_page->cloning_datas)
                && true === array_key_exists('contents', $origin_page->cloning_datas)) {
            $origin_page->cloning_datas['contents'][$this->getUid()] = $clone;
        }

        if (false === ($this instanceof ContentSet)) {
            foreach ($this->_data as $key => $values) {
                foreach ($values as $type => &$value) {
                    if (is_array($value)) {
                        $keys = array_keys($value);
                        $values = array_values($value);

                        $type = end($keys);
                        $value = end($values);
                    }

                    if (0 === strpos($type, 'BackBuilder\ClassContent')) {
                        foreach ($this->_subcontent as $subcontent) {
                            if ($subcontent->getUid() == $value) {
                                $newsubcontent = $subcontent->createClone($origin_page);
                                $clone->$key = $newsubcontent;
                                break;
                            }
                        }
                    } else {
                        $clone->$key = $value;
                    }
                }
                unset($value);
            }
        }

        return $clone;
    }

    /**
     * Returns the content revision
     * @return \BackBuilder\ClassContent\AClassContent
     * @codeCoverageIgnore
     */
    protected function _getContentInstance()
    {
        return $this;
    }

    /**
     * Sets options at the construction of a new instance
     * @param array $options Initial options for the content:
     *                         - label       the label of the content
     *                         - parameters  a set of parameters for the content
     *                         - default     array default value for datas
     * @return \BackBuilder\ClassContent\AClassContent
     */
    protected function _setOptions($options = null)
    {
        if (null !== $options) {
            $options = (array) $options;

            if (true === array_key_exists('label', $options)) {
                $this->_label = $options['label'];
            }

            if (true === array_key_exists('parameters', $options)) {
                foreach ($options['parameters'] as $key => $param) {
                    $this->_defineParam($key, $param['type'], $param['options']);
                }
            }

            if (true === array_key_exists('default', $options)) {
                $options['default'] = (array) $options['default'];
                foreach ($options['default'] as $var => $value) {
                    $this->_data[$var] = array();
                    $this->_maxentry[$var] = 1;
                    $this->_minentry[$var] = 0;
                    $this->__set($var, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Sets a collection of datas
     * Has to be overwritten by generalizations
     * @return void
     * @codeCoverageIgnore
     */
    protected function _initData()
    {
        // Has to be overwritten by generalizations
    }

    /**
     * Dynamically adds and sets new property to this content
     * @param string $var the name of the property
     * @param mixed $value the value of the property
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _defineProperty($var, $value)
    {
        if (false === array_key_exists($var, $this->_properties)) {
            $this->_properties[$var] = $value;
        }

        return $this;
    }

    /**
     * Dynamically adds and sets new element to this content
     * @param string $var the name of the element
     * @param string $type the type
     * @param array $options Initial options for the content (see this constructor)
     * @param Boolean $updateAccept dynamically accept or not the type for the new element
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _defineData($var, $type = 'scalar', $options = null, $updateAccept = true)
    {
        if (true === $updateAccept) {
            $this->_addAcceptedType($type, $var);
        }

        if (false === array_key_exists($var, $this->_data)) {
            $this->_data[$var] = array();
            $this->_maxentry[$var] = (!is_null($options) && isset($options['maxentry'])) ? $options['maxentry'] : 1;
            $this->_minentry[$var] = (!is_null($options) && isset($options['minentry'])) ? $options['minentry'] : 0;

            $values = array();
            if (true === is_array($options) && true === array_key_exists('default', $options)) {
                $values = (array) $options['default'];

                if ('scalar' !== $type) {
                    foreach ($values as &$value) {
                        $value = new $type(null, $options);
                    }
                    unset($value);
                }
            } else {
                $values[] = ('scalar' == $type) ? '' : new $type(null, $options);
            }

            return $this->__set($var, $values);
        }

        return $this;
    }

    /**
     * Dynamically add and set new parameter to this content
     * @param string $var the name of the element
     * @param string $type the type
     * @param array $options Initial options for the parameter (see this constructor)
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _defineParam($var, $type = 'scalar', $options = null)
    {
        if ('accept' === $var) {
            if (true === is_array($options) && true === array_key_exists('default', $options)) {
                return $this->_addAcceptedType($options['default']);
            }
        } else {
            $values = array();
            if (true === is_array($options) && true === array_key_exists('default', $options)) {
                $values[$type] = $options['default'];
            } else {
                $values[$type] = null;
            }

            if (false === array_key_exists($var, $this->_parameters)) {
                $this->_parameters[$var] = $values;
            }

            $this->_defaultparameters[$var] = $values;
        }

        return $this;
    }

    /**
     * Adds a new accepted type to the element
     * @param string $type the type to accept
     * @param string $var the element
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     */
    protected function _addAcceptedType($type, $var = null)
    {
        if (null === $var) {
            return $this;
        }

        if (false === array_key_exists($var, $this->_accept)) {
            $this->_accept[$var] = array();
        }

        $types = (array) $type;
        foreach ($types as $type) {
            $type = (NAMESPACE_SEPARATOR === substr($type, 0, 1)) ? substr($type, 1) : $type;
            if (false === in_array($type, $this->_accept[$var])) {
                $this->_accept[$var][] = $type;
            }
        }

        return $this;
    }

    /**
     * Adds a subcontent to the collection.
     * @param \BackBuilder\ClassContent\AClassContent $value
     * @return string the unique identifier of the add subcontent
     */
    protected function _addSubcontent(AClassContent $value)
    {
        if (false === $this->_subcontent->indexOf($value)) {
            $this->_subcontent->add($value);
        }

        $this->_subcontentmap[$value->getUid()] = $this->_subcontent->indexOf($value);

        return $value->getUid();
    }

    /**
     * Removes the association with subcontents of the element $var
     * @param string $var
     */
    protected function _removeSubcontent($var)
    {
        if (true === $this->acceptSubcontent($var)) {
            foreach ($this->_data[$var] as $type => $value) {
                if (is_array($value)) {
                    $keys = array_keys($value);
                    $values = array_values($value);

                    $type = end($keys);
                    $value = end($values);
                }

                foreach ($this->_subcontent as $subcontent) {
                    if ($subcontent->getUid() === $value) {
                        $this->_subcontent->removeElement($subcontent);
                        break;
                    }
                }
            }

            $this->_updateSubcontentMap();
        }
    }

    /**
     * Updates the associationing map between subcontent and uid
     */
    private function _updateSubcontentMap()
    {
        $this->_subcontentmap = array();

        $index = 0;
        foreach ($this->_subcontent as $subcontent) {
            $this->_subcontentmap[$subcontent->getUid()] = $index++;
        }
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Public methods overload by Revision                  */
    /*                          if a draft is defined                         */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Magical function to set value to given element
     * @param string $var The name of the element
     * @param mixed $value The value to set
     * @return \BackBuilder\ClassContent\AClassContent The current instance content
     * @throws \BackBuilder\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     * @codeCoverageIgnore
     */
    public function __set($var, $value)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__set($var, $value) : parent::__set($var, $value);
    }

    /**
     * Magical function to check the setting of an element
     * @param string $var the name of the element
     * @codeCoverageIgnore
     */
    public function __isset($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__isset($var) : parent::__isset($var);
    }

    /**
     * Magical function to unset an element
     * @param string $var The name of the element to unset
     * @codeCoverageIgnore
     */
    public function __unset($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->__unset($var) : parent::__unset($var);
    }

    /**
     * Return the label of the content
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getLabel() : parent::getLabel();
    }

    /**
     * Returns the current accepted subcontents
     * @return array
     * @codeCoverageIgnore
     */
    public function getAccept()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getAccept() : parent::getAccept();
    }

    /**
     * Returns the raw datas array of the content
     * @return array
     * @codeCoverageIgnore
     */
    public function getDataToObject()
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->getDataToObject() : parent::getDataToObject();
    }

    /**
     * Get the maxentry of the content
     * @return array
     * @codeCoverageIgnore
     */
    public function getMaxEntry()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getMaxEntry() : parent::getMaxEntry();
    }

    /**
     * Gets the minentry of the content
     * @return array
     * @codeCoverageIgnore
     */
    public function getMinEntry()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getMinEntry() : parent::getMinEntry();
    }

    /**
     * Returns the creation date of the content
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getCreated()
    {
        return NULL != $this->getDraft() ? $this->getDraft()->getCreated() : parent::getCreated();
    }

    /**
     * Returns the last modified date of the content
     * @return DateTime
     * @codeCoverageIgnore
     */
    public function getModified()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getModified() : parent::getModified();
    }

    /**
     * Returns the revision number of the content
     * @return int
     * @codeCoverageIgnore
     */
    public function getRevision()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getRevision() : parent::getRevision();
    }

    /**
     * Returns the state of the content
     * @return int
     * @codeCoverageIgnore
     */
    public function getState()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getState() : parent::getState();
    }

    /**
     * Sets one or all parameters
     * @param string $var the parameter name to set, if NULL all the parameters array wil be set
     * @param mixed $values the parameter value or all the parameters if $var is NULL
     * @param string $type the optionnal casting type of the value
     * @return \BackBuilder\ClassContent\AClassContent The current instance
     * @codeCoverageIgnore
     */
    public function setParam($var = null, $values = null, $type = null)
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->setParam($var, $values, $type) : parent::setParam($var, $values, $type);
    }

    /**
     * Checks if the element accept subcontent
     * @param string $var the element
     * @return Boolean TRUE if a subcontents are accepted, FALSE otherwise
     * @codeCoverageIgnore
     */
    public function acceptSubcontent($var)
    {
        return (null !== $this->getDraft()) ? $this->getDraft()->acceptSubcontent($var) : parent::acceptSubcontent($var);
    }

    /**
     * Returns the mode to be used by current content
     * @return string
     */
    public function getMode()
    {
        $rendermode = NULL;

        if (is_array($this->getParam('rendermode'))) {
            $rendermode = (array) $this->getParam('rendermode');
            $rendermode = array_pop($rendermode);

            if (isset($rendermode['rendertype'])) {
                switch ($rendermode['rendertype']) {
                    case 'select':
                        $rendermode = $rendermode['selected'];
                        break;
                }
            }
        }

        return $rendermode;
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of IRenderable                        */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Return the data of this content
     * @param string $var The element to be return, if NULL, all datas are returned
     * @param Boolean $forceArray Force the return as array
     * @return mixed Could be either one or array of scalar, array, AClassContent instance
     * @throws \BackBuilder\ClassContent\Exception\UnknownPropertyException Occurs when $var does not match an element
     * @throws \BackBuilder\AutoLoader\Exception\ClassNotFoundException Occurs if the class of a subcontent can not be loaded
     * @codeCoverageIgnore
     */
    public function getData($var = NULL, $forceArray = false)
    {
        return (null != $this->getDraft()) ? $this->getDraft()->getData($var, $forceArray) : parent::getData($var, $forceArray);
    }

    /**
     * Returns defined parameters
     * @param string $var The parameter to be return, if NULL, all parameters are returned
     * @param string $type The casting type of the parameter
     * @return mixed the parameter value or NULL if unfound
     * @codeCoverageIgnore
     */
    public function getParam($var = null, $type = null)
    {
        return (null != $this->getDraft()) ? $this->getDraft()->getParam($var, $type) : parent::getParam($var, $type);
    }

    /**
     * Checks for state of the content before rendering it
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return $this->getState() === AClassContent::STATE_NORMAL;
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                   Implementation of Serializable                       */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Return the serialized string of the content
     * @return string
     */
    public function serialize()
    {
        $serialized = json_decode(parent::serialize());

        $serialized->properties = $this->_arrayToStdClass($this->getProperty());
        $serialized->isdraft = (null !== $this->getDraft());
        $serialized->draftuid = $serialized->isdraft ? $this->getDraft()->getUid() : null;

        return json_encode($serialized);
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                         Deprecated methods ?                           */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * ?????????????????????
     * Return the real classname object in spite of the doctrine's proxy
     * @return string
     */
    public function getRealClass()
    {
        if (in_array('Doctrine\ORM\Proxy\Proxy', class_implements($this))) {
            $class = get_parent_class($this);
        } else {
            $class = get_class($this);
        }

        return $class;
    }

    /**
     * ?????????????????????
     * Unsets a subcontent to the current collection
     * @param \BackBuilder\ClassContent\AClassContent $subContent
     * @return \BackBuilder\ClassContent\AClassContent
     */
    public function unsetSubContent(AClassContent $subContent)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->unsetSubContent($subContent);

        foreach ($this->_data as $key => $value) {
            if (is_array($value)) {
                $totalContent = count($value);
                foreach ($value as $cKey => $cValue) {
                    //$contentType = end(array_keys($cValue));
                    $contentUid = (is_array($cValue)) ? end(array_values($cValue)) : $cValue;
                    if ($subContent->getUid() == $contentUid) {
                        if ($totalContent == 1) {
                            $this->_data[$key] = array();
                            $this->_subcontent->removeElement($subContent);
                        } else {
                            unset($value[$cKey]);
                            $this->_data[$key] = $value;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * ??????????????????????????????
     * Update the instance
     * @param Revision $lastCommitted The last committed revision of the content
     * @throws ClassContentException Occurs when a data conflict is detected
     */
    public function updateDraft(Revision $lastCommitted)
    {
        if (NULL === $revision = $this->getDraft())
            throw new ClassContentException('Enable to update: missing draft', ClassContentException::REVISION_MISSING);

        if ($revision->getLabel() != $this->_label) {
            $revision->setState(Revision::STATE_CONFLICTED);
            throw new ClassContentException('Enable to update: conflict appears', ClassContentException::REVISION_CONFLICTED);
        }

        $this->releaseDraft();
        foreach ($revision->getData() as $key => $value) {
            
        }
    }

    /**
     * ??????????????????????????????
     */
    public function setPreviewMode()
    {
        foreach (array_keys($this->_data) as $key) {
            if ((isset($this->_accept[$key])) && (1 === count($this->_accept[$key]))) {
                $type = $this->_accept[$key][0];

                if ((0 === strpos($type, 'BackBuilder\ClassContent')) && ('BackBuilder\ClassContent\ContentSet' !== $type)) {
                    if (NULL === $this->getData($key)) {
                        if (class_exists($type)) {
                            $value = array();
                            $value[] = new $type();
                            $this->__set($key, $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns a subcontent instance by its type and value, FALSE if not found
     * @param string $type The classname of the subcontent
     * @param string $value The value of the subcontent (uid)
     * @return \BackBuilder\ClassContent\AClassContent|FALSE
     */
    protected function _getContentByDataValue($type, $value)
    {
        if (true === class_exists($type)) {
            $index = 0;
            foreach ($this->_subcontent as $subcontent) {
                $this->_subcontentmap[$subcontent->getUid()] = $index++;
                if ($subcontent->getUid() === $value) {
                    return $subcontent;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * ??????????????????????????????
     */
    public function getAcceptedType($var)
    {
        $accepts = ($this->getAccept());
        if (isset($accepts[$var]) && !empty($accepts[$var])) {
            return reset($accepts[$var]);
        }
        else
            throw new ClassContentException(sprintf('Unknown element %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);
    }

}