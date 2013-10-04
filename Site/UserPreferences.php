<?php

namespace BackBuilder\Site;

use Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;


/**
 * UserPreferences object in BackBuilder 5
 * 
 * User preferences persistence
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Site
 * @copyright   Lp system
 * @author      n.dufreche
 * @Entity(repositoryClass="BackBuilder\Site\Repository\UserPreferencesRepository")
 * @Table(name="user_preferences")
 */
class UserPreferences 
{
    /**
     * Unique identifier of the revision
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    private $_uid;

    /**
     * The owner of this revision
     * @var Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     *
     * @Column(type="string", name="owner")
     */
    private $_owner;

     /**
     * The owner of this revision
     * @var text
     *
     * @Column(type="text", name="preferences")
     */
    private $_preferences;

    /**
     * Class constructor
     * 
     * @param string $uid The unique identifier of the revision
     * @param TokenInterface $token The current auth token
     */
    public function __construct($uid = NULL, $token = NULL)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;

        if ($token instanceof TokenInterface) {
            $this->_owner = UserSecurityIdentity::fromToken($token);
        }
    }
    
    /**
     * Return the uid user preferences
     *
     * @return String
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Return the owner of the user preferences
     *
     * @return Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     */
    public function getOwner() 
    {
        return $this->_owner;
    }

    /**
     * Return a json encoded string of user preferences
     *
     * @return text
     */
    public function getPreferences() 
    {
        return $this->_preferences;
    }

    /**
     * Set the user preferences UID
     *
     * @param string $uid
     * @return \BackBuilder\Site\UserPreferences
     */
    public function setUid($uid) 
    {
        $this->_uid = $uid;
        return $this;
    }

    /**
     * Set the owner of the user preferences
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return \BackBuilder\Site\UserPreferences
     */
    public function setOwner(UserInterface $user) 
    {
        $this->_owner = UserSecurityIdentity::fromAccount($user);
        return $this;
    }

    /**
     *  Set the preferences of the user
     *
     * @param mixed $preferences
     * @return \BackBuilder\Site\UserPreferences
     */
    public function setPreferences($preferences) 
    {
        if (is_array($preferences) || is_object($preferences)) {
            $preferences = json_encode($preferences);
        }
        $this->_preferences = $preferences;
        return $this;
    }
}