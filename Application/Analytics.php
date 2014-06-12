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

namespace BackBuilder\Application;

use Symfony\Component\HttpFoundation\ParameterBag;
use BackBuilder\BBApplication;

/**
 * Application Analytics service
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Application
 * @copyright   Lp digital system
 * @author      ken.golovin
 */
class Analytics
{
    /**
     *
     * @var ParameterBag
     */
    protected $params;
    
    /**
     *
     * @var bool
     */
    protected $initialised = false;
    
    /**
     *
     * @var BBApplication
     */
    protected $bbapp;
    
    public function __construct(BBApplication $bbapp)
    {
        $this->bbapp = $bbapp;
        $this->params = new ParameterBag();
    }
    
    /**
     * 
     * @see ParameterBag::get
     */
    public function getParam($path, $default = null)
    {
        if(!$this->initialised) {
            $this->initialise();
        }
        
        return $this->params->get($path, $default, true);
    }
    
    /**
     * 
     * @return ParameterBag
     */
    public function getParams()
    {
        if(!$this->initialised) {
            $this->initialise();
        }
        
        return $this->params;
    }
    
    /**
     * 
     * @see ParameterBag::set
     */
    public function setParam($key, $value)
    {
        if(!$this->initialised) {
            $this->initialise();
        }
        
        return $this->params->set($key, $value);
    }
    
    protected function initialise()
    {
        $this->collectConfigData();
        $this->collectSiteData();
        $this->collectControllerActionData();
    }
    

    protected function collectConfigData()
    {
        $this->params->add($this->bbapp->getConfig()->getSection('analytics'));
    }
    
    protected function collectSiteData()
    {
        $site = $this->bbapp->getContainer()->get('site');
     }
    
    protected function collectControllerActionData()
    {
        
    }
}