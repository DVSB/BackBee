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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        
    }

    /**
     * @deprecated since version 1.0
     * @param \BackBuilder\BBApplication $application
     * @codeCoverageIgnore
     */
    public function __onInit(BBApplication $application)
    {
        $this->initService($application);
    }

    /**
     * @param \BackBuilder\BBApplication $application
     * @codeCoverageIgnore
     */
    public function initService(BBApplication $application)
    {
        $this->_application = $this->bbapp = $application;
        $this->_em = $application->getEntityManager();
    }

    /**
     * Returns the current application
     * @return \BackBuilder\BBApplication
     * @codeCoverageIgnore
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Returns the current entity manager of the BackBuilder application
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Exception\MissingApplicationException Occurs if none BackBuilder application is defined
     */
    public function getEntityManager()
    {
        if (null === $this->_application) {
            throw new \BackBuilder\Exception\MissingApplicationException('None BackBuilder application defined');
        }

        return $this->_application->getEntityManager();
    }

    /**
     * Checks if the attributes are granted against the current token.
     * @param mixed $attributes
     * @param mixed|null $object
     * @return boolean Return TRUE if current token if granted
     * @throws \BackBuilder\Exception\MissingApplicationException Occurs if none BackBuilder application is defined
     * @throws \BackBuilder\Security\Exception\ForbiddenAccessException Occurs if the current token have not the permission
     */
    public function isGranted($attributes, $object = null)
    {
        if (null === $this->_application) {
            throw new \BackBuilder\Exception\MissingApplicationException('None BackBuilder application defined');
        }

        $securityContext = $this->_application->getSecurityContext();

        if (false === $securityContext->isGranted('sudo')) {
            if (null !== $securityContext->getACLProvider()
                    && false === $securityContext->isGranted($attributes, $object)) {
                throw new \BackBuilder\Security\Exception\ForbiddenAccessException('Forbidden acces');
            }
        }

        return true;
    }

    public function __get($name)
    {
        if ($name === 'bbapp')
            return $this->_application;

        if ($name === 'application' || $name === 'em')
            return $this->{'_' . $name};
    }

}