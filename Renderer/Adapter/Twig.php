<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Renderer\Adapter;

use BackBee\Renderer\AbstractRenderer;
use BackBee\Renderer\AbstractRendererAdapter;
use BackBee\Renderer\Exception\RendererException;

/**
 * twig renderer adapter for BackBee\Renderer\Renderer.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Twig extends AbstractRendererAdapter
{
    /**
     * @var BackBee\Renderer\Adapter\TwigLoaderFilesystem
     */
    private $loader;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * Extensions to include in searching file.
     *
     * @var array
     */
    protected $extensions = array(
        '.twig',
        '.html.twig',
        '.xml.twig',
    );

    /**
     * Twig adapter constructor.
     *
     * @param AbstractRenderer $renderer
     */
    public function __construct(AbstractRenderer $renderer)
    {
        parent::__construct($renderer);

        $this->loader = new TwigLoaderFilesystem(array());

        $application = $this->renderer->getApplication();

        $this->twig = new \Twig_Environment($this->loader);

        if (true === $application->isDebugMode()) {
            $this->twig->enableDebug();
            $this->twig->addExtension(new \Twig_Extension_Debug());
        } elseif (false === $application->isClientSAPI()) {
            $this->twig->enableAutoReload();
            $cache_directory = $application->getCacheDir().DIRECTORY_SEPARATOR.'twig';
            $this->setTwigCache($cache_directory);
        }
    }

    /**
     * Add Twig extension.
     *
     * @param \Twig_ExtensionInterface $extension
     */
    public function addExtension(\Twig_ExtensionInterface $extension)
    {
        $this->twig->addExtension($extension);
    }

    /**
     * Set Twig cache directory.
     *
     * @param string $cacheDirectory
     */
    public function setTwigCache($cacheDirectory)
    {
        if (
            false === is_dir($cacheDirectory)
            && (false === is_writable(dirname($cacheDirectory)) || false === @mkdir($cacheDirectory, 0755))
        ) {
            throw new RendererException(
                sprintf('Unable to create twig cache "%s"', $cacheDirectory),
                RendererException::RENDERING_ERROR
            );
        }

        if (false === is_writable($cacheDirectory)) {
            throw new RendererException(
                sprintf('Twig cache "%s" is not writable', $cacheDirectory),
                RendererException::RENDERING_ERROR
            );
        }

        $this->twig->setCache($cacheDirectory);
    }
    /**
     * @see BackBee\Renderer\RendererAdapterInterface::getManagedFileExtensions()
     */
    public function getManagedFileExtensions()
    {
        return $this->extensions;
    }

    /**
     * Check if $filename exists in directories provided by $templateDir.
     *
     * @param string $filename
     * @param array  $templateDir
     *
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
     * Add dir path into loader only if it not already exists.
     *
     * @param array $templateDir
     */
    private function addDirPathIntoLoaderIfNotExists(array $templateDir)
    {
        $paths = $this->loader->getPaths();
        if ((count($paths) !== count($templateDir)) || (0 < count(array_diff($paths, $templateDir)))) {
            $this->loader->removeAllPaths();
            try {
                $this->loader->setPaths($templateDir);
            } catch (\Twig_Error_Loader $e) {}
        }
    }

    /**
     * Generate the render of $filename template with $params and $vars.
     *
     * @param string $filename
     * @param array  $templateDir
     * @param array  $params
     * @param array  $vars
     *
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
        } catch (\BackBee\FrontController\Exception\FrontControllerException $fe) {
            throw $fe;
        } catch (\Exception $e) {
            throw new RendererException(
                $e->getMessage().' in '.$filename, RendererException::RENDERING_ERROR, $e
            );
        }

        return $render;
    }

    /**
     * @param string $filename
     *
     * @return \Twig_TemplateInterface
     */
    public function loadTemplate($filename)
    {
        return $this->twig->loadTemplate($filename);
    }
}
