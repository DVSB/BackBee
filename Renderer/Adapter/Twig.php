<?php
namespace BackBuilder\Renderer\Adapter;

use Exception,
	ReflectionClass,
	Twig_Error_Loader;

use Twig_Loader_Filesystem,
	Twig_Environment;

use BackBuilder\BBApplication,
	BackBuilder\Renderer\ARenderer,
	BackBuilder\Renderer\IRenderable,
	BackBuilder\Renderer\Exception\RendererException,
	BackBuilder\Util\File;

class Twig extends ARenderer
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
	protected $includeExtensions = array(
		'.twig',
		'.html.twig'
	);

	/**
	 * The file name of the twig template
	 * @var string
	 */
	private $templateFile;

	/**
	 * Constructor
	 * 
	 * @param BackBuilder\BBApplication|null $bbapp  
	 * @param array|null 					 $config
	 */
	public function __construct(BBApplication $bbapp = null, $config = null)
	{
		parent::__construct($bbapp, $config);

		$this->loader = new \Twig_Loader_Filesystem(array());
		$this->twig = new \Twig_Environment($this->loader, array(
			'debug' => null !== $bbapp ? $bbapp->isDebugMode() : false
		));

		$this->setParam('this', $this);
	}

	/**
	 * @see BackBuilder\Renderer\ARenderer::addScriptDir()
	 */
	public function addScriptDir($newDir, $position = 0)
	{
		$this->loader->addPath($newDir);
	}

	/**
	 * @see BackBuilder\Renderer\ARenderer::addLayoutDir()
	 */
	public function addLayoutDir($newDir, $position = 0)
	{
		$this->loader->addPath($newDir);
	}

	/**
     * @see BackBuilder\Renderer\ARenderer::render()
     */
    public function render(IRenderable $object = null, $mode = null, $params = null, $template = null, $ignoreIfRenderModeNotAvailable = true)
    {
    	if (null === $object) {
    		return;
    	}

    	$bbapp = $this->getApplication();
    	if (false === $object->isRenderable() && null === $bbapp->getBBUserToken()) {
    		return;
    	}

    	$this->getApplication()->debug(sprintf(
    		'Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).', 
    		get_class($object), 
    		$object->getUid(), 
    		$mode, 
    		$ignoreIfRenderModeNotAvailable
    	));

    	$renderer = clone $this;
    	$renderer->setObject($object)
 				 ->setMode($mode, $ignoreIfRenderModeNotAvailable)
 				 ->_triggerEvent('prerender');

 		if (null === $renderer->__render) {
            // Rendering a page with layout
            if (is_a($object, '\BackBuilder\NestedNode\Page')) {
                $renderer->setCurrentPage($object);
                $renderer->__render = $renderer->_renderPage($template);
                $bbapp->debug(sprintf('Rendering Page OK'));
            } else {
                // Rendering a content
                $renderer->__render = $renderer->renderContent($params, $template);
            }

            $renderer->_triggerEvent('postrender', null, $renderer->__render);
        }

        return $renderer->__render;
    }

    private function renderContent($params = null, $template = null)
    {
    	try {
    		$mode = null !== $this->getMode() ? $this->getMode() : $this->_object->getMode();
    		$this->templateFile = $template;
    		// Check if $template is null, if so we have to guess the template file name with $this->_object
    		if (null === $this->templateFile && null !== $this->_object) {
    			// Try to get template filename with custom $mode
    			$this->templateFile = $this->getTemplateFile($this->_object, $mode);
    			if (false === $this->templateFile) {
    				// Try to get template filename with $mode from current Twig object
    				$this->templateFile = $this->getTemplateFile($this->_object, $this->getMode());
    			}

    			if (false === $this->templateFile && false === $this->_ignoreIfRenderModeNotAvailable) {
    				// Try to get template filename without any mode
    				$this->templateFile = $this->getTemplateFile($this->_object);
    			}
    		}

    		// If we still not able to get template filename, throw RendererException
    		if (false === $this->templateFile) {
    			throw new RendererException(sprintf(
    				'Unable to find file \'%s\' in path (%s)', 
    				$template, 
    				implode(', ', $this->_scriptdir)
    			), RendererException::SCRIPTFILE_ERROR);
    		}
    	} catch (RendererException $e) { // Unknow template
    		$render = '';
    		// Try to render subcontent
    		if (null !== $this->_object && is_array($this->_object->getData())) {
    			foreach ($this->_object->getData() as $subcontents) {
    				$subcontents = (array) $subcontents;

    				foreach ($subcontents as $subcontent) {
    					if (is_a($subcontent, 'BackBuilder\Renderer\IRenderable')) {
    						$renderer = clone $this;
    						$subcontentRender = $renderer->render(
    							$subcontent, 
    							$this->getMode(), 
    							$params, 
    							$template, 
    							$this->_ignoreIfRenderModeNotAvailable
    						);
    						if (false === $subcontentRender) {
    							throw $e;
    						}

    						$this->_restore();

    						$render .= $subcontentRender;
    					}
    				}
    			}
    		}

    		return $render;
    	}

    	$bbapp = $this->getApplication();
    	// Assign vars and parameters
		if (null !== $this->_object) {
			$draft = $this->_object->getDraft();
			$aClassContentClassname = 'BackBuilder\ClassContent\AClassContent';
			if (true === is_a($this->_object, $aClassContentClassname) && false === $this->_object->isLoaded()) {
				$em = $bbapp->getEntityManager();

				$classname = get_class($this->_object);
				$uid = $this->_object->getUid();

				$em->detatch($this->_object);
				$object = $em->find($classname, $uid);
				if (null !== $object) {
					$this->_object = $object;
					if (null !== $draft) {
						$this->_object->setDraft($draft);
					}
				}

				$this->assign($this->_object->getData());
				$this->setParam($this->_object->getParam());
			}
		}

		if (null !== $params) {
			$params = (array) $params;
			foreach ($params as $param => $value) {
				$this->setParam($param, $value);
			}
		}

		if (null !== $bbapp) {
			$this->bbapp->debug(sprintf(
				'Rendering content `%s(%s)`.', 
				get_class($this->_object), 
				$this->_object->getUid()
			));
		}

		return $this->renderTemplate();
    }

    private function renderPage($layoutFile = null)
    {
    	$this->setNode($this->getObject());

    	$bbapp = $this->getApplication();
    	// Rendering subcontent
    	$contentSet = $this->getObject()->getContentSet();
    	if (null !== $contentSet) {
    		$revision = $bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft(
    			$contentset, 
    			$bbapp->getBBUserToken()
    		);
    		if (null !== $bbapp->getBBUserToken() && null !== $revision) {
    			$contentSet->setDraft($revision);
    		}

    		$layout = $this->getObject()->getLayout();
    		$zones = $layout->getZones();
    		$indexZone = 0;
    		foreach ($contentSet->getData() as $content) {
    			if (true === array_key_exists($indexZone, $zones)) {
    				$zone = $zones[$indexZone];
    				$isMainZone = null !== $zone && true === property_exists($zone, 'mainZone') && true === $zone->mainZone;
    				$this->container()->add($this->render($content, $this->getMode(), array(
    					'class' => 'rootContentSet',
                        'isRoot' => true,
                        'indexZone' => $indexZone++,
                        'isMainZone' => $isMainZone
    				), null, $this->_ignoreIfRenderModeNotAvailable));
    			}
    		}
    	}

    	// Check for a valid layout file
    	$this->templateFile = $layoutFile;
    	if (null === $this->templateFile) {
    		$this->templateFile = $this->_getLayoutFile($this->layout());
    	}

    	if ($this->loader->exists($this->templateFile)) {
    		throw new RendererException(sprintf('Unable to read layout %s.', $layoutFile), RendererException::LAYOUT_ERROR);
    	}

    	if (null !== $bbapp) {
            $this->_application->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));
        }

        return $this->_renderTemplate();
    }

    private function getTemplateFile(IRenderable $object, $mode = null)
    {
    	$template = $this->_getTemplatePath($object);

    	foreach ($this->includeExtensions as $ext) {
    		$filename = $template . ($mode ? '.' . $mode : '') . $ext;
    		if (true === $this->loader->exists($filename)) {
    			return $filename;
    		}
    	}

    	$parentClassname = get_parent_class($object);
    	if (false !== $parentClassname) {
    		$parent = new ReflectionClass($parentClassname);
    		if (false === $parent->isAbstract()) {
    			return $this->_getTemplateFile(new $parentClassname(), $mode, null);
    		}
    	}

    	return false;
    }

    /**
     * @see  BackBuilder\Renderer\ARenderer::partial()
     */
	public function partial($template = null, $params = null)
	{
		$this->templateFile = $template;
		
		if (null !== $params) {
			$params = (array) $params;
			foreach ($params as $param => $value) {
				$this->setParam($param, $value);
			}
		}

		return $this->renderTemplate(true);
	}

	private function renderTemplate($isPartial = false)
	{
		$render = '';
        try {
        	if (false === $isPartial) {
        		$this->_triggetEvent();
        	}

        	$render = $this->twig->render($this->templateFile, $this->getParam());

        	if (null !== $this->_application) {
	        	$this->_application->debug(sprintf('Rendering file `%s`.', $this->templateFile));
	        }
        } catch (Twig_Error_Loader $e) {
        	var_dump(get_class($e)); die;
        	throw new RendererException($e->getMessage() . ' in ' . $this->templateFile, RendererException::RENDERING_ERROR, $e);
        }

        return $render;
	}
}
