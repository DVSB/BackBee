<?php

namespace BackBuilder\Util;

use BackBuilder\BBApplication;
use Doctrine\ORM\EntityManager;

class ObjectIdentityRetrieval
{

    private $_identifier;
    private $_class;
    private $_em;
    private static $_pattern = '/\((\w+),(.+)\)/';

    public function __construct(EntityManager $em, $identifier, $class)
    {
        $this->_em = $em;
        $this->_class = $class;
        $this->_identifier = $identifier;
    }

    public static function build(BBApplication $application, $objectIdentity)
    {
        $matches = array();
        preg_match(self::$_pattern, $objectIdentity, $matches);

        return new self($application->getEntityManager(), trim($matches[1]), trim($matches[2]));
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getObject()
    {
        return $this->_em->getRepository($this->_class)->find($this->_identifier);
    }

}