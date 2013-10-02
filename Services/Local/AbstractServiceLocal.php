<?php

namespace BackBuilder\Services\Local;

use BackBuilder\BBApplication;

/**
 * Abstract class for local RPC service
 * @category    BackBuilder
 * @package     BackBuilder\Services\Local
 * @copyright   Lp digital system
 * @author      n.bremont
 */
class AbstractServiceLocal implements IServiceLocal
{
    /**
     * Current BackBuilder application
     * @var \BackBuilder\BBApplication
     */
    private $_application;

    /**
     * Current EntityManager for the application
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * Class constructor
     */
    public function __construct() {}
    
    /**
     * @deprecated since version 1.0
     * @param \BackBuilder\BBApplication $application
     */
    public function __onInit(BBApplication $application)
    {
        $this->initService($application);
    }
    
    /**
     * @param \BackBuilder\BBApplication $application
     */
    public function initService(BBApplication $application)
    {
        $this->_application = $this->bbapp = $application;
        $this->_em = $application->getEntityManager();
    }

    public function __get($name)
    {
        if ($name === 'bbapp')
            return $this->_application;
        
        if ($name === 'application' || $name === 'em')
            return $this->{'_' . $name};
    }
}