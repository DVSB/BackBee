<?php
namespace BackBuilder\Renderer\Adapter;

use Exception,
	ReflectionClass,
	Twig_Error_Loader;

use Twig_Loader_Filesystem,
	Twig_Environment;

use BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\Exception\RendererException,
	BackBuilder\Renderer\IRenderable,
    BackBuilder\Renderer\ARendererAdapter,
	BackBuilder\Util\File;

class Twig extends ARendererAdapter
{
	/**
	 * @var Twig_Loader_Filesystem
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
		$this->loader = new \Twig_Loader_Filesystem(array());
		$this->twig = new \Twig_Environment($this->loader, array(
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

    public function isValidTemplateFile($filename, array $templateDir)
    {
        if (0 === count($templateDir)) {
            return false;
        }

        $this->addDirPathIntoLoaderIfNotExists($templateDir);

        return $this->loader->exists($filename);
    }

    private function addDirPathIntoLoaderIfNotExists(array $templateDir)
    {
        $paths = $this->loader->getPaths();
        foreach ($templateDir as $dir) {
            if (false === in_array($dir, $paths)) {
                $this->loader->addPath($dir);
            }
        }
    }

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
