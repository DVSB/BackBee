<?php

namespace BackBuilder\ClassContent;

use BackBuilder\NestedNode\Page,
    BackBuilder\Renderer\IRenderable,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\Util\Parameter;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * Abstract class for content object in BackBuilder 4
 *
 * Basicaly a BackBuilder content is a composite of AClassContent
 * A content is also an persistant Doctrine entity
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp system
 * @author      c.rouillon
 *
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\ClassContentRepository")
 * @Table(name="content")
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="classname", type="string")
 * @DiscriminatorMap({"BackBuilder\ClassContent\ContentSet" = "BackBuilder\ClassContent\ContentSet"})
 */
abstract class AClassContent implements IRenderable, \Serializable, DomainObjectInterface
{


    const STATE_NEW = 1000;
    const STATE_NORMAL = 1001;
    const STATE_LOCKED = 1002;

    /**
     * The acceptable class name for values
     * @var array
     *
     * @Column(type="array", name="accept")
     */
    protected $_accept;

    /**
     * The creation datetime
     * @var DateTime
     *
     * @Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * A map of content
     * @var mixed
     *
     * @Column(type="array", name="data")
     */
    protected $_data;

    /**
     * Yaml parameters
     * @var array
     */
    protected $_defaultparameters = array();

    /**
     * The optionnal personnal draft of this content
     * @var Revision
     */
    protected $_draft;

    /**
     * The indexed values of elements
     * @var ArrayColection
     *
     * @OneToMany(targetEntity="BackBuilder\ClassContent\Indexation", mappedBy="_content", cascade={"all"})
     */
    protected $_indexation;

    /**
     * Is this content persisted
     * @var boolean
     */
    protected $_isloaded;

    /**
     * The label of this content
     * @var string
     *
     * @Column(type="string", name="label")
     */
    protected $_label;

    /**
     * The main nested node (page)
     * @var Page
     *
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\Page")
     * @JoinColumn(name="node_uid", referencedColumnName="uid")
     */
    protected $_mainnode;

    /**
     * The maximal number of items for values
     * @var array
     *
     * @Column(type="array", name="maxentry")
     */
    protected $_maxentry;

    /**
     * The minimal number of items for values
     * @var array
     *
     * @Column(type="array", name="minentry")
     */
    protected $_minentry;

    /**
     * The last modification datetime
     * @var DateTime
     *
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * The content's parameters
     * @var array
     *
     * @Column(type="array", name="parameters")
     */
    protected $_parameters;

    /**
     * The many to many association between this content and its parent content
     * @var AClassContent
     *
     * @ManyToMany(targetEntity="BackBuilder\ClassContent\AClassContent", mappedBy="_subcontent")
     */
    protected $_parentcontent;

    /**
     * The content's properties
     * @var array
     */
    protected $_properties;

    /**
     * The revision number of the content
     * @var int
     *
     * @Column(type="integer", name="revision")
     */
    protected $_revision;

    /**
     * The revisions of the content
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="BackBuilder\ClassContent\Revision", mappedBy="_content", fetch="LAZY")
     * @OrderBy({"_version" = "DESC"})
     */
    protected $_revisions;

    /**
     * State of this content
     * @var int
     *
     * @Column(type="integer", name="state")
     */
    protected $_state;

    /**
     * The many to many association between this content and its subcontent
     * @var ArrayCollection
     *
     * @ManyToMany(targetEntity="BackBuilder\ClassContent\AClassContent", inversedBy="_parentcontent", cascade={"persist", "detach", "merge", "refresh"})
     * @JoinTable(name="content_has_subcontent",
     *   joinColumns={@JoinColumn(name="parent_uid", referencedColumnName="uid")},
     *   inverseJoinColumns={@JoinColumn(name="content_uid", referencedColumnName="uid")}
     * )
     */
    public $_subcontent;

    /**
     * Store the map associating content uid to subcontent index
     * @var array;
     */
    protected $_subcontentmap = array();

    /**
     * Unique identifier of the content
     * @var string
     *
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;

    /**
     * Class constructor
     * @param string $uid The unique identifier of the content
     * @param array $options Initial options for the content:
     *                         - accept      array Acceptable class names for the value
     *                         - maxentry    int The maxentry in value
     *                         - default     array default value for datas
     */
    public function __construct($uid = NULL, $options = NULL)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
        $this->_accept = array();
        $this->_data = array();
        $this->_maxentry = array();
        $this->_minentry = array();
        $this->_indexation = new ArrayCollection();
        $this->_subcontent = new ArrayCollection();
        $this->_parentcontent = new ArrayCollection();
        $this->_revisions = new ArrayCollection();
        $this->_isloaded = FALSE;
        $this->_revision = 0;
        $this->_state = self::STATE_NEW;
        $this->_defaultparameters = array();
        $this->_setOptions($options);
    }

    /**
     * Alternative clone method, created because of problems related to doctrine clone method
     * @param \BackBuilder\NestedNode\Page $origin_page
     * @return \BackBuilder\ClassContent\ContentSet
     */
    public function createClone(Page $origin_page = null)
    {
        $class = $this->getRealClass();
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
     * Magical function to clone this content
     */
    public function __clone()
    {
        if ($this->_uid) {
            $this->_uid = md5(uniqid('', TRUE));
            $this->_created = new \DateTime();
            $this->_modified = new \DateTime();
            $this->_isloaded = FALSE;
            $this->_revision = 0;
            $this->_state = self::STATE_NEW;
            $this->_draft = NULL;

            $this->_indexation->clear();
            $this->_revisions->clear();
            $this->_parentcontent->clear();

            $subcontents = array();
            foreach ($this->_data as $values) {
                foreach ($values as $type => &$value) {
                    if (is_array($value)) {
                        $keys = array_keys($value);
                        $values = array_values($value);

                        $type = end($keys);
                        $value = end($values);
                    }

                    if (0 === strpos($type, 'BackBuilder\ClassContent')) {
//                        $classtoload = class_exists($type);
                        foreach ($this->_subcontent as $subcontent) {
                            if ($subcontent->getUid() == $value) {
                                $newsubcontent = clone $subcontent;
                                $value = $newsubcontent->getUid();
                                $subcontents[] = $newsubcontent;
                                break;
                            }
                        }
                    }
                }
                unset($value);
            }

            $this->_subcontent->clear();
            foreach ($subcontents as $subcontent) {
                var_dump('parent_' . $this->_uid, 'child_' . $subcontent->getUid());
                $this->_subcontent->add($subcontent);
            }
        }
    }

    /**
     * Magical function to get value for given element
     * @param string $var the name of the element
     * @return mixed the value
     */
    public function __get($var)
    {
        return $this->getData($var);
    }

    /**
     * Magical function to check the setting of an element
     * @param string $var the name of the element
     */
    public function __isset($var)
    {
        return (NULL === $this->getDraft()) ? array_key_exists($var, $this->_data) && 0 < count($this->_data[$var]) : $this->getDraft()->__isset($var);
    }

    /**
     * Magical function to set value to given element
     * @param string $var the name of the element
     * @param mixed $value the value to set
     * @return AClassContent $this
     * @throws ClassContentException
     */
    public function __set($var, $value)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->__set($var, $value);

        $values = is_array($value) ? $value : array($value);
        if (isset($this->_data[$var])) {
            $this->__unset($var);
            $val = array();

            foreach ($values as $value) {
                if (
                        (isset($this->_maxentry[$var]) && 0 < $this->_maxentry[$var] && $this->_maxentry[$var] == count($val)) ||
                        (isset($this->_minentry[$var]) && count($val) < $this->_minentry[$var] && $this->_maxentry[$var] == count($val))
                ) {
                    break;
                }
                if ($this->_isAccepted($value, $var)) {
                    $type = $this->_getType($value);
                    if (is_object($value) && $value instanceof AClassContent) {
                        if (FALSE === $this->_subcontent->indexOf($value))
                            $this->_subcontent->add($value);
                        $this->_subcontentmap[$value->getUid()] = $this->_subcontent->indexOf($value);
                        $value = $value->getUid();
                    }

                    $val[] = array($type => $value);
                }
            }
            $this->_data[$var] = $val;
            $this->_modified = new \DateTime();
        } else {
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);
        }

        return $this;
    }

    /**
     * Magical function to get a string representation of the content
     * @return string
     */
    public function __toString()
    {
        if ($this->isElementContent()) {
            $string = '';
            foreach ($this->getData() as $val) {
                $string .= $val;
            }
            return $string;
        } else {
            return sprintf('%s(%s)', get_class($this), $this->_uid);
        }
    }

    /**
     * Magical function to unset an element
     * @param string $var the name of the element
     */
    public function __unset($var)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->__unset($var);

        if ($this->__isset($var)) {
            if (TRUE === $this->_acceptSubcontent($var)) {
                $this->_updateSubcontentMap();

                foreach ($this->_data[$var] as $type => $value) {
                    if (is_array($value)) {
                        $keys = array_keys($value);
                        $values = array_values($value);

                        $type = end($keys);
                        $value = end($values);
                    }

                    foreach ($this->_subcontent as $subcontent) {
                        if ($subcontent->getUid() == $value) {
                            $this->_subcontent->removeElement($subcontent);
                            break;
                        }
                    }
                }
            }

            $this->_data[$var] = array();
        }
    }

    /**
     *
     * @param AClassContent $subContent
     * @return type
     */
    public function unsetSubContent(AClassContent $subContent)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->unsetSubContent($subContent);

        foreach ($this->_data as $key => $value) {
            if (is_array($value)) {
                $totalContent = count($value);
                foreach ($value as $cKey => $cValue) {
                    $contentType = end(array_keys($cValue));
                    $contentUid = end(array_values($cValue));
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
    }

    /**
     * Check if the element accept subcontent
     * @param string $var the element
     * @return Boolean True if a subcontents are accepted False else
     */
    private function _acceptSubcontent($var)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->acceptSubcontent($var);

        if (FALSE === array_key_exists($var, $this->_accept))
            return FALSE;

        foreach ($this->_accept[$var] as $type) {
            if (0 === strpos($type, 'BackBuilder\ClassContent')) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Add a new accepted type to the element
     * @param string $type the type to accept
     * @param string $var the element
     * @return AClassContent $this
     */
    protected function _addAcceptedType($type, $var = NULL)
    {
        if (NULL === $var)
            return $this;

        if (!isset($this->_accept[$var]))
            $this->_accept[$var] = array();

        $types = (array) $type;
        foreach ($types as $type) {
            $type = (NAMESPACE_SEPARATOR == substr($type, 0, 1)) ? substr($type, 1) : $type;
            if (!in_array($type, $this->_accept[$var]))
                $this->_accept[$var][] = $type;
        }

        return $this;
    }

    /**
     * Dynamically add and set new element to this content
     * @param string $var the name of the element
     * @param type $value the type
     * @param array $options Initial options for the content (see this constructor)
     * @param boolean $updateAccept dynamically accept or not the type for the new element
     * @return AClassContent $this
     */
    protected function _defineData($var, $type = 'scalar', $options = NULL, $updateAccept = TRUE)
    {
        if ($updateAccept)
            $this->_addAcceptedType($type, $var);

        if (!isset($this->_data[$var])) {
            $this->_data[$var] = array();
            $this->_maxentry[$var] = (!is_null($options) && isset($options['maxentry'])) ? $options['maxentry'] : 1;
            $this->_minentry[$var] = (!is_null($options) && isset($options['minentry'])) ? $options['minentry'] : 0;

            $values = array();
            if (!is_null($options) && isset($options['default'])) {
                $values = (array) $options['default'];

                if ('scalar' != $type) {
                    foreach ($values as &$value) {
                        $value = new $type(NULL, $options);
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
     * @param type $value the type
     * @param array $options Initial options for the parameter (see this constructor)
     * @return AClassContent $this
     */
    protected function _defineParam($var, $type = 'scalar', $options = NULL)
    {
        if ('accept' == $var) {
            if (!is_null($options) && isset($options['default']))
                return $this->_addAcceptedType($options['default']);
        } else {
            $values = array();
            if (!is_null($options) && isset($options['default'])) {
                $values[$type] = $options['default'];
            } else {
                $values[$type] = NULL;
            }

            if (!isset($this->_parameters[$var])) {
                $this->_parameters[$var] = $values;
            }

            $this->_defaultparameters[$var] = $values;
        }

        return $this;
    }

    /**
     * Dynamically add and set new property to this content
     * @param string $var the name of the property
     * @param mixed $value the value of the property
     * @return AClassContent $this
     */
    protected function _defineProperty($var, $value)
    {
        if (!isset($this->_properties[$var])) {
            $this->_properties[$var] = $value;
        }
        return $this;
    }

    /**
     * Update a non pesistent property value for the current instance
     * @param string $var the name of the property
     * @param mixed $value the value of the property
     * @return AClassContent the current instance
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
     * Return the type of a given value
     * @param mixed $value
     * @return string
     */
    protected function _getType($value)
    {
        if (is_object($value))
            return get_class($value);

        if (is_array($value))
            return 'array';

        return 'scalar';
    }

    /**
     * Sets a collection of datas
     * Has to be overwritten by generalizations
     */
    protected function _initData()
    {
        // Has to be overwritten by generalizations
    }

    /**
     * Check for an accepted type
     * @param string $value the value from which the type will be checked
     * @param string $var the element to be checks
     * @return boolean
     */
    private function _isAccepted($value, $var = NULL)
    {
        if (NULL === $var)
            return false;

        if (!isset($this->_accept[$var]) || 0 == count($this->_accept[$var]))
            return true;

        return in_array($this->_getType($value), $this->_accept[$var]);
    }

    public function getType($var)
    {
        $accepts = ($this->getAccept());
        if (isset($accepts[$var]) && !empty($accepts[$var])) {
            return reset($accepts[$var]);
        }
        else
            throw new ClassContentException(sprintf('Unknown element %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);
    }

    /**
     * Set options at the construction of a new instance
     * @param array $options Initial options for the content:
     *                         - label       the label of the content
     *                         - parameters  a set of parameters for the content
     *                         - default     array default value for datas
     */
    protected function _setOptions($options = NULL)
    {
        if (!is_null($options)) {
            $options = (array) $options;
            if (array_key_exists('label', $options))
                $this->_label = $options['label'];
            if (array_key_exists('parameters', $options)) {
                foreach ($options['parameters'] as $key => $param)
                    $this->_defineParam($key, $param['type'], $param['options']);
            }
            if (array_key_exists('default', $options)) {
                $options['default'] = (array) $options['default'];
                foreach ($options['default'] as $var => $value) {
                    $this->_data[$var] = array();
                    $this->_maxentry[$var] = 1;
                    $this->_minentry[$var] = 0;
                    $this->__set($var, $value);
                }
            }
        }
    }

    /**
     * Update the associationing map between subcontent and uid
     */
    private function _updateSubcontentMap()
    {
        $this->_subcontentmap = array();
        $index = 0;

        foreach ($this->_subcontent as $subcontent) {
            $this->_subcontentmap[$subcontent->getUid()] = $index++;
        }
    }

    /**
     * Add a new revision to the collection
     * @codeCoverageIgnore
     * @param Revision $revisions
     * @return AClassContent the current instance
     */
    public function addRevision(Revision $revisions)
    {
        $this->_revisions[] = $revisions;
        return $this;
    }

    /**
     * Return the current accepted subcontent
     * @return array
     */
    public function getAccept()
    {
        return NULL != $this->getDraft() ? $this->getDraft()->getAccept() : $this->_accept;
    }

    /**
     * Return the creation date of the content
     * @return DateTime
     */
    public function getCreated()
    {
        return NULL != $this->getDraft() ? $this->getDraft()->getCreated() : $this->_created;
    }

    /**
     * Return the data of this content
     * @param $var string the element to be return, if NULL, all datas are returned
     * @param $forceArray boolean force the return as array
     * @return mixed could be either one or array of scalar value, AClassContent instance
     * @throws ClassContentException Occurs when $var does not match an element
     */
    public function getData($var = NULL, $forceArray = false)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->getData($var);

        if (NULL === $var) {
            $datas = array();
            foreach (array_keys($this->_data) as $key) {
                $datas[$key] = $this->getData($key);
            }

            return $datas;
        }

        if (array_key_exists($var, $this->_data)) {
            $data = array();
            foreach ($this->_data[$var] as $type => $value) {
                if (is_array($value)) {
                    $keys = array_keys($value);
                    $values = array_values($value);

                    $type = end($keys);
                    $value = end($values);
                }

                if (0 === strpos($type, 'BackBuilder\ClassContent')) {
                    // Ensure the class type is known
                    $classtoload = class_exists($type);

                    //if (!$ignoreMap && array_key_exists($value, $this->_subcontentmap)) {
                    //    $data[] = $this->_subcontent->get($this->_subcontentmap[$value]);
                    //} else {
                    $index = 0;
                    foreach ($this->_subcontent as $subcontent) {
                        $this->_subcontentmap[$subcontent->getUid()] = $index++;
                        if ($subcontent->getUid() == $value) {
                            $data[] = $subcontent;
                            break;
                        }
                    }
                    //}
                } else {
                    $data[] = $value;
                }
            }

            if (!$forceArray) {
                switch (count($data)) {
                    case 0:
                        $data = NULL;
                        break;
                    case 1:
                        $data = array_pop($data);
                        break;
                }
            }

            return $data;
        } else if ($this instanceof ContentSet) {
            return NULL;
        } else {
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);
        }
    }

    /**
     * Returns the raw datas array of the content
     * @return array
     */
    public function getDataToObject()
    {
        return (NULL === $this->getDraft()) ? $this->_data : $this->getDraft()->getDataToObject();
    }

    /**
     * Returns the parameters as defined in Yaml
     * @codeCoverageIgnore
     * @return array
     */
    public function getDefaultParameters()
    {
        return $this->_defaultparameters;
    }

    /**
     * Return the user draft of this content if exists
     * @codeCoverageIgnore
     * @return Revision the current draft if exists NULL otherwise
     */
    public function getDraft()
    {
        return $this->_draft;
    }

    /**
     * Return the label of the content
     * @return string
     */
    public function getLabel()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getLabel() : $this->_label;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getIndexation() {
        return $this->_indexation;
    }

    /**
     * Return the associated page of the content
     * @codeCoverageIgnore
     * @return Page
     */
    public function getMainNode()
    {
        return $this->_mainnode;
    }

    /**
     * Get the maxentry of the content
     * @return array
     */
    public function getMaxEntry()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getMaxEntry() : $this->_maxentry;
    }

    /**
     * Get the minentry of the content
     * @return array
     */
    public function getMinEntry()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getMinEntry() : $this->_minentry;
    }

    /**
     * Return the last modification date of the content
     * @return DateTime
     */
    public function getModified()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getModified() : $this->_modified;
    }

    /**
     * Return defined parameters of the content
     * @param $var string the parameter to be return, if NULL, all parameters are returned
     * @param $type string the casting type of the parameter
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($var = NULL, $type = NULL)
    {
        if (NULL !== $this->getDraft()) {
            return $this->getDraft()->getParam($var, $type);
        }

        if (NULL == $var) {
            return $this->_parameters;
        }

        if (isset($this->_parameters[$var])) {
            if (NULL == $type)
                return $this->_parameters[$var];
            else if (isset($this->_parameters[$var][$type]))
                return $this->_parameters[$var][$type];
            else
                return NULL;
        }

        return NULL;
    }

    /**
     * Return the current parent content if exists
     * @codeCoverageIgnore
     * @return AClassContent The parent content if exists, NULL otherwise
     */
    public function getParentContent()
    {
        return $this->_parentcontent;
    }

    /**
     * Return defined property of the content
     * @param $var string the property to be return, if NULL, all properties are returned
     * @return mixed the property value or NULL if unfound
     */
    public function getProperty($var = NULL)
    {
        if (NULL == $var) {
            return $this->_properties;
        }

        if (isset($this->_properties[$var])) {
            return $this->_properties[$var];
        }

        return NULL;
    }

    /**
     * Return the revision number of the content
     * @return int
     */
    public function getRevision()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getRevision() : $this->_revision;
    }

    /**
     * Return the collection of revisions of the content
     * @codeCoverageIgnore
     * @return ArrayCollection
     */
    public function getRevisions()
    {
        return $this->_revisions;
    }

    /**
     * Return the state of the content
     * @return int
     */
    public function getState()
    {
        return (NULL !== $this->getDraft()) ? $this->getDraft()->getState() : $this->_state;
    }

    /**
     * Return the unique identifier of the content
     * @codeCoverageIgnore
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Return TRUE if the content is an entity managed by doctrine
     * @codeCoverageIgnore
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->_isloaded;
    }

    public function isElementContent()
    {
        return false !== strpos(get_class($this), 'BackBuilder\ClassContent\Element\\');
    }

    /**
     * Checks for state of the content before rendering it
     * @return boolean
     */
    public function isRenderable()
    {
        return $this->getState() == AClassContent::STATE_NORMAL;
    }

    /**
     * Initialized datas on postLoad doctrine event
     */
    public function postLoad()
    {
        $this->_initData();
        $this->_parameters = Parameter::paramsReplaceRecursive($this->getDefaultParameters(), $this->getParam());
        $this->_isloaded = true;
    }

    /**
     * Prepare to commit an user draft data for current content
     * @return AClassContent the current instance
     * @throw ClassContentException Occurs when the provided revision is invalid
     */
    public function prepareCommitDraft()
    {
        if (NULL === $revision = $this->getDraft()) {
            throw new ClassContentException('Enable to commit: missing draft', ClassContentException::REVISION_MISSING);
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
                throw new ClassContentException('Content is in conflict, please resolve or revert it', ClassContentException::REVISION_CONFLICTED);
                break;
        }

        throw new ClassContentException(sprintf('Content can not be commited (state : %s)', $revision->getState()), ClassContentException::REVISION_UPTODATE);
    }

    /**
     * Unset current user draft
     * @codeCoverageIgnore
     * @return AClassContent the current instance
     */
    public function releaseDraft()
    {
        $this->_draft = NULL;
        return $this;
    }

    /**
     * Return the serialized string of the content
     * @return string
     */
    public function serialize()
    {
        $serialized = new \stdClass();
        $serialized->uid = $this->getUid();
        $serialized->type = get_class($this);
        $serialized->isdraft = (NULL !== $this->getDraft());
        $serialized->draftuid = $serialized->isdraft ? $this->getDraft()->getUid() : NULL;
        $serialized->label = $this->getLabel();
        $serialized->revision = $this->getRevision();
        $serialized->state = $this->getState();
        $serialized->created = $this->getCreated();
        $serialized->modified = $this->getModified();

        $serialized->properties = new \stdClass();
        foreach ($this->getProperty() as $key => $value) {
            $serialized->properties->$key = $value;
        }
        $serialized->accept = new \stdClass();
        foreach ($this->getAccept() as $key => $value) {
            $serialized->accept->$key = $value;
        }
        $serialized->maxentry = new \stdClass();
        if (true === is_array($this->getMaxEntry())) {
            foreach ($this->getMaxEntry() as $key => $value) {
                $serialized->maxentry->$key = $value;
            }
        }
        $serialized->minentry = new \stdClass();
        if (true === is_array($this->getMinEntry())) {
            foreach ($this->getMinEntry() as $key => $value) {
                $serialized->minentry->$key = $value;
            }
        }
        $serialized->data = new \stdClass();
        foreach ($this->getData() as $key => $value) {
            if (is_array($value)) {
                $tmp = array();
                foreach ($value as $val)
                    $tmp[] = ($val instanceof AClassContent) ? $val->getUid() : $val;
                $serialized->data->$key = $tmp;
            } else {
                $serialized->data->$key = ($value instanceof AClassContent) ? $value->getUid() : $value;
            }
        }

        $serialized->param = new \stdClass();
        if ($this->getParam())
            foreach ($this->getParam() as $key => $value)
                $serialized->param->$key = $value;

        return json_encode($serialized);
    }

    /**
     * Associated an user draft to this content
     * @codeCoverageIgnore
     * @param Revision $draft
     * @return AClassContent the current instance
     */
    public function setDraft(Revision $draft)
    {
        $this->_draft = $draft;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param type $label
     * @return \BackBuilder\ClassContent\AClassContent
     */
    public function setLabel($label)
    {
        $this->_label = $label;
        return $this;
    }

    /**
     * Set the main page to this content
     * @param Page $node
     * @return AClassContent the current instance
     */
    public function setMainNode(Page $node = NULL)
    {
        $this->_mainnode = $node;
        return $this;
    }

    public function setModified()
    {
        $this->_modified = new \DateTime();
        return $this;
    }
    /**
     * Set parameter of the content
     * @param string $var the parameter name to set, if NULL all the parameters array wil be set
     * @param mixed $values the parameter value or all the parameters if $var is null
     * @param string $type the optionnal casting type of the value
     * @return AClassContent the current instance
     */
    public function setParam($var, $values, $type = NULL)
    {
        if (NULL !== $this->getDraft())
            return $this->getDraft()->setParam($var, $values, $type);

        if (NULL === $var) {
            $this->_parameters = $values;
        } else {
            if (NULL !== $type)
                $values = array($type => $values);
            else
                $values = (array) $values;

            if (is_array($this->_parameters) && array_key_exists($var, $this->_parameters))
                $this->_parameters[$var] = array_replace_recursive($this->_parameters[$var], $values);
            else
                $this->_parameters[$var] = $values;
        }

        return $this;
    }

    /**
     * What this ?
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
     * Set the revision number of the content
     * @codeCoverageIgnore
     * @param int $revision
     * @return AClassContent the current instance
     */
    public function setRevision($revision)
    {
        $this->_revision = $revision;
        return $this;
    }

    /**
     * Set the state of the content
     * @codeCoverageIgnore
     * @param int $state
     * @return AClassContent the current instance
     */
    public function setState($state)
    {
        $this->_state = $state;
        return $this;
    }

    /**
     * Initialized the instance from a serialized string
     * @param string $serialized
     * @param boolean $strict If TRUE, all missing or additionnal element will generate an error
     * @return AClassContent the current instance
     */
    public function unserialize($serialized, $strict = FALSE)
    {

        if (FALSE === is_object($serialized))
            $serialized = json_decode($serialized);

        foreach (get_object_vars($serialized) as $property => $value) {
            $property = '_' . $property;
            if (in_array($property, array('_created', '_modified'))) {
                continue;
            } else if ($property == "_param" && !is_null($value)) {
                foreach ($value as $param => $paramvalue) {
                    $this->setParam($param, $paramvalue);
                }
            } else if ($property == "_data" && !is_null($value)) {
                foreach ($value as $el => $val) {
                    $this->$el = $val;
                }
            } else if ($property == "_value" && !is_null($value)) {
                $this->value = $value;
            } else if (TRUE === property_exists($this, $property)) {
                //$this->$property = $value;
            } else if (TRUE === $strict)
                throw new BBException(sprintf('Unknown property `%s` in %s.', $property, get_class($this)));
        }

        return $this;
    }

    /**
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
     * Returns a unique identifier for this domain object
     * @codeCoverageIgnore
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->getUid();
    }

    /**
     * Returns the mode use by current content
     * @return string
     */
    public function getMode() {
        $rendermode = NULL;

        if (is_array($this->getParam('rendermode'))) {
            $rendermode = @array_pop($this->getParam('rendermode'));

            if (isset($rendermode['rendertype']) && ('select' === $rendermode['rendertype'])) {
                $rendermode = $rendermode['options'][$rendermode['selected']];
            }

            if (isset($rendermode['rendertype'])) {
                switch ($rendermode['rendertype']) {
                    case 'select':
                        $rendermode = $rendermode['options'][$rendermode['selected']];
                        break;
                }
            }
        }

        return $rendermode;
    }

}
