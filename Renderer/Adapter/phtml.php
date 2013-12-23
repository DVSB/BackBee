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

use Symfony\Component\HttpFoundation\ParameterBag;
use BackBuilder\BBApplication,
    BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\IRenderable,
    BackBuilder\Renderer\Exception\RendererException,
    BackBuilder\Site\Layout,
    BackBuilder\Util\File;

/**
 * Rendering adapter for phtml templating files
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @subpackage  Adapter
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class phtml extends ARenderer
{

    const HEADER_SCRIPT = 'header';
    const FOOTER_SCRIPT = 'footer';

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
     * Array that contains every declared js script (header and footer)
     * @var array
     */
    private $_scripts;

    public function __construct(BBApplication $application = null, $config = null)
    {
        parent::__construct($application, $config);

        $this->_scripts = new ParameterBag();
    }

    /**
     * Try to locate the corresponding template file for the current object
     * @access private
     * @param IRenderable $object The object to render
     * @param string $mode The rendering mode
     * @return string The file path to the template file
     */
    private function _getTemplateFile(IRenderable $object, $mode = null)
    {
        $template = $this->_getTemplatePath($object);

        foreach ($this->_includeExtensions as $ext) {
            $filename = $template . ($mode ? '.' . $mode : '') . $ext;
            File::resolveFilepath($filename, null, array('include_path' => $this->_scriptdir));
            if (is_file($filename) && is_readable($filename)) {
                return $filename;
            }
        }

        if ($parentClassname = get_parent_class($object)) {
            $parent = new \ReflectionClass($parentClassname);
            if (!$parent->isAbstract()) {
                return $this->_getTemplateFile(new $parentClassname(), $mode, null);
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
    private function _renderContent($params = null, $template = null)
    {
        try {
            $mode = (null !== $this->getMode()) ? $this->getMode() : $this->_object->getMode();
            $this->_templateFile = $template;
            if (null === $this->_templateFile && null !== $this->_object) {
                $this->_templateFile = $this->_getTemplateFile($this->_object, $mode);
                if (false === $this->_templateFile) {
                    $this->_templateFile = $this->_getTemplateFile($this->_object, $this->getMode());
                }
                if (false === $this->_templateFile && false === $this->_ignoreIfRenderModeNotAvailable) {
                    $this->_templateFile = $this->_getTemplateFile($this->_object);
                }
            }

            File::resolveFilepath($this->_templateFile, null, array('include_path' => $this->_scriptdir));

            // Unfound template file for this object
            if (!is_file($this->_templateFile) || !is_readable($this->_templateFile)) {
                throw new RendererException(sprintf('Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)), RendererException::SCRIPTFILE_ERROR);
            }
        } catch (RendererException $e) {
            $render = '';

            // Unknown template, try to render subcontent
            if (null !== $this->_object && is_array($this->_object->getData())) {
                foreach ($this->_object->getData() as $subcontents) {
                    $subcontents = is_array($subcontents) ? $subcontents : array($subcontents);

                    foreach ($subcontents as $subcontent) {
                        if (is_a($subcontent, 'BackBuilder\Renderer\IRenderable')) {
                            $renderer = clone $this;
                            if (false === $subcontentrender = $renderer->render($subcontent, $this->getMode(), $params, $template, $this->_ignoreIfRenderModeNotAvailable)) {
                                throw $e;
                            }

                            $this->_restore();

                            $render .= $subcontentrender;
                        }
                    }
                }
            }

            return $render;
        }

        // Assign vars and parameters
        if (null !== $this->_object) {
            $draft = $this->_object->getDraft();
            if (is_a($this->_object, 'BackBuilder\ClassContent\AClassContent') && !$this->_object->isLoaded()) {
                // trying to refresh unloaded content
                $em = $this->getApplication()->getEntityManager();

                $classname = get_class($this->_object);
                $uid = $this->_object->getUid();

                $em->detach($this->_object);
                if (null !== $object = $em->find($classname, $uid)) {
                    $this->_object = $object;
                    if (null !== $draft)
                        $this->_object->setDraft($draft);
                }
            }

            $this->assign($this->_object->getData())
                    ->setParam($this->_object->getParam());
        }
        if (null !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value)
                $this->setParam($param, $value);
        }

        if (null !== $this->_application)
            $this->_application->debug(sprintf('Rendering content `%s(%s)`.', get_class($this->_object), $this->_object->getUid()));

        return $this->_renderTemplate();
    }

    /**
     * Render a page object
     * @param string $layoutfile A force layout script to be rendered
     * @return string The rendered output
     * @throws RendererException
     */
    private function _renderPage($layoutfile = null)
    {
        $this->setNode($this->getObject());

        // Rendering subcontent
        if (null !== $contentset = $this->getObject()->getContentSet()) {
            if (null !== $this->getApplication()->getBBUserToken() && null !== $revision = $this->getApplication()->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft($contentset, $this->getApplication()->getBBUserToken())) {
                $contentset->setDraft($revision);
            }

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
                        'isMainZone' => null !== $zone && property_exists($zone, 'mainZone') && true === $zone->mainZone
                    ), null, $this->_ignoreIfRenderModeNotAvailable));
                }
            }
        }

        // Check for a valid layout file
        $this->_templateFile = $layoutfile;
        if (null === $this->_templateFile) {
            $this->_templateFile = $this->_getLayoutFile($this->layout());
        }

        File::resolveFilepath($this->_templateFile, null, array('include_path' => $this->_layoutdir));

        if (false === is_readable($this->_templateFile)) {
            throw new RendererException(sprintf('Unable to read layout %s.', $layoutfile), RendererException::LAYOUT_ERROR);
        }

        if (null !== $this->_application) {
            $this->_application->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));
        }

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

        if (null !== $this->_application)
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
        $basedir = (null !== $this->_application) ? $this->_application->getRepository() : '';

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

        if (!is_file($filename) || false === $realfilename = realpath($filename))
            throw new RendererException(sprintf('Unable to find file \'%s\' in path (%s)', $filename, implode(', ', $this->_scriptdir)), RendererException::SCRIPTFILE_ERROR);

        return $realfilename;
    }

    /**
     * @see BackBuilder\Renderer\ARenderer::render()
     */
    public function render(IRenderable $object = null, $mode = null, $params = null, $template = null, $ignoreIfRenderModeNotAvailable = true)
    {
        if (null === $object)
            return;

        if (false === $object->isRenderable() && null === $this->getApplication()->getBBUserToken())
            return;

        $this->getApplication()->debug(sprintf('Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).', get_class($object), $object->getUid(), $mode, $ignoreIfRenderModeNotAvailable));

        $renderer = clone $this;
        $renderer->setObject($object)
                ->setMode($mode, $ignoreIfRenderModeNotAvailable)
                ->_triggerEvent('prerender');

        if (null === $renderer->__render) {
            // Rendering a page with layout
            if (is_a($object, '\BackBuilder\NestedNode\Page')) {
                $renderer->setCurrentPage($object);
                $renderer->__render = $renderer->_renderPage($template);
                $renderer->_insertHeaderAndFooterScript();
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

        if (false === strpos(basename($pathinfo), '.') && '/' != substr($pathinfo, -1)) {
            if (null === $this->_default_ext) {
                if (null !== $this->getApplication())
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
    public function partial($template = null, $params = null)
    {
        $this->_templateFile = $template;
        File::resolveFilepath($this->_templateFile, null, array('include_path' => $this->_scriptdir));

        // Assign parameters
        if (null !== $params) {
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
    public function error($error_code, $title = null, $message = null, $trace = null)
    {
        foreach ($this->_includeExtensions as $ext) {
            $this->_templateFile = 'error' . DIRECTORY_SEPARATOR . $error_code . $ext;
            File::resolveFilepath($this->_templateFile, null, array('include_path' => $this->_layoutdir));
            if (false !== file_exists($this->_templateFile) || false === is_readable($this->_templateFile))
                break;
        }
        if (false === file_exists($this->_templateFile) || false === is_readable($this->_templateFile)) {
            $this->_templateFile = 'error' . DIRECTORY_SEPARATOR . 'default.phtml';
            File::resolveFilepath($this->_templateFile, null, array('include_path' => $this->_layoutdir));
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
        if (false === ($layoutfile = parent::updateLayout($layout)))
            return false;

        $mainLayoutRow = $layout->getDomDocument();
        if (!$layout->isValid() || null === $mainLayoutRow)
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

        $layoutNode = $domlayout->importNode($mainLayoutRow->firstChild, true);
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

    /**
     * Helper: generate javascript's tag with $href and add it to head tag children
     * Note: guaranteed that two or more scripts with same href will be included only once
     * 
     * @param string $href href of the js file to add
     * @return BackBuilder\Renderer\Adapter\phtml
     */
    public function addHeaderScript($href)
    {
        $this->_addScript(self::HEADER_SCRIPT, $href);

        return $this;
    }

    /**
     * Helper: generate javascript's tag with $href and add it to body tag children
     * Note: if header and footer scripts contains same href string, the script will be
     * only add in the head tag
     * 
     * @param string $href 
     * @return BackBuilder\Renderer\Adapter\phtml
     */
    public function addFooterScript($href)
    {
        $this->_addScript(self::FOOTER_SCRIPT, $href);

        return $this;
    }

    /**
     * @param string $type 
     * @param string $href 
     */
    private function _addScript($type, $href)
    {
        $scripts = array();
        if ($this->_scripts->has($type)) {
            $scripts = $this->_scripts->get($type);
        }

        if (!in_array($href, $scripts)) {
            $scripts[] = $href;
        }

        $this->_scripts->set($type, $scripts);
    }

    private function _insertHeaderAndFooterScript()
    {
        if (null === $this->_scripts) {
            return;
        }

        $footerScripts = $this->_scripts->get(self::FOOTER_SCRIPT, array());
        $headerScripts = $this->_scripts->get(self::HEADER_SCRIPT, array());
        $footerScripts = array_diff($footerScripts, $headerScripts);
        if (0 < count($headerScripts)) {
            $this->setRender(strstr($this->getRender(), '</head>', true) . $this->_generateScriptCode($headerScripts) . strstr($this->getRender(), '</head>'));
        }

        if (0 < count($footerScripts)) {
            $this->setRender(strstr($this->getRender(), '</body>', true) . $this->_generateScriptCode($footerScripts) . strstr($this->getRender(), '</body>'));
        }

        // Reset scripts array
        $this->_scripts->remove(self::FOOTER_SCRIPT);
        $this->_scripts->remove(self::HEADER_SCRIPT);
    }

    /**
     * 
     * @param  array $scripts
     * @return string
     */
    private function _generateScriptCode($scripts)
    {
        $result = '';
        foreach ($scripts as $href) {
            $result .= '<script type="text/javascript" src="' . $href . '"></script>';
        }

        return $result;
    }
}
