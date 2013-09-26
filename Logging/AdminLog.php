<?php
namespace BackBuilder\Logging;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity,
    Symfony\Component\Security\Core\User\UserInterface;

/**
 * @category    BackBuilder Bundle
 * @package     BackBuilder\Security
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 * @Entity(repositoryClass="BackBuilder\Logging\Repository\AdminLogRepository")
 * @Table(name="admin_log")
 */
class AdminLog {
    /**
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;
    /**
     * @var Symfony\Component\Security\Acl\Domain\UserSecurityIdentity
     * @Column(type="string", name="owner")
     */
    protected $_owner;
    /**
     * @var string
     * @Column(type="string", name="controller")
     */
    protected $_controller;
    /**
     * @var string
     * @Column(type="string", name="action")
     */
    protected $_action;
    /**
     * @var string
     * @Column(type="string", name="entity")
     */
    protected $_entity;
    /**
     * @var string
     * @Column(type="datetime", name="created_at")
     */
    protected $_created_at;
    
    public function __construct($uid = NULL, $token = NULL)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->_created_at = new \DateTime();
        if ($token instanceof TokenInterface)
            $this->_owner = UserSecurityIdentity::fromToken($token);
    }

    /**
     * Get the owner of the log
     * @codeCoverageIgnore
     * @return UserInterface
     */
    public function getOwner() {
        return $this->_owner;
    }
    
    /**
     * Set the owner of the log
     * @codeCoverageIgnore
     * @param UserInterface $user
     * @return AdminLog
     */
    public function setOwner(UserInterface $user) {
        $this->_owner = UserSecurityIdentity::fromAccount($user);
        return $this;
    }

    /**
     * Get the controller
     * @codeCoverageIgnore
     * @return String
     */
    public function getController() {
        return $this->_controller;
    }
    
    /**
     * Set the controller
     * @codeCoverageIgnore
     * @param string $controller
     * @return AdminLog
     */
    public function setController($controller) {
        $this->_controller = $controller;
        return $this;
    }

    /**
     * Get the action call in the controller
     * @codeCoverageIgnore
     * @return String
     */
    public function getAction() {
        return $this->_action;
    }
    
    /**
     * Set the action call in the controller
     * @codeCoverageIgnore
     * @param String $action
     * @return AdminLog
     */
    public function setAction($action) {
        $this->_action = $action;
        return $this;
    }

    /**
     * Set the entity call in the controller
     * @codeCoverageIgnore
     * @return Object
     */
    public function getEntity() {
        return $this->_entity;
    }
    
    /**
     * Set the entity call in the controller
     * @codeCoverageIgnore
     * @param Object $entity
     * @return AdminLog
     */
    public function setEntity($entity) {
        $this->_entity = ObjectIdentity::fromDomainObject($entity);
        return $this;
    }

    /**
     * Get the datetime of the log
     * @codeCoverageIgnore
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->_created_at;
    }
}