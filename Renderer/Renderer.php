<?php

namespace BackBuilder\Renderer;

use BackBuilder\BBApplication,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\NestedNode\Page,
    BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\Exception\RendererException,
    BackBuilder\Renderer\IRenderable,
    BackBuilder\Renderer\IRendererAdapter,
    BackBuilder\Site\Layout,
    BackBuilder\Util\String;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Renderer engine class; able to manage multiple template engine
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
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
     * @param BBApplication  $bbapp                   
     * @param array|null  	 $config                  
     * @param boolean 		 $autoloadRendererApdater 
     */
    public function __construct(BBApplication $bbapp = null, $config = null, $autoloadRendererApdater = true)
    {
        parent::__construct($bbapp, $config);
        $this->rendererAdapters = new ParameterBag();
        $this->manageableExt = new ParameterBag();

        if (true === $autoloadRendererApdater) {
            $rendererConfig = $this->getApplication()->getConfig()->getRendererConfig();
            $adapters = (array) $rendererConfig['adapter'];
            foreach ($adapters as $adapter) {
                $this->addRendererAdapter(new $adapter($this));
            }
        }
    }

    public function __clone()
    {
        parent::__clone();

        $this->updateRendererAdapters();
    }

    protected function _restore()
    {
        parent::_restore();

        $this->updateRendererAdapters();

        return $this;
    }

    /**
     * Update every registered renderer adapters of the current renderer instance
     * by updating their ARenderer; its method is used at clone and unset of
     * ARenderer
     */
    private function updateRendererAdapters()
    {
        foreach ($this->rendererAdapters->all() as $ra) {
            $ra->setRenderer($this);
        }
    }

    /**
     * Register a renderer adapter ($rendererAdapter); this method also set
     * current $rendererAdapter as default adapter if it is not set
     * 
     * @param IRendererAdapter $rendererAdapter 
     */
    public function addRendererAdapter(IRendererAdapter $rendererAdapter)
    {
        $key = $this->getRendererAdapterKey($rendererAdapter);
        if (false === $this->rendererAdapters->has($key)) {
            $this->rendererAdapters->set($key, $rendererAdapter);
            $this->addManagedExtensions($rendererAdapter);
        }

        if (null === $this->defaultAdapter) {
            $this->defaultAdapter = $key;
        }
    }

    /**
     * Compute a key for renderer adapter ($rendererAdapter)
     * 
     * @param  IRendererAdapter $rendererAdapter 
     * @return string
     */
    private function getRendererAdapterKey(IRendererAdapter $rendererAdapter)
    {
        $key = explode(DIRECTORY_SEPARATOR, get_class($rendererAdapter));

        return strtolower($key[count($key) - 1]);
    }

    /**
     * Extract managed extensions from rendererAdapter and store it
     * 
     * @param IRendererAdapter $rendererAdapter 
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
     * Returns an adapter containing in $adapeters; it will returns in prior
     * the defaultAdpater if it is in $adapters or the first adapter found
     * 
     * @param  array  $adapters contains object of type IRendererAdapter
     * @return IRendererAdapter
     */
    private function getRightAdapter(array $adapters)
    {
        $adapter = null;
        if (1 < count($adapters) && true === in_array($this->defaultAdapter, $adapters)) {
            $adapter = $this->defaultAdapter;
        } else {
            $adapter = reset($adapters);
        }

        return $adapter;
    }

    /**
     * Returns the right adapter to use according to the filename extension
     * 
     * @return IRendererAdapter
     */
    private function determineWhichAdapterToUse($filename = null)
    {
        if (null === $filename || false === is_string($filename)) {
            return null;
        }

        $pieces = explode('.', $filename);
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
     * Set the adapter referenced by $adapterKey as defaultAdapter to use in conflict
     * case; the default adapter is also considered by self::getRightAdapter()
     * 
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

    /**
     * Return template file extension of the default adapter
     * @return String
     */
    public function getDefaultAdapterExt()
    {
        $managedExt = $this->rendererAdapters->get($this->defaultAdapter)->getManagedFileExtensions();
        
        return array_shift($managedExt);
    }

    /**
     * @see BackBuilder\Renderer\IRenderer::render()
     */
    public function render(IRenderable $obj = null, $mode = null, $params = null, $template = null, $ignoreModeIfNotSet = false)
    {
        if (null === $obj) {
            return null;
        }

        $bbapp = $this->getApplication();
        if (false === $obj->isRenderable() && null === $bbapp->getBBUserToken()) {
            return null;
        }

        $bbapp->debug(sprintf(
            'Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).', get_class($obj), $obj->getUid(), $mode, $ignoreModeIfNotSet
        ));

        $parent = $this->getObject();

        $renderer = clone $this;

        $renderer->setObject($obj)
                ->setMode($mode, $ignoreModeIfNotSet)
                ->_triggerEvent('prerender');

        if (
            null !== $renderer->getClassContainer() && 
            ($renderer->getClassContainer() instanceof AClassContent) && 
            null === $renderer->getCurrentElement()
        ) {
            $renderer->tryResolveParentObject($renderer->getClassContainer(), $obj);
        }

        if (null === $renderer->__render) {
            // Rendering a page with layout
            if (true === is_a($obj, '\BackBuilder\NestedNode\Page')) {
                $renderer->setCurrentPage($obj);
                $renderer->__render = $renderer->renderPage($template);
                $renderer->insertHeaderAndFooterScript();
                $bbapp->debug('Rendering Page OK');
            } else {

                // Rendering a content
                $renderer->__render = $renderer->renderContent($params, $template);
            }

            $renderer->_triggerEvent('postrender', null, $renderer->__render);
        }

        $render = $renderer->__render;
        $this->_restore();
        unset($renderer);

        return $render;
    }

    public function tryResolveParentObject(AClassContent $parent, AClassContent $element)
    {
        foreach ($parent->getData() as $key => $values) {
            if (false === is_array($values)) {
                $values = array($values);
            }

            foreach ($values as $value) {
                if ($value instanceof AClassContent) {
                    if (false === $value->isLoaded()) {
                        // try to load subcontent
                        if (null !== $subcontent = $this->getApplication()
                                ->getEntityManager()
                                ->getRepository(\Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($value))
                                ->load($value, $this->getRenderer()->getApplication()->getBBUserToken())) {
                            $value = $subcontent;
                        }
                    }
                    
                    if (true === $element->equals($value)) {
                        $this->__currentelement = $key;
                        $this->__object = $parent;
                        $this->_parentuid = $parent->getUid();
                    } else {
                        $this->tryResolveParentObject($value, $element);
                    }
                }

                if (null !== $this->_element_name) {
                    break;
                }
            }

            if (null !== $this->_element_name) {
                break;
            }
        }
    }

    /**
     * @see BackBuilder\Renderer\IRenderer::partial()
     */
    public function partial($template = null, $params = null)
    {
        $this->templateFile = $template;

        // Assign parameters
        if (null !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value) {
                $this->setParam($param, $value);
            }
        }

        return $this->renderTemplate(true);
    }

    /**
     * @see BackBuilder\Renderer\IRenderer::error()
     */
    public function error($errorCode, $title = null, $message = null, $trace = null)
    {
        $found = false;
        foreach ($this->manageableExt->keys() as $ext) {
            $this->templateFile = 'error' . DIRECTORY_SEPARATOR . $errorCode . $ext;
            if (true === $this->isValidTemplateFile($this->templateFile, true)) {
                $found = true;
                break;
            }
        }

        if (false === $found) {
            foreach ($this->manageableExt->keys() as $ext) {
                $this->templateFile = 'error' . DIRECTORY_SEPARATOR . 'default' . $ext;
                if (true === $this->isValidTemplateFile($this->templateFile)) {
                    $found = true;
                    break;
                }
            }
        }

        if (false === $found) {
            return false;
        }

        $this->assign('error_code', $errorCode);
        $this->assign('error_title', $title);
        $this->assign('error_message', $message);
        $this->assign('error_trace', $trace);

        return $this->renderTemplate(false, true);
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
            $bbUserToken = $bbapp->getBBUserToken();
            $revisionRepo = $bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision');
            if (null !== $bbUserToken && null !== $revision = $revisionRepo->getDraft($contentSet, $bbUserToken)) {
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
                                'class' => 'rootContentSet',
                                'isRoot' => true,
                                'indexZone' => $zoneIndex++,
                                'isMainZone' => $isMain
                                    ), null, $this->_ignoreIfRenderModeNotAvailable));
                }
            }
        }

        // Check for a valid layout file
        $this->templateFile = $layoutFile;
        if (null === $this->templateFile) {
            $this->templateFile = $this->_getLayoutFile($this->layout());
        }

        if (false === $this->isValidTemplateFile($this->templateFile, true)) {
            throw new RendererException(sprintf('Unable to read layout %s.', $this->templateFile), RendererException::LAYOUT_ERROR);
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

            if (false === $this->isValidTemplateFile($this->templateFile)) {
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
                            $scRender = $this->render(
                                    $sc, $this->getMode(), $params, $template, $this->_ignoreIfRenderModeNotAvailable
                            );
                            if (false === $scRender) {
                                throw $e;
                            }

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

                $em->detach($this->_object);
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
            $bbapp->debug(sprintf('Rendering content `%s(%s)`.', get_class($this->_object), $this->_object->getUid()));
        }

        return $this->renderTemplate();
    }

    /**
     * Try to compute and guess a valid filename for $object:
     * 		- on success return string which is the right filename with its extension
     * 		- on fail return false
     * 		
     * @param  IRenderable $object 
     * @param  [type]      $mode   
     * @return string|boolean string if successfully found a valid file name, else false
     */
    private function getTemplateFile(IRenderable $object, $mode = null)
    {
        $tmpStorage = $this->templateFile;
        $template = $this->_getTemplatePath($object);
        foreach ($this->manageableExt->keys() as $ext) {
            $this->templateFile = $template . (null !== $mode ? '.' . $mode : '') . $ext;
            if (true === $this->isValidTemplateFile($this->templateFile)) {
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
     * Use the right adapter depending on $filename extension to define if
     * $filename is a valid template filename or not
     * 
     * @param  string  $filename 
     * @param  boolean $isLayout if you want to check $filename in layout dir, default: false
     * @return boolean           
     */
    private function isValidTemplateFile($filename, $isLayout = false)
    {
        $adapter = $this->determineWhichAdapterToUse($filename);
        if (null === $adapter) {
            return false;
        }

        return $adapter->isValidTemplateFile(
                        $filename, true === $isLayout ? $this->_layoutdir : $this->_scriptdir
        );
    }

    /**
     * @param  boolean $isPartial 
     * @param  boolean $isLayout  
     * @return string
     */
    private function renderTemplate($isPartial = false, $isLayout = false)
    {
        $adapter = $this->determineWhichAdapterToUse($this->templateFile);
        $dirs = true === $isLayout ? $this->_layoutdir : $this->_scriptdir;

        if (null === $adapter) {
            throw new RendererException(sprintf(
                    'Unable to manage file \'%s\' in path (%s)', $this->templateFile, implode(', ', $dirs)
            ), RendererException::SCRIPTFILE_ERROR);
        }

        $this->getApplication()->debug(sprintf('Rendering file `%s`.', $this->templateFile));
        if (false === $isPartial) {
            $this->_triggerEvent();
        }

        return $adapter->renderTemplate($this->templateFile, $dirs, $this->getParam(), $this->getAssignedVars());
    }

    /**
     * Returns an array of template files according the provided pattern
     * 
     * @param string $pattern
     * @return array
     */
    public function getTemplatesByPattern($pattern)
    {
        $templates = array();
        foreach ($this->manageableExt->keys() as $ext) {
            $templates = array_merge($templates, parent::getTemplatesByPattern($pattern . $ext));
        }

        return $templates;
    }

    /**
     * Returns the list of available render mode for the provided object
     * 
     * @param \BackBuilder\Renderer\IRenderable $object
     * @return array
     */
    public function getAvailableRenderMode(IRenderable $object)
    {
        $modes = parent::getAvailableRenderMode($object);
        foreach ($modes as &$mode) {
            $mode = str_replace($this->manageableExt->keys(), '', $mode);
        }

        return array_unique($modes);
    }

    /**
     * @see BackBuilder\Renderer\IRenderer::updateLayout()
     */
    public function updateLayout(Layout $layout)
    {
        $layoutFile = parent::updateLayout($layout);
        $adapter = $this->determineWhichAdapterToUse($layoutFile);
        if (null === $adapter) {
            throw new RendererException(sprintf(
                            'Unable to manage file \'%s\' in path (%s)', $layoutFile, $this->_layoutdir[0]
                    ), RendererException::SCRIPTFILE_ERROR);
        }

        return $adapter->updateLayout($layout, $layoutFile);
    }

    /**
     * Return the file path to current layout, try to create it if not exists
     * 
     * @param Layout $layout
     * @return string the file path
     * @throws RendererException
     */
    protected function _getLayoutFile(Layout $layout)
    {
        $layoutfile = $layout->getPath();
        if (null === $layoutfile && 0 < $this->manageableExt->count()) {
            $adapter = null;
            if (null !== $this->defaultAdapter && null !== $adapter = $this->rendererAdapters->get($this->defaultAdapter)) {
                $extensions = $adapter->getManagedFileExtensions();
            } else {
                $extensions = $this->manageableExt->keys();
            }

            if (0 === count($extensions)) {
                throw new RendererException(
                        'Declared adapter(s) (count:' . $this->rendererAdapters->count() . ') is/are not able to manage ' .
                        'any file extensions at moment.'
                );
            }

            $layoutfile = String::toPath($layout->getLabel(), array('extension' => reset($extensions)));
            $layout->setPath($layoutfile);
        }

        return $layoutfile;
    }

}
