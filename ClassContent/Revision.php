<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\ClassContent;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use BackBee\ClassContent\Exception\ClassContentException;

/**
 * Revision of a content in BackBee.
 *
 * A revision is owned by a valid user and has several states :
 *
 * * STATE_ADDED : new content, revision number to 0
 * * STATE_MODIFIED : new draft of an already revisionned content
 * * STATE_COMMITTED : one of the committed revision of a content
 * * STATE_DELETED : revision of an deleted content
 * * STATE_CONFLICTED : revision conflicted with current committed version
 * * STATE_TO_DELETE : revision to delete
 *
 * When a revision is defined as a draft of a content (ie STATE_ADDED or STATE_MODIFIED),
 * it overloads all getters and setters of its content except getUid() and setUid().
 *
 * @category    BackBee
 * @package     BackBee\ClassContent
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 * @Entity(repositoryClass="BackBee\ClassContent\Repository\RevisionRepository")
 * @Table(name="revision", indexes={@index(name="IDX_CONTENT", columns={"content_uid"}), @index(name="IDX_REVISION_CLASSNAME_1", columns={"classname"}), @index(name="IDX_DRAFT", columns={"owner", "state"})})
 * @HasLifecycleCallbacks
 */
class Revision extends AContent implements \Iterator, \Countable
{
    /**
     * Committed revision of a content
     * @var int;
     */

    const STATE_COMMITTED = 1000;

    /**
     * New content, revision number to 0
     * @var int
     */
    const STATE_ADDED = 1001;

    /**
     * New draft of an already revisionned content
     * @var int
     */
    const STATE_MODIFIED = 1002;

    /**
     * Revision conflicted with current committed version
     * @var int
     */
    const STATE_CONFLICTED = 1003;

    /**
     * Revision of an deleted content
     * @var int
     */
    const STATE_DELETED = 1004;

    /**
     * Revision to delete
     * @var int
     */
    const STATE_TO_DELETE = 1005;

    /**
     * The attached revisionned content
     * @var \BackBee\ClassContent\AClassContent
     * @ManyToOne(targetEntity="BackBee\ClassContent\AClassContent", inversedBy="_revisions", fetch="EXTRA_LAZY")
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    private $_content;

    /**
     * The entity target content classname
     * @var string
     * @Column(type="string", name="classname")
     */
    private $_classname;

    /**
     * The owner of this revision
     * @var \Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     * @Column(type="string", name="owner")
     */
    private $_owner;

    /**
     * The comment associated to this revision
     * @var string
     * @Column(type="string", name="comment", nullable=true)
     */
    private $_comment;

    /**
     * Internal position in iterator
     * @var int
     */
    private $_index = 0;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    private $_em;

    /**
     * @var \BackBee\Security\Token\BBUserToken
     */
    private $_token;

    /**
     * Sets the current entity manager to dynamicaly load subrevisions
     * @param  \Doctrine\ORM\EntityManager    $em
     * @return \BackBee\ClassContent\Revision
     */
    public function setEntityManager(\Doctrine\ORM\EntityManager $em = null)
    {
        $this->_em = $em;

        return $this;
    }

    /**
     * Sets the current BB user's token to dynamically load subrevisions
     * @param  \BackBee\Security\Token\BBUserToken $token
     * @return \BackBee\ClassContent\Revision
     */
    public function setToken(\BackBee\Security\Token\BBUserToken $token = null)
    {
        $this->_token = $token;

        return $this;
    }

    /**
     * Return a subcontent instance by its type and value, FALSE if not found
     * @param  string                                    $type  The classname of the subcontent
     * @param  string                                    $value The value of the subcontent (uid)
     * @return \BackBee\ClassContent\AClassContent|FALSE
     */
    protected function _getContentByDataValue($type, $value)
    {
        $element = new $type($value);

        if (null !== $this->_em) {
            $element = $this->_em->getRepository($type)->load($element, $this->_token);
        }

        return $element;
    }

    /*     * **************************************************************** */
    /*                                                                        */
    /*                        Common functions                                */
    /*                                                                        */
    /*     * **************************************************************** */

    /**
     * Class constructor.
     * @param string         $uid   The unique identifier of the revision
     * @param TokenInterface $token The current auth token
     */
    public function __construct($uid = null, $token = null)
    {
        parent::__construct($uid, $token);

        $this->_state = self::STATE_ADDED;
    }

    /**
     * Returns the revisionned content
     * @return \BackBee\ClassContent\AClassContent
     * @codeCoverageIgnore
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Returns the entity target content classname
     * @return string
     * @codeCoverageIgnore
     */
    public function getClassname()
    {
        return $this->_classname;
    }

    /**
     * Returns the owner of the revision
     * @return \Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     * @codeCoverageIgnore
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Returns the comment
     * @return string
     * @codeCoverageIgnore
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Sets the whole datas of the revision
     * @param  array                               $data
     * @return \BackBee\ClassContent\AClassContent the current instance content
     * @codeCoverageIgnore
     */
    public function setData(array $data)
    {
        $this->_data = $data;

        return $this->_getContentInstance();
    }

    /**
     * Sets the attached revisionned content
     * @param  \BackBee\ClassContent\AClassContent $content
     * @return \BackBee\ClassContent\AClassContent the current instance content
     */
    public function setContent(AClassContent $content = null)
    {
        $this->_content = $content;

        if (null !== $this->_content) {
            $this->setClassname(ClassUtils::getRealClass($this->_content));
        }

        return $this->_getContentInstance();
    }

    /**
     * Sets the entity target content classname
     * @param  string                              $classname
     * @return \BackBee\ClassContent\AClassContent the current instance content
     * @codeCoverageIgnore
     */
    public function setClassname($classname)
    {
        $this->_classname = $classname;

        return $this->_getContentInstance();
    }

    /**
     * Sets the owner of the revision
     * @param  \Symfony\Component\Security\Core\User\UserInterface $user
     * @return \BackBee\ClassContent\AClassContent                 the current instance content
     * @codeCoverageIgnore
     */
    public function setOwner(UserInterface $user)
    {
        $this->_owner = UserSecurityIdentity::fromAccount($user);

        return $this->_getContentInstance();
    }

    /**
     * Sets the comment associated to the revision
     * @param  string                              $comment
     * @return \BackBee\ClassContent\AClassContent the current instance content
     * @codeCoverageIgnore
     */
    public function setComment($comment)
    {
        $this->_comment = $comment;

        return $this->_getContentInstance();
    }

    /**
     * Returns the revision content
     * @return \BackBee\ClassContent\AClassContent
     * @codeCoverageIgnore
     */
    protected function _getContentInstance()
    {
        return $this->getContent();
    }

    /**
     * Sets options at the construction of a new revision
     * @param  mixed                          $options
     * @return \BackBee\ClassContent\AContent
     */
    protected function setOptions($options = null)
    {
        if ($options instanceof TokenInterface) {
            $this->_owner = UserSecurityIdentity::fromToken($options);
        }

        return $this;
    }

    /*     * ************************************************************************ */
    /*                                                                         */
    /*                       ContentSet functions                              */
    /*                                                                         */
    /*     * ************************************************************************ */

    /**
     * Empty the current set of contents
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function clear()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not clear an content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        $this->_data = array();
        $this->_index = 0;
    }

    /**
     * @see Countable::count()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function count()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not count an content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        return count($this->_data);
    }

    /**
     * @see Iterator::current()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function current()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not get current of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        return $this->getData($this->_index);
    }

    /**
     * Return the first subcontent of the set
     * @return AClassContent the first element
     */
    public function first()
    {
        return $this->getData(0);
    }

    /**
     * Return the item at index
     * @param  int                   $index
     * @return the                   item or NULL if $index is out of bounds
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function item($index)
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not get item of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        if (0 <= $index && $index < $this->count()) {
            return $this->getData($index);
        }

        return;
    }

    /**
     * @see Iterator::key()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function key()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not get key of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        return $this->_index;
    }

    /**
     * Return the last subcontent of the set
     * @return AClassContent the last element
     */
    public function last()
    {
        return $this->getData($this->count() - 1);
    }

    /**
     * @see Iterator::next()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function next()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not get next of a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        return $this->getData($this->_index++);
    }

    /**
     * Pop the content off the end of the set and return it
     * @return AClassContent Returns the last content or NULL if set is empty
     */
    public function pop()
    {
        $last = $this->last();

        if (null === $last) {
            return;
        }

        array_pop($this->_data);
        $this->rewind();

        return $last;
    }

    /**
     * Push one element onto the end of the set
     * @param  AClassContent         $var The pushed values
     * @return AClassContent         the current instance content
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function push(AClassContent $var)
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not push in a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        if ($this->isAccepted($var)) {
            $this->_data[] = array(get_class($var) => $var->getUid());
        }

        return $this->getContent();
    }

    /**
     * @see Iterator::rewind()
     * @throws ClassContentException Occurs if the attached content is not a ContentSet
     */
    public function rewind()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not rewind a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        $this->_index = 0;
    }

    /**
     * Shift the content off the beginning of the set and return it
     * @return AClassContent Returns the shifted content or NULL if set is empty
     */
    public function shift()
    {
        $first = $this->first();

        if (null === $first) {
            return;
        }

        array_shift($this->_data);
        $this->rewind();

        return $first;
    }

    /**
     * Prepend one to the beginning of the set
     * @param  AClassContent $var The prepended values
     * @return ContentSet    The current content set
     */
    public function unshift(AClassContent $var)
    {
        if ($this->isAccepted($var)) {
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
    public function valid()
    {
        if (!($this->_content instanceof ContentSet)) {
            throw new ClassContentException(sprintf('Can not valid a content %s.', get_class($this)), ClassContentException::UNKNOWN_ERROR);
        }

        return isset($this->_data[$this->_index]);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $sourceData = $this->_getContentInstance()->jsonSerialize(self::JSON_CONCISE_FORMAT);
        $draftData = parent::jsonSerialize(self::JSON_CONCISE_FORMAT);
        $draftData['uid'] = $sourceData['uid'];
        $draftData['type'] = $sourceData['type'];
        $elements = [];
        foreach ($sourceData['elements'] as $key => $element) {
            if ($element !== $draftData['elements'][$key]) {
                $elements[$key] = [
                    'current' => $element,
                    'draft'   => $draftData['elements'][$key],
                ];
            }
        }

        $draftData['elements'] = $elements;

        $parameters = [];
        foreach ($draftData['parameters'] as $key => $parameter) {
            if ($parameter['value'] !== $sourceData['parameters'][$key]['value']) {
                $parameters[$key] = [
                    'current' => $sourceData['parameters'][$key]['value'],
                    'draft'   => $parameter['value'],
                ];
            }
        }

        $draftData['parameters'] = $parameters;
        $draftData['label'] = $this->_getContentInstance()->getLabel();

        return array_merge(parent::jsonSerialize(self::JSON_INFO_FORMAT), $draftData);
    }
}
