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

namespace BackBuilder\Renderer\Adapter;

use Twig_Environment,
    Twig_Extension_Debug;
use BackBuilder\Renderer\Adapter\TwigLoaderFilesystem,
    BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\Exception\RendererException,
    BackBuilder\Renderer\ARendererAdapter;

/**
 * twig renderer adapter for BackBuilder\Renderer\Renderer
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Twig extends ARendererAdapter
{

    /**
     * @var BackBuilder\Renderer\Adapter\TwigLoaderFilesystem
     */
    private $loader;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * Extensions to include in searching file
     * @var array
     */
    protected $includeExtensions = array(
        '.twig',
        '.html.twig',
        '.xml.twig'
    );

    /**
     * Constructor
     * 
     * @param BackBuilder\BBApplication|null $bbapp  
     * @param array|null 					 $config
     */
    public function __construct(ARenderer $renderer)
    {
        parent::__construct($renderer);

        $this->loader = new TwigLoaderFilesystem(array());       
        
        $bbapp = $this->renderer->getApplication();
        $isDebugMode = null !== $bbapp ? $bbapp->isDebugMode() : false;
        
        $this->twig = new Twig_Environment($this->loader, array(
            'debug' => $isDebugMode
        ));

        if (true === $isDebugMode) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        foreach ($bbapp->getContainer()->findTaggedServiceIds('twig.extension') as $id => $datas) {
            $this->twig->addExtension($bbapp->getContainer()->get($id));
        }
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::getManagedFileExtensions()
     */
    public function getManagedFileExtensions()
    {
        return $this->includeExtensions;
    }

    /**
     * Check if $filename exists in directories provided by $templateDir
     * 
     * @param  [type]  $filename    
     * @param  array   $templateDir 
     * @return boolean true if the file was found and is readable
     */
    public function isValidTemplateFile($filename, array $templateDir)
    {
        if (0 === count($templateDir)) {
            return false;
        }

        $this->addDirPathIntoLoaderIfNotExists($templateDir);

        return $this->loader->exists($filename);
    }

    /**
     * Add dir path into loader only if it not already exists
     * 
     * @param array $templateDir 
     */
    private function addDirPathIntoLoaderIfNotExists(array $templateDir)
    {
        $paths = $this->loader->getPaths();
        if ((count($paths) !== count($templateDir)) || (0 < count(array_diff($paths, $templateDir)))) {
            $this->loader->removeAllPaths();
            try{
                $this->loader->setPaths($templateDir);
            }catch(\Twig_Error_Loader $e){
                //@Todo what to do when one of path does not exist
            }
        }
    }

    /**
     * Generate the render of $filename template with $params and $vars
     * 
     * @param  string $filename    
     * @param  array  $templateDir 
     * @param  array  $params      
     * @param  array  $vars        
     * @return string              
     */
    public function renderTemplate($filename, array $templateDir, array $params = array(), array $vars = array())
    {
        $this->addDirPathIntoLoaderIfNotExists($templateDir);
        $render = '';
        try {
            $params['this'] = $this;
            $params = array_merge($params, $vars);
            $render = $this->twig->render($filename, $params);
        } catch (\Exception $e) {
            throw new RendererException(
                    $e->getMessage() . ' in ' . $filename, RendererException::RENDERING_ERROR, $e
            );
        }

        return $render;
    }

    /**
     * 
     * @param string $filename
     * @return \Twig_TemplateInterface
     */
    public function loadTemplate($filename)
    {
        return $this->twig->loadTemplate($filename);
    }

}
