<?php
namespace BackBuilder\ClassContent;

use BackBuilder\Renderer\IRenderable,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\Util\Parameter;

use Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Revision of a content in BackBuilder
 * A revision is owned by a valid user and has several states :
 *     - STATE_ADDED : new content, revision number to 0
 *     - STATE_MODIFIED : new draft of an already revisionned content
 *     - STATE_COMMITTED : one of the committed revision of a content
 *     - STATE_DELETED : revision of an deleted content
 *     - STATE_CONFLICTED : revision conflicted with current committed version
 *
 * When a revision is defined as a draft of a content (ie STATE_ADDED or STATE_MODIFIED),
 * it overloads all getters and setters of its content except getUid() and setUid().
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp system
 * @author      c.rouillon
 *
 * @Entity(repositoryClass="BackBuilder\ClassContent\Repository\RevisionRepository")
 * @Table(name="revision")
 * @HasLifecycleCallbacks
 */
class Revision implements IRenderable, \Iterator, \Countable {
    const STATE_COMMITTED  = 1000;
    const STATE_ADDED      = 1001;
    const STATE_MODIFIED   = 1002;
    const STATE_CONFLICTED = 1003;
    const STATE_DELETED    = 1004;
    const STATE_TO_DELETE  = 1005;

    /**
     * The acceptable class name for values
     * @var array
     *
     * @Column(type="array", name="accept")
     */
    private $_accept;

    /**
     * The entity target content classname
     * @var string
     *
     * @Column(type="string", name="classname")
     */
    private $_classname;

    /**
     * The attached revisionned content
     * @var BackBuilder\ClassContent\AClassContent
     *
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", inversedBy="_revisions", fetch="LAZY")
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    private $_content;

    /**
     * The creation datetime
     * @var DateTime
     *
     * @Column(type="datetime", name="created")
     */
    private $_created;

    /**
     * A map of content
     * @var mixed
     *
     * @Column(type="array", name="data")
     */
    private $_data;

    /**
     * Internal position in iterator
     * @var int
     */
    private $_index = 0;

    /**
     * The label of this content
     * @var string
     *
     * @Column(type="string", name="label")
     */
    private $_label;

    /**
     * The maximal number of items for values
     * @var array
     *
     * @Column(type="array", name="maxentry")
     */
    private $_maxentry;

    /**
     * The minimal number of items for values
     * @var array
     *
     * @Column(type="array", name="minentry")
     */
    private $_minentry;

    /**
     * The last modification datetime
     * @var DateTime
     *
     * @Column(type="datetime", name="modified")
     */
    private $_modified;

    /**
     * The owner of this revision
     * @var Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     *
     * @Column(type="string", name="owner")
     */
    private $_owner;

    /**
     * The content's parameters
     * @var array
     *
     * @Column(type="array", name="parameters")
     */
    private $_parameters;

    /**
     * Revision number
     * @var Integer
     *
     * @Column(type="integer", name="revision")
     */
    private $_revision;

    /**
     * The current state o f the revision
     * @var string
     *
     * @Column(name="state", type="integer")
     */
    private $_state;

    /**
     * Unique identifier of the content
     * @var string
     *
     * @Id @Column(type="string", name="uid")
     */
    private $_uid;

    /***************************************************************************/
    /*                                                                         */
    /*                         Common functions                                */
    /*                                                                         */
    /***************************************************************************/

    /**
     * Class constructor
     * @param string $uid The unique identifier of the revision
     * @param TokenInterface $token The current auth token
     */
    public function __construct($uid = NULL, $token = NULL) {
        $this->_uid           = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->_created       = new \DateTime();
        $this->_modified      = new \DateTime();
        $this->_state         = self::STATE_ADDED;

        if ($token instanceof TokenInterface)
            $this->_owner     = UserSecurityIdentity::fromToken($token);
    }

    /**
     * Return the type of a given value
     * @param mixed $value
     * @return string
     */
    private function _getType($value) {
        if (is_object($value))
            return get_class($value);

        if (is_array($value))
            return 'array';

        return 'scalar';
    }

    /**
     * Check for an accepted type
     * @param string $value the value from which the type will be checked
     * @param string $var the element to be checks
     * @return boolean
     */
    private function _isAccepted($value, $var = NULL) {
        if ($this->_content instanceof ContentSet) {
            if (!($value instanceof AClassContent))
                return FALSE;

            if (!isset($this->_accept) || 0 == count($this->_accept))
                return TRUE;

            return in_array($this->_getType($value), $this->_accept);
        } else {
            if (NULL === $var)
                return FALSE;

            if (!isset($this->_accept[$var]) || 0 == count($this->_accept[$var]))
                return TRUE;

            return in_array($this->_getType($value), $this->_accept[$var]);
        }
    }

    /**
     * Check if the element accept subcontent
     * @param string $var the element
     * @return Boolean True if a subcontents are accepted False else
     */
    public function acceptSubcontent($var) {
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
     * Return the current accepted subcontent
     * @codeCoverageIgnore
     * @return array
     */
    public function getAccept() {
        return $this->_accept;
    }

    /**
     * Return the entity target content classname
     * @codeCoverageIgnore
     * @return string
     */
    public function getClassname() {
        return $this->_classname;
    }

    /**
     * Return the revisionned content
     * @codeCoverageIgnore
     * @return BackBuilder\ClassContent\AClassContent
     */
    public function getContent() {
        return $this->_content;
    }

    /**
     * Return the creation date of the revision
     * @codeCoverageIgnore
     * @return DateTime
     */
    public function getCreated() {
        return $this->_created;
    }

    /**
     * Return the data of the revision
     * @param $var string the element to be return, if NULL, all datas are returned
     * @param $forceArray boolean force the return as array
     * @return mixed could be either one or array of scalar value, AClassContent instance
     * @throws ClassContentException Occurs when $var does not match an element
     */
    public function getData($var = NULL, $forceArray = false) {
        if (NULL === $var) {
            $datas = array();
            foreach(array_keys($this->_data) as $key) {
                $datas[$key] = $this->getData($key);
            }

            return $datas;
        }

        if (array_key_exists($var, $this->_data)) {
            $data = array();
            foreach($this->_data[$var] as $type => $value) {
                if (is_array($value)) {
                    $keys = array_keys($value);
                    $values = array_values($value);

                    $type = end($keys);
                    $value = end($values);
                }

                if (0 === strpos($type, 'BackBuilder\ClassContent')) {
                    $data[] = new $type($value);
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
        } else {
            if ($this->getContent() instanceof ContentSet)
                return NULL;
            else
                throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);
        }
    }

    /**
     * Initialized datas on postLoad doctrine event
     */
    public function postLoad() {
        $this->_parameters = Parameter::paramsReplaceRecursive($this->getContent()->getDefaultParameters(), $this->getParam());
    }

    /**
     * Returns the raw datas array of the revision
     * @codeCoverageIgnore
     * @return array
     */
    public function getDataToObject() {
        return $this->_data;
    }

    /**
     * Return the label of the revision
     * @codeCoverageIgnore
     * @return string
     */
    public function getLabel() {
        return $this->_label;
    }

    /**
     * Get the maxentry of the revision
     * @codeCoverageIgnore
     * @return array
     */
    public function getMaxEntry() {
        return $this->_maxentry;
    }

    /**
     * Get the minentry of the revision
     * @codeCoverageIgnore
     * @return array
     */
    public function getMinEntry() {
        return $this->_minentry;
    }

    /**
     * Return the last modification date of the revision
     * @codeCoverageIgnore
     * @return DateTime
     */
    public function getModified() {
        return $this->_modified;
    }

    /**
     * Return the owner of the revision
     * @codeCoverageIgnore
     * @return Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     */
    public function getOwner() {
        return $this->_owner;
    }

    /**
     * Return defined parameters of the revision
     * @param $var string the parameter to be return, if NULL, all parameters are returned
     * @param $type string the casting type of the parameter
     * @return mixed the parameter value or NULL if unfound
     */
    public function getParam($var = NULL, $type = NULL) {
        if (NULL === $var) {
            return $this->_parameters;
        }

        if (isset($this->_parameters[$var])) {
            if (NULL === $type)
                return $this->_parameters[$var];
            else if (isset($this->_parameters[$var][$type]))
                return $this->_parameters[$var][$type];
            else
                return NULL;
        }

        return NULL;
    }

    /**
     * Return the revision number of the revision
     * @codeCoverageIgnore
     * @return int
     */
    public function getRevision() {
        return $this->_revision;
    }

    /**
     * Return the state of the revision
     * @codeCoverageIgnore
     * @return int
     */
    public function getState() {
        return $this->_state;
    }

    /**
     * Return the unique identifier of the revision
     * @codeCoverageIgnore
     * @return string
     */
    public function getUid() {
        return $this->_uid;
    }

    /**
     * Checks for state of the revsion before rendering it
     * @codeCoverageIgnore
     * @return boolean Always false, a revision can not be rendered
     */
    public function isRenderable() {
        return false;
    }

    /**
     * Return the serialized string of the revision
     * @return string
     */
    public function serialize() {
        $serialized = new \stdClass();
        $serialized->uid = $this->getUid();
        $serialized->content = (NULL === $this->_content) ? NULL : json_decode($this->_content->serialize());
        $serialized->label = $this->getLabel();
        $serialized->revision = $this->getRevision();
        $serialized->state = $this->getState();
        $serialized->created = $this->getCreated();
        $serialized->modified = $this->getModified();

        $serialized->data = new \stdClass();
        foreach($this->getData() as $key => $value) {
            if (is_array($value)) {
                $tmp = array();
                foreach($value as $val)
                    $tmp[] = is_a($val, '\BackBuilder\ClassContent\AClassContent') ? $val->getUid() : $val;
                $serialized->data->$key = $tmp;
            } else {
                $serialized->data->$key = is_a($value, '\BackBuilder\ClassContent\AClassContent') ? $value->getUid() : $value;
            }
        }

        return json_encode($serialized);
    }

    /**
     * Set the acceptable classnmae for value
     * @codeCoverageIgnore
     * @param array $accept
     * @return AClassContent the current instance content
     */
    public function setAccept($accept) {
        $this->_accept = $accept;
        return $this->getContent();
    }

    /**
     * Set the entity target content classname
     * @codeCoverageIgnore
     * @param string $classname
     * @return AClassContent the current instance content
     */
    public function setClassname($classname) {
        $this->_classname = $classname;
        return $this->getContent();
    }

    /**
     * Set the attached revisionned content
     * @param AClassContent $content
     * @return AClassContent the current instance content
     */
    public function setContent($content) {
        if (NULL !== $content && !($content instanceof AClassContent))
            throw new \Exception();

        $this->_content = $content;

        if (NULL !== $this->_content)
            $this->setClassname( get_class($this->_content) );

        return $this->getContent();
    }

    /**
     * Set creation date
     * @codeCoverageIgnore
     * @param DateTime $created
     * @return AClassContent the current instance content
     */
    public function setCreated($created) {
        $this->_created = $created;
        return $this->getContent();
    }

    /**
     * Set the whole revision datas
     * @codeCoverageIgnore
     * @param array $data
     * @return AClassContent the current instance content
     */
    public function setData($data) {
        $this->_data = $data;
        return $this->getContent();
    }

    /**
     * Set the label
     * @codeCoverageIgnore
     * @param string $label
     * @return AClassContent the current instance content
     */
    public function setLabel($label) {
        $this->_label = $label;
        return $this->getContent();
    }

    /**
     * Set the maximum number of items for elements
     * @codeCoverageIgnore
     * @param array $maxentry
     * @return AClassContent the current instance content
     */
    public function setMaxEntry($maxentry) {
        $this->_maxentry = $maxentry;
        return $this->getContent();
    }

    /**
     * Set the minimum number of items for elements
     * @codeCoverageIgnore
     * @param array $minentry
     * @return AClassContent the current instance content
     */
    public function setMinEntry($minentry) {
        $this->_minentry = $minentry;
        return $this->getContent();
    }

    /**
     * Set the last modification date
     * @codeCoverageIgnore
     * @param DateTime $modified
     * @return AClassContent the current instance content
     */
    public function setModified($modified) {
        $this->_modified = $modified;
        return $this->getContent();
    }

    /**
     * Set the owner of the revision
     * @codeCoverageIgnore
     * @param UserInterface $user
     * @return AClassContent the current instance content
     */
    public function setOwner(UserInterface $user) {
        $this->_owner = UserSecurityIdentity::fromAccount($user);
        return $this->getContent();
    }

    /**
     * Set parameter of the revision
     * @param string $var the parameter name to set, if NULL all the parameters array wil be set
     * @param mixed $values the parameter value or all the parameters if $var is null
     * @param string $type the optionnal casting type of the value
     * @return AClassContent the current instance content
     */
    public function setParam($var, $values, $type = NULL) {
        if (NULL === $var) {
            $this->_parameters = $values;
        } else {
            if (NULL !== $type)
                $values = array($type => $values);
            else
                $values = (array) $values;

            if (is_array($this->_parameters) && array_key_exists($var, $this->_parameters))
                $this->_parameters[$var] = $values; //array_replace_recursive($this->_parameters[$var], $values);
            else
                $this->_parameters[$var] = $values;
        }

        return $this->getContent();
    }

    /**
     * Set the revision number of the revision
     * @codeCoverageIgnore
     * @param int $revision
     * @return AClassContent the current instance content
     */
    public function setRevision($revision) {
        $this->_revision = $revision;
        return $this->getContent();
    }

    /**
     * Set the state of the revision
     * @codeCoverageIgnore
     * @param int $state
     * @return AClassContent the current instance content
     */
    public function setState($state) {
        $this->_state = $state;
        return $this->getContent();
    }

    /***************************************************************************/
    /*                                                                         */
    /*                     AClassContent functions                             */
    /*                                                                         */
    /***************************************************************************/

    /**
     * Magical function to get value for given element
     * @param string $var the name of the element
     * @return mixed the value
     * @throws ClassContentException Occurs when the revisionned content is a ContentSet
     */
    public function __get($var) {
        if ($this->_content instanceof ContentSet)
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);

        return $this->getData($var);
    }

    /**
     * Magical function to check the setting of an element
     * @param string $var the name of the element
     * @throws ClassContentException Occurs when the revisionned content is a ContentSet
     */
    public function __isset($var) {
        if ($this->_content instanceof ContentSet)
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);

        return array_key_exists($var, $this->_data) && 0 < count($this->_data[$var]);
    }

    /**
     * Magical function to set value to given element
     * @param string $var the name of the element
     * @param mixed $value the value to set
     * @return AClassContent the current instance content
     * @throws ClassContentException Occurs when the revisionned content is a ContentSet
     */
    public function __set($var, $value) {
        if ($this->_content instanceof ContentSet)
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);

        $values = is_array($value) ? $value : array($value);

        if (isset($this->_data[$var])) {
            $this->__unset($var);
            $val = array();

            foreach ($values as $value) {
                if (isset($this->_maxentry[$var]) && 0 < $this->_maxentry[$var] && $this->_maxentry[$var] == count($val))
                    break;

                if ($this->_isAccepted($value, $var)) {
                    $type = $this->_getType($value);
                    if (is_object($value) && $value instanceof AClassContent) {
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

        return $this->getContent();
    }

    /**
     * Magical function to unset an element
     * @param string $var the name of the element
     * @throws ClassContentException Occurs when the revisionned content is a ContentSet
     */
    public function __unset($var) {
        if ($this->_content instanceof ContentSet)
            throw new ClassContentException(sprintf('Unknown property %s in %s.', $var, get_class($this)), ClassContentException::UNKNOWN_PROPERTY);

        if ($this->__isset($var)) {
            if (TRUE === $this->acceptSubcontent($var)) {
                foreach ($this->_data[$var] as $type => $value) {
                    if (is_array($value)) {
                        $keys = array_keys($value);
                        $values = array_values($value);

                        $type = end($keys);
                        $value = end($values);
                    }
                }
            }

            $this->_data[$var] = array();
        }
    }

    /***************************************************************************/
    /*                                                                         */
    /*                       ContentSet functions                              */
    /*                                                                         */
    /***************************************************************************/

    /**
     * Empty the current set of contents
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function clear() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not clear an content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        $this->_data = array();
        $this->_index = 0;
    }

    /**
     * @see Countable::count()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function count() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not count an content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        return count($this->_data);
    }

    /**
     * @see Iterator::current()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function current() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not get current of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        return $this->getData($this->_index);
    }

    /**
     * Return the first subcontent of the set
     * @codeCoverageIgnore
     * @return AClassContent the first element
     */
    public function first() {
        return $this->getData(0);
    }

    /**
     * Return the item at index
     * @param int $index
     * @return the item or NULL if $index is out of bounds
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function item($index) {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not get item of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        if (0 <= $index && $index < $this->count())
            return $this->getData($index);

        return NULL;
    }

    /**
     * @see Iterator::key()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function key() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not get key of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        return $this->_index;
    }

    /**
     * Return the last subcontent of the set
     * @return AClassContent the last element
     */
    public function last() {
        return $this->getData($this->count() - 1);
    }

    /**
     * @see Iterator::next()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function next() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not get next of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        return $this->getData($this->_index++);
    }

    /**
     * Pop the content off the end of the set and return it
     * @return AClassContent Returns the last content or NULL if set is empty
     */
    public function pop() {
        $last = $this->last();

        if (NULL === $last)
            return NULL;

        array_pop($this->_data);
        $this->rewind();

        return $last;
    }

    /**
     * Push one element onto the end of the set
     * @param AClassContent $var The pushed values
     * @return AClassContent the current instance content
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function push(AClassContent $var) {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not push in a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        if ($this->_isAccepted($var)) {
            $this->_data[] = array(get_class($var) => $var->getUid());
        }

        return $this->getContent();
    }

    /**
     * @see Iterator::rewind()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function rewind() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not rewind a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        $this->_index = 0;
    }

    /**
     * Shift the content off the beginning of the set and return it
     * @return AClassContent Returns the shifted content or NULL if set is empty
     */
    public function shift() {
        $first = $this->first();

        if (NULL === $first)
            return NULL;

        array_shift($this->_data);
        $this->rewind();

        return $first;
    }

    /**
     * Prepend one to the beginning of the set
     * @param AClassContent $var The prepended values
     * @return ContentSet The current content set
     */
    public function unshift(AClassContent $var) {
        if ($this->_isAccepted($var)) {
            if (!$this->_maxentry || $this->_maxentry > $this->count()) {
                array_unshift($this->_data, array($this->_getType($var) => $var->getUid()));
            }
        }

        return $this->getContent();
    }

    /**
     * @see Iterator::valid()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function valid() {
        if (!($this->_content instanceof ContentSet))
            throw new ClassContentException(sprintf('Can not valid a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);

        return isset($this->_data[$this->_index]);
    }

}