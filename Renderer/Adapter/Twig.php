<?php

namespace BackBuilder\Renderer\Adapter;

use Exception,
	Twig_Error_Loader;

use Twig_Environment;

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
	protected $includeExtensions = array('.twig');

	/**
	 * Constructor
	 * 
	 * @param BackBuilder\BBApplication|null $bbapp  
	 * @param array|null 					 $config
	 */
	public function __construct(ARenderer $renderer)
	{
        parent::__construct($renderer);

        $bbapp = $this->renderer->getApplication();
		$this->loader = new TwigLoaderFilesystem(array());
		$this->twig = new Twig_Environment($this->loader, array(
			'debug' => null !== $bbapp  ? $bbapp->isDebugMode() : false
		));
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
        foreach ($templateDir as $dir) {
            if (false === in_array($dir, $paths)) {
                $this->loader->addPath($dir);
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
        } catch (Exception $e) {
            throw new RendererException(
                $e->getMessage() . ' in ' . $filename, RendererException::RENDERING_ERROR, $e
            );
        }

        return $render;
    }	
}
