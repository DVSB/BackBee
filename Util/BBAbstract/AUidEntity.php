<?php

namespace BackBuilder\Util\BBAbstract;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @category    BackBuilder
 * @package     BackBuilder\?
 * @copyright   Lp digital system
 * @author      Nicolas Dufreche <nicolas.dufreche@lp-digital.fr>
 */
abstract class AUidEntity implements DomainObjectInterface
{

    /**
     * @var string
     * @Id @Column(type="string", name="uid")
     */
    protected $_uid;
    /**
     * @var boolean
     */
    private $_is_new = false;
    
    

    public function __construct($uid = NULL)
    {
        if (is_null($uid)) {
            $uid = md5(uniqid('', TRUE));
            $this->_is_new = true;
        }
        
        $this->_uid = $uid;
    }
    
    public function isNew()
    {
        return $this->_is_new;
    }
    
        
    public function getObjectIdentifier()
    {
        return $this->_uid;
    }
}