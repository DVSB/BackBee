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

namespace BackBuilder\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Forms,
    Symfony\Component\Form\FormBuilderInterface;


use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

use BackBuilder\IApplication;

/**
 * Base Controler
 *
 * @category    BackBuilder
 * @package     BackBuilder\Controller
 * @copyright   Lp system
 * @author      k.golovin
 */
class Controller
{
    /**
     * Current BackBuilder application
     * @var \BackBuilder\BBApplication
     */
    protected $_application;

    /**
     * Class constructor
     *
     * @access public
     * @param IApplication $application The current BBapplication
     */
    public function __construct(IApplication $application = null) 
    {
        $this->_application = $application;
    }
    
    /**
     * Returns current Backbuilder application
     *
     * @access public
     * @return \BackBuilder\DependencyInjection\ContainerBuilder\IApplication
     */
    public function getApplication() 
    {
        return $this->_application;
    }
    
    
    /**
     * Returns the application's DIC
     *
     * @access public
     * @return ContainerBuilder
     */
    public function getContainer() 
    {
        return $this->_application->getContainer();
    }
    
    
    /**
     * Returns the current request
     *
     * @access public
     * @return Request
     */
    public function getRequest() 
    {
        return $this->_application->getRequest();
    }
    
    /**
     * 
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->_application->getEntityManager();
    }
    
    /**
     * 
     * @return FormBuilderInterface
     */
    public function createFormBuilder($data)
    {
        $validator = Validation::createValidator();
                
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();
        
        return $formFactory->createBuilder('form', $data);
    }
    
    /**
     * 
     * @param string $view
     * @param array $parameters
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        if(null === $response) {
            $response = new Response();
        }
        
        // locate the full path to the view
        $matches = null;
        preg_match("/Bundle\\\([a-zA-Z0-9]+Bundle)\\\/i", get_class($this), $matches); 
        if(isset($matches[1])) {
            $bundleName = $matches[1];
            
            // check that the view is not the full path already
            $matchesView = null;
            preg_match("/Bundle\\\\([a-zA-Z0-9]+Bundle)\\\\/i", $view, $matchesView); 

            if(!isset($matchesView[1])) {
                // view is not the full path, so prepend the bundle views dir
                $bundle = $this->getApplication()->getBundle($bundleName);
                $bundle->getBaseDir();
                $view = $bundle->getBaseDir() . '/Ressources/views/' . $view;
            }
        }

        try {
            $this->renderer = $this->getApplication()->getRenderer();
            $content = $this->renderer->partial($view, $parameters);
            $response->setContent($content);
        } catch(\Exception $e) {
            var_dump($e);exit;
            throw $e;
        }
        
        return $response;
    }
 
}
