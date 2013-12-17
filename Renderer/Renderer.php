<?php

namespace BackBuilder\Renderer;

use BackBuilder\BBApplication,
	BackBuilder\Renderer\ARenderer,
	BackBuilder\Renderer\Exception\RendererException,
	BackBuilder\Renderer\IRenderable,
	BackBuilder\Renderer\IRendererAdapter;

use Symfony\Component\HttpFoundation\ParameterBag;

class Renderer extends ARenderer
{
	/**
	 * contains every IRendererAdapter added by user
	 * @var ParameterBag
	 */
	private $rendererAdapters;

	/**
	 * contains every extensions that Renderer can manage thanks to registered IRendererAdapter
	 * @var ParameterBag
	 */
	private $manageableExt;

	/**
	 * key of the default adapter to use when there is a conflict
	 * @var string
	 */
	private $defaultAdapter;

	/**
     * The file path to the template
     * @var string
     */
    private $templateFile;

    /**
     * Constructor
     * 
     * @param [type]  $bbapp                   [description]
     * @param [type]  $config                  [description]
     * @param boolean $autoloadRendererApdater [description]
     */
	public function __construct(BBApplication $bbapp = null, $config = null, $autoloadRendererApdater = true)
	{
		parent::__construct($bbapp, $config);
		$this->rendererAdapters = new ParameterBag();
		$this->manageableExt = new ParameterBag();

		if (true === $autoloadRendererApdater) {
			$rendererConfig = $this->getApplication()->getConfig()->getRendererConfig();
			$adapters = (array) $rendererConfig['adapter'];
			foreach ($adapters as $a) {
				$this->addRendererAdapter(new $a());
			}
		}

		/*$this->_scriptdir = array(
			'c:\Work'
		);
		$this->setParam('te_name', 'phtml');
		$this->templateFile = 'a/test.phtml';
		var_dump($this->renderTemplate()); die;*/
		//var_dump($this->isValidTemplateFile()); die;
	}

	/**
	 * [addRendererAdapter description]
	 * @param IRendererAdapter $rendererAdapter [description]
	 */
	public function addRendererAdapter(IRendererAdapter $rendererAdapter)
	{
		$key = $this->getRendererAdapterKey($rendererAdapter);
		if (false === $this->rendererAdapters->has($key)) {
			$this->rendererAdapters->set($key, $rendererAdapter);
			$this->addManagedExtensions($rendererAdapter);
			if ($rendererAdapter instanceof \BackBuilder\Renderer\ARendererAdapter) {
				$rendererAdapter->setHelperManager($this->helperManager);
			}
		}
	}

	/**
	 * [getRendererAdapterKey description]
	 * @param  IRendererAdapter $rendererAdapter [description]
	 * @return [type]                            [description]
	 */
	private function getRendererAdapterKey(IRendererAdapter $rendererAdapter)
	{
		$key = explode(DIRECTORY_SEPARATOR, get_class($rendererAdapter));
		
		return strtolower($key[count($key) - 1]);
	}

	/**
	 * [addManagedExtensions description]
	 * @param IRendererAdapter $rendererAdapter [description]
	 */
	private function addManagedExtensions(IRendererAdapter $rendererAdapter)
	{
		$key = $this->getRendererAdapterKey($rendererAdapter);
		foreach ($rendererAdapter->getManagedFileExtensions() as $ext) {
			$rendererAdapters = array($key);
			if ($this->manageableExt->has($ext)) {
				$rendererAdapters = $this->manageableExt->get($ext);
				$rendererAdapters[] = $key;
			}
			
			$this->manageableExt->set($ext, $rendererAdapters);
		}
	}

	/**
	 * [getRightAdapter description]
	 * @param  array  $adapters [description]
	 * @return [type]           [description]
	 */
	private function getRightAdapter(array $adapters)
	{
		$adapter = null;
		if (1 < count($adapters) && true === in_array($this->defaultAdapter, $adapters)) {
			$adapter = $this->defaultAdapter;
		} else {
			$adapter = $adapters[0];
		}

		return $adapter;
	}

	/**
	 * [determineWhichAdapterToUse description]
	 * @return [type] [description]
	 */
	private function determineWhichAdapterToUse()
	{
		if (null === $this->templateFile || false === is_string($this->templateFile)) {
			return null;
		}

		$pieces = explode('.', $this->templateFile);
		if (1 > count($pieces)) {
			return null;
		}

		$ext = '.' . $pieces[count($pieces) - 1];
		$adaptersForExt = $this->manageableExt->get($ext);
		if (false === is_array($adaptersForExt) || 0 === count($adaptersForExt)) {
			return null;
		}

		$adapter = $this->getRightAdapter($adaptersForExt);

		return $this->rendererAdapters->get($adapter);
	}

	/**
	 * @param  string $adapterKey 
	 * @return boolean
	 */
	public function defaultAdapter($adapterKey)
	{
		$exists = false;
		if (true === in_array($adapterKey, $this->rendererAdapters->keys())) {
			$this->defaultAdapter = $adapterKey;
			$exists = true;
		}

		return $exists;
	}

	public function render(IRenderable $obj = null, $mode = null, $params = null, $template = null, $ignoreModeIfNotSet = true)
	{
		if (null === $obj) {
			return null;
		}

		$bbapp = $this->getApplication();
		if (false === $obj->isRenderable() && null === $bbapp->getBBUserToken()) {
			return null;
		}

		$bbapp->debug(sprintf(
			'Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).', 
    		get_class($obj), $obj->getUid(), $mode, $ignoreModeIfNotSet
    	));

    	$renderer = clone $this;
    	$renderer->setObject($obj)
    		->setMode($mode, $ignoreModeIfNotSet)
    		->_triggerEvent('prerender');

    	if (null === $renderer->__render) {
    		// Rendering a page with layout
    		if (true === is_a($obj, '\BackBuilder\NestedNode\Page')) {
    			$renderer->setCurrentPage($obj);
    			$renderer->__render = $renderer->renderPage($template);
    			// phtml need to do something here (to insert header and footer script)
    			$bbapp->debug('Rendering Page OK');
    		} else {
    			// Rendering a content
    			$renderer->__render = $renderer->renderContent($params, $template);
    		}

    		$renderer->_triggerEvent('postrender', null, $renderer->__render);
    	}

    	return $renderer->__render;
	}

	/**
     * Render a page object
     * 
     * @param string $layoutfile A force layout script to be rendered
     * @return string The rendered output
     * @throws RendererException
     */
	private function renderPage($layoutFile = null)
	{
		$this->setNode($this->getObject());

		$bbapp = $this->getApplication();
		// Rendering subcontent
		if (null !== $contentSet = $this->getObject()->getContentSet()) {
			$revision = $bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft(
    			$contentSet, 
    			$bbapp->getBBUserToken()
    		);
    		if (null !== $bbapp->getBBUserToken() && null !== $revision) {
    			$contentSet->setDraft($revision);
    		}

    		$layout = $this->getObject()->getLayout();
    		$zones = $layout->getZones();
    		$zoneIndex = 0;
    		foreach ($contentSet->getData() as $content) {
    			if (true === array_key_exists($zoneIndex, $zones)) {
    				$zone = $zones[$zoneIndex];
    				$isMain = null !== $zone && true === property_exists($zone, 'mainZone') && true === $zone->mainZone;
    				$this->container()->add($this->render($content, $this->getMode(), array(
    					'class' 		=> 'rootContentSet',
                        'isRoot' 		=> true,
                        'indexZone' 	=> $zoneIndex++,
                        'isMainZone' 	=> $isMain
    				), null, $this->_ignoreIfRenderModeNotAvailable));
    			}
    		}
		}

		// Check for a valid layout file
		$this->templateFile = $layoutFile;
		if (null === $this->templateFile) {
			$this->templateFile = $this->_getLayoutFile($this->layout());
		}

		if (false === $this->isValidTemplateFile()) {
			throw new RendererException(sprintf('Unable to read layout %s.', $layoutFile), RendererException::LAYOUT_ERROR);
		}

		$bbapp->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));

		return $this->renderTemplate(false, true);
	}

    /**
     * Render a ClassContent object
     * @param array $params A Force set of parameters to render the object
     * @param string $template A force template script to be rendered
     * @return string The rendered output
     * @throws RendererException
     */
    private function renderContent($params = null, $template = null)
	{
		try {
			$mode = null !== $this->getMode() ? $this->getMode() : $this->_object->getMode();
			$this->templateFile = $template;
			if (null === $this->templateFile && null !== $this->_object) {
				$this->templateFile = $this->getTemplateFile($this->_object, $mode);
				if (false === $this->templateFile) {
					$this->templateFile = $this->getTemplateFile($this->_object, $this->getMode());
				}

				if (false === $this->templateFile && false === $this->_ignoreIfRenderModeNotAvailable) {
					$this->templateFile = $this->getTemplateFile($this->_object);
				}
			}

			if (false === $this->isValidTemplateFile) {
				throw new RendererException(sprintf(
					'Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)
				), RendererException::SCRIPTFILE_ERROR);
			}
		} catch (RendererException $e) {
			$render = '';

			// Unknown template, try to render subcontent
			if (null !== $this->_object && true === is_array($this->_object->getData())) {
				foreach ($this->_object->getData() as $subcontents) {
					$subcontents = (array) $subcontents;

					foreach ($subcontents as $sc) {
						if (true === is_a($sc, 'BackBuilder\Renderer\IRenderable')) {
							$renderer = clone $this;
							$scRender = $renderer->render(
								$sc, 
								$this->getMode(), 
								$params, 
								$template, 
								$this->_ignoreIfRenderModeNotAvailable
							);
							if (false === $scRender) {
								throw $e;
							}

							$this->_restore();

							$render .= $scRender;
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
				// trying to refresh unloaded content
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
			}

			$this->assign($this->_object->getData());
			$this->setParam($this->_object->getParam());
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

	/**
	 * [getTemplateFile description]
	 * @param  IRenderable $object [description]
	 * @param  [type]      $mode   [description]
	 * @return [type]              [description]
	 */
	private function getTemplateFile(IRenderable $object, $mode = null)
	{
		$tmpStorage = $this->templateFile;
		$template = $this->_getTemplatePath($object);
		foreach ($this->manageableExt->keys() as $ext) {
			$this->templateFile = $template . (null !== $mode ? '.' . $mode : '') . $ext;
			if (true === $this->isValidTemplateFile()) {
				$filename = $this->templateFile;
				$this->templateFile = $tmpStorage;

				return $filename;
			}
		}

		if ($parentClassname = get_parent_class($object)) {
            $parent = new \ReflectionClass($parentClassname);
            if (!$parent->isAbstract()) {
                return $this->getTemplateFile(new $parentClassname(), $mode, null);
            }
        }

        return false;
	}

	/**
	 * [isValidTemplateFile description]
	 * @param  boolean $isLayout [description]
	 * @return boolean           [description]
	 */
	private function isValidTemplateFile($isLayout = false)
	{
		$adapter = $this->determineWhichAdapterToUse();
		if (null === $adapter) {
			return false;
		}
		
		return $adapter->isValidTemplateFile(
			$this->templateFile,
			true === $isLayout ? $this->_layoutdir : $this->_scriptdir
		);
	}

	/**
	 * [renderTemplate description]
	 * @param  boolean $isPartial [description]
	 * @param  boolean $isLayout  [description]
	 * @return [type]             [description]
	 */
	private function renderTemplate($isPartial = false, $isLayout = false)
	{
		$adapter = $this->determineWhichAdapterToUse();
		$dirs = true === $isLayout ? $this->_layoutdir : $this->_scriptdir;
		if (null === $adapter) {
			throw new RendererException(sprintf(
				'Unable to find file \'%s\' in path (%s)', 
				$this->_templateFile, 
				implode(', ', $dirs)
			), RendererException::SCRIPTFILE_ERROR);
		}

		$this->getApplication()->debug(sprintf('Rendering file `%s`.', $this->_templateFile));
		if (false === $isPartial) {
			$this->_triggerEvent();
		}

		return $adapter->renderTemplate($this->templateFile, $dirs, $this->getParam());
	}
}
