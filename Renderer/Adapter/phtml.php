<?php

namespace BackBuilder\Renderer\Adapter;

use BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\IRenderable,
    BackBuilder\Renderer\Exception\RendererException,
    BackBuilder\Site\Layout,
    BackBuilder\Util\File;

/**
 * Rendering adapter for phtml templating files
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer\Adapter
 * @copyright   Lp system
 * @author      c.rouillon
 */
class phtml extends ARenderer
{

    /**
     * Default extension to use to construct URI
     * @var String
     */
    private $_default_ext;

    /**
     * Extensions to include searching file
     * @var array
     */
    protected $_includeExtensions = array('.phtml', '.php');

    /**
     * The file path to the phtml template
     * @var string
     */
    private $_templateFile;

    /**
     * Try to locate the corresponding template file for the current object
     * @access private
     * @param IRenderable $object The object to render
     * @param string $mode The rendering mode
     * @return string The file path to the template file
     */
    private function _getTemplateFile(IRenderable $object, $mode = NULL)
    {
        $template = $this->_getTemplatePath($object);

        foreach ($this->_includeExtensions as $ext) {
            $filename = $template . ($mode ? '.' . $mode : '') . $ext;
            File::resolveFilepath($filename, NULL, array('include_path' => $this->_scriptdir));
            if (is_file($filename) && is_readable($filename))
                return $filename;
        }

        if ($parentClassname = get_parent_class($object)) {
            $parent = new \ReflectionClass($parentClassname);
            if (!$parent->isAbstract()) {
                return $this->_getTemplateFile(new $parentClassname(), $mode, NULL);
            }
        }

        return false;
    }

    /**
     * Render a ClassContent object
     * @param array $params A Force set of parameters to render the object
     * @param string $template A force template script to be rendered
     * @return string The rendered output
     * @throws RendererException
     */
    private function _renderContent($params = NULL, $template = NULL)
    {
        try {
            $mode = (null !== $this->getMode()) ? $this->getMode() : $this->_object->getMode();
            $this->_templateFile = $template;
            if (NULL === $this->_templateFile && NULL !== $this->_object) {
                $this->_templateFile = $this->_getTemplateFile($this->_object, $mode);
                if (FALSE === $this->_templateFile) {
                    $this->_templateFile = $this->_getTemplateFile($this->_object, $this->getMode());
                }
                if (false === $this->_templateFile && false === $this->_ignoreIfRenderModeNotAvailable) {
                    $this->_templateFile = $this->_getTemplateFile($this->_object);
                }
            }
            File::resolveFilepath($this->_templateFile, NULL, array('include_path' => $this->_scriptdir));

            // Unfound template file for this object
            if (!is_file($this->_templateFile) || !is_readable($this->_templateFile))
                throw new RendererException(sprintf('Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)), RendererException::SCRIPTFILE_ERROR);
        } catch (RendererException $e) {
            $render = '';

            // Unknown template, try to render subcontent
            if (NULL !== $this->_object && is_array($this->_object->getData())) {
                foreach ($this->_object->getData() as $subcontents) {
                    $subcontents = is_array($subcontents) ? $subcontents : array($subcontents);

                    foreach ($subcontents as $subcontent) {
                        if (is_a($subcontent, 'BackBuilder\Renderer\IRenderable')) {
                            $renderer = clone $this;
                            if (FALSE === $subcontentrender = $renderer->render($subcontent, $this->getMode(), $params, $template, $this->_ignoreIfRenderModeNotAvailable))
                                throw $e;
                            $this->_restore();

                            $render .= $subcontentrender;
                        }
                    }
                }
            }

            return $render;
        }

        // Assign vars and parameters
        if (NULL !== $this->_object) {
            $draft = $this->_object->getDraft();
            if (is_a($this->_object, 'BackBuilder\ClassContent\AClassContent') && !$this->_object->isLoaded()) {
                // trying to refresh unloaded content
                $em = $this->getApplication()->getEntityManager();

                $classname = get_class($this->_object);
                $uid = $this->_object->getUid();

                $em->detach($this->_object);
                if (NULL !== $object = $em->find($classname, $uid)) {
                    $this->_object = $object;
                    if (NULL !== $draft)
                        $this->_object->setDraft($draft);
                }
            }

            $this->assign($this->_object->getData())
                    ->setParam($this->_object->getParam());
        }

        if (NULL !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value)
                $this->setParam($param, $value);
        }

        if (NULL !== $this->_application)
            $this->_application->debug(sprintf('Rendering content `%s(%s)`.', get_class($this->_object), $this->_object->getUid()));

        return $this->_renderTemplate();
    }

    /**
     * Render a page object
     * @param string $layoutfile A force layout script to be rendered
     * @return string The rendered output
     * @throws RendererException
     */
    private function _renderPage($layoutfile = NULL)
    {
        $this->setNode($this->getObject());

        // Rendering subcontent
        if (NULL !== $contentset = $this->getObject()->getContentSet()) {
            if (NULL !== $this->getApplication()->getBBUserToken() && NULL !== $revision = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft($contentset, $this->getApplication()->getBBUserToken()))
                $contentset->setDraft($revision);

            $layout = $this->getObject()->getLayout();
            $zones = $layout->getZones();

            $indexZone = 0;
            foreach ($contentset->getData() as $content) {
                if (array_key_exists($indexZone, $zones)) {
                    $zone = $zones[$indexZone];
                    $this->container()->add($this->render($content, $this->getMode(), array(
                                'class' => 'rootContentSet',
                                'isRoot' => true,
                                'indexZone' => $indexZone++,
                                'isMainZone' => NULL !== $zone && property_exists($zone, 'mainZone') && TRUE === $zone->mainZone
                                    ), null, $this->_ignoreIfRenderModeNotAvailable));
                }
            }
        }
        // Check for a valid layout file
        $this->_templateFile = $layoutfile;
        if (NULL === $this->_templateFile)
            $this->_templateFile = $this->_getLayoutFile($this->layout());

        File::resolveFilepath($this->_templateFile, NULL, array('include_path' => $this->_layoutdir));

        if (false === is_readable($this->_templateFile))
            throw new RendererException(sprintf('Unable to read layout %s.', $layoutfile), RendererException::LAYOUT_ERROR);

        if (NULL !== $this->_application)
            $this->_application->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));

        return $this->_renderTemplate();
    }

    /**
     * Render the template file
     * @return string The rendered output
     * @throws RendererException
     */
    private function _renderTemplate($isPartial = false)
    {
        if (!is_file($this->_templateFile) && !is_readable($this->_templateFile))
            throw new RendererException(sprintf('Unable to find file \'%s\' in path (%s)', $this->_templateFile, implode(', ', $this->_scriptdir)), RendererException::SCRIPTFILE_ERROR);

        if (NULL !== $this->_application)
            $this->_application->debug(sprintf('Rendering file `%s`.', $this->_templateFile));

        try {
            if (false === $isPartial) {
                $this->_triggerEvent();
            }

            unset($isPartial);

            ob_start();
            include $this->_templateFile;
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw new RendererException($e->getMessage() . ' in ' . $this->_templateFile, RendererException::RENDERING_ERROR, $e);
        }
    }

    /**
     * Try to find the real path to the provided file name
     * @param string $filename The file to looking for
     * @return string The real file path
     * @throws RendererException
     */
    private function _resolvefilePath($filename)
    {
        $basedir = (NULL !== $this->_application) ? $this->_application->getRepository() : '';

        if (!is_file($filename)) {
            foreach ($this->_scriptdir as $scriptdir) {
                if (!is_dir($scriptdir))
                    $scriptdir = $basedir . DIRECTORY_SEPARATOR . $scriptdir;

                if (is_file($scriptdir . DIRECTORY_SEPARATOR . $filename)) {
                    $template = $scriptdir . DIRECTORY_SEPARATOR . $filename;
                    break;
                }
            }
        }

        if (!is_file($filename) || FALSE === $realfilename = realpath($filename))
            throw new RendererException(sprintf('Unable to find file \'%s\' in path (%s)', $filename, implode(', ', $this->_scriptdir)), RendererException::SCRIPTFILE_ERROR);

        return $realfilename;
    }

    /**
     * @see BackBuilder\Renderer\ARenderer::render()
     */
    public function render(IRenderable $object = NULL, $mode = NULL, $params = NULL, $template = NULL, $ignoreIfRenderModeNotAvailable = true)
    {
        if (NULL === $object)
            return;

        if (false === $object->isRenderable() && NULL === $this->getApplication()->getBBUserToken())
            return;

        $this->getApplication()->debug(sprintf('Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).', get_class($object), $object->getUid(), $mode, $ignoreIfRenderModeNotAvailable));

        $renderer = clone $this;
        $renderer->setObject($object)
                ->setMode($mode, $ignoreIfRenderModeNotAvailable)
                ->_triggerEvent('prerender');

        if (NULL === $renderer->__render) {
            // Rendering a page with layout
            if (is_a($object, '\BackBuilder\NestedNode\Page')) {
                $renderer->setCurrentPage($object);
                $renderer->__render = $renderer->_renderPage($template);
                $this->getApplication()->debug(sprintf('Rendering Page OK'));
            } else {
                // Rendering a content
                $renderer->__render = $renderer->_renderContent($params, $template);
            }

            $renderer->_triggerEvent('postrender', null, $renderer->__render);
        }

        return $renderer->__render;
    }

    public function getUri($pathinfo = '/')
    {
        $pathinfo = parent::getUri($pathinfo);

        if (FALSE === strpos(basename($pathinfo), '.') && '/' != substr($pathinfo, -1)) {
            if (NULL === $this->_default_ext) {
                if (NULL !== $this->getApplication())
                    if ($this->getApplication()->getContainer()->has('site'))
                        $this->_default_ext = $this->getApplication()->getContainer()->get('site')->getDefaultExtension();
            }

            $pathinfo .= $this->_default_ext;
        }

        return $pathinfo;
    }

    /**
     * @see BackBuilder\Renderer.ARenderer::partial()
     */
    public function partial($template = NULL, $params = NULL)
    {
        $this->_templateFile = $template;
        File::resolveFilepath($this->_templateFile, NULL, array('include_path' => $this->_scriptdir));

        // Assign parameters
        if (NULL !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value)
                $this->setParam($param, $value);
        }

        // Remove variables from local scope
        unset($params, $template);

        return $this->_renderTemplate(true);
    }

    /**
     * @see BackBuilder\Renderer.ARenderer::erro()
     */
    public function error($error_code, $title = NULL, $message = NULL, $trace = NULL)
    {
        foreach ($this->_includeExtensions as $ext) {
            $this->_templateFile = 'error' . DIRECTORY_SEPARATOR . $error_code . $ext;
            File::resolveFilepath($this->_templateFile, NULL, array('include_path' => $this->_layoutdir));
            if (false !== file_exists($this->_templateFile) || false === is_readable($this->_templateFile))
                break;
        }
        if (false === file_exists($this->_templateFile) || false === is_readable($this->_templateFile)) {
            $this->_templateFile = 'error' . DIRECTORY_SEPARATOR . 'default.phtml';
            File::resolveFilepath($this->_templateFile, NULL, array('include_path' => $this->_layoutdir));
        }
        if (false === file_exists($this->_templateFile) || false === is_readable($this->_templateFile))
            return false;

        $this->assign('error_code', $error_code)
                ->assign('error_title', $title)
                ->assign('error_message', $message)
                ->assign('error_trace', $trace);

        // Remove variables from local scope
        unset($error_code, $title, $message, $trace);

        return $this->_renderTemplate();
    }

    /**
     * @see BackBuilder\Renderer\ARenderer::updateLayout()
     */
    public function updateLayout(Layout $layout)
    {
        if (FALSE === ($layoutfile = parent::updateLayout($layout)))
            return FALSE;

        $mainLayoutRow = $layout->getDomDocument();
        if (!$layout->isValid() || NULL === $mainLayoutRow)
            throw new RendererException('Malformed data for the layout layout.');

        // Add an php instruction to each final droppable zone found
        $xpath = new \DOMXPath($mainLayoutRow);
        $textNode = $mainLayoutRow->createTextNode('<?php echo $this->container()->first(); ?>');
        $nextNode = $mainLayoutRow->createTextNode('<?php echo $this->container()->next(); ?>');
        foreach ($xpath->query('//div[@class!="clear"]') as $node) {
            if (!$node->hasChildNodes()) {
                $node->appendChild(clone $textNode);
                $textNode = $nextNode;
            }
        }

        libxml_use_internal_errors(true);

        $domlayout = new \DOMDocument();
        //$domlayout->loadHTMLFile($layoutfile);
        $layoutcontent = str_replace(array('<?php', '?>'), array('&lt;?php', '?&gt;'), file_get_contents($layoutfile));
        @$domlayout->loadHTML($layoutcontent);
        $domlayout->formatOutput = true;

        $layoutNode = $domlayout->importNode($mainLayoutRow->firstChild, TRUE);
        $layoutid = $layoutNode->getAttribute('id');

        $xPath = new \DOMXPath($domlayout);
        if (($targetNodes = $xPath->query('//div[@id="' . $layoutid . '"]')) && 0 < $targetNodes->length) {
            foreach ($targetNodes as $targetNode) {
                $targetNode->parentNode->replaceChild($layoutNode, $targetNode);
            }
        } else if (($targetNodes = $domlayout->getElementsByTagName('body')) && 0 < $targetNodes->length) {
            foreach ($targetNodes as $targetNode) {
                $targetNode->appendChild($layoutNode);
            }
        } else {
            $domlayout->appendChild($layoutNode);
        }

        if (!file_put_contents($layoutfile, preg_replace_callback('/(&lt;|<)\?php(.+)\?(&gt;|>)/iu', create_function('$matches', 'return "<?php".html_entity_decode(urldecode($matches[2]))."?".">";'), $domlayout->saveHTML())))
            throw new RendererException(sprintf('Unable to save layout %s.', $layoutfile), RendererException::LAYOUT_ERROR);

        libxml_clear_errors();

        return $layoutfile;
    }

    /**
     * Returns an array of template files according the provided pattern
     * @param string $pattern
     * @return array
     */
    public function getTemplatesByPattern($pattern)
    {
        $templates = array();
        foreach ($this->_includeExtensions as $ext) {
            $templates = array_merge($templates, parent::getTemplatesByPattern($pattern . $ext));
        }

        return $templates;
    }

    /**
     * Returns the list of available render mode for the provided object
     * @param \BackBuilder\Renderer\IRenderable $object
     * @return array
     */
    public function getAvailableRenderMode(IRenderable $object)
    {
        $modes = parent::getAvailableRenderMode($object);
        foreach ($modes as &$mode) {
            $mode = str_replace($this->_includeExtensions, '', $mode);
        }
        unset($mode);

        return array_unique($modes);
    }

}