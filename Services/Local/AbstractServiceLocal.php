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

namespace BackBuilder\Services\Local;

use BackBuilder\BBApplication;

/**
 * Abstract class for local RPC service
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class AbstractServiceLocal implements IServiceLocal
{
    /**
     * Directory list
     * @var \stdClass
     */
    protected $_dir;
    /**
     * Bundle name identifier
     * @var string
     */
    protected $identifier;
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
        $this->_application = $application;
        $this->_em = $application->getEntityManager();
        $this->_dir = new \stdClass();
        if (NULL !== $application && null !== $this->identifier) {
            $this->_dir->bundle = implode(DIRECTORY_SEPARATOR, array($this->application->getBundle($this->identifier)->getResourcesDir(), 'Templates', 'scripts'));
            $this->_dir->bundle .= DIRECTORY_SEPARATOR;
        }
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

    /**
     * Render template
     *
     * @param string $template
     * @param array $params
     * @return string
     */
    public function render($template, $params = array())
    {
        $result = "";
        if (isset($template) && is_string($template)) {
            $this->_assignParams($params);
            $result = $this->application->getRenderer()->partial($template);
        }

        return $result;
    }

    private function _assignParams($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $param) {
                $this->application->getRenderer()->assign($key, $param);
            }
        }
        $this->application->getRenderer()->assign('dir', $this->_dir);
    }

    public function __get($name)
    {
        if ($name === 'bbapp') {
            return $this->_application;
        }
        if ($name === 'application' || $name === 'em') {
            return $this->{'_' . $name};
        }
    }

}