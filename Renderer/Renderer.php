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

namespace BackBee\Renderer;

use BackBee\BBApplication;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBee\NestedNode\Page;
use BackBee\Renderer\Exception\RendererException;
use BackBee\Routing\RouteCollection;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Utils\File\File;
use BackBee\Utils\String;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Renderer engine class; able to manage multiple template engine.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Renderer extends AbstractRenderer implements DumpableServiceInterface, DumpableServiceProxyInterface
{
    /**
     * constants used to manage external resources.
     */
    const CSS_LINK = 'css';
    const HEADER_JS = 'js_header';
    const FOOTER_JS = 'js_footer';

    /**
     * Contains every RendererAdapterInterface added by user
     * @var ParameterBag
     */
    private $renderer_adapters;

    /**
     * Contains every extensions that Renderer can manage thanks to registered RendererAdapterInterface
     * @var ParameterBag
     */
    private $manageable_ext;

    /**
     * key of the default adapter to use when there is a conflict.
     *
     * @var string
     */
    private $default_adapter;

    /**
     * The file path to the template.
     *
     * @var string
     */
    private $template_file;

    /**
     * define if renderer has been restored by container or not.
     *
     * @var boolean
     */
    private $is_restored;

    /**
     * contains every external resources of current page (js and css).
     *
     * @var ParameterBag
     */
    private $external_resources;

    /**
     * Constructor.
     *
     * @param BBApplication $application
     * @param array|null    $config
     * @param boolean       $autoloadRendererApdater
     */
    public function __construct(BBApplication $application = null, $config = null, $autoloadRendererApdater = true)
    {
        parent::__construct($application, $config);
        $this->renderer_adapters = new ParameterBag();
        $this->manageable_ext = new ParameterBag();
        $this->external_resources = new ParameterBag();

        if (null !== $application && true === $autoloadRendererApdater) {
            $rendererConfig = $this->getApplication()->getConfig()->getRendererConfig();
            $adapters = (array) $rendererConfig['adapter'];
            foreach ($adapters as $adapter) {
                $this->addRendererAdapter(new $adapter($this));
            }
        }

        $this->is_restored = false;
    }

    /**
     * Update every helpers and every registered renderer adapters with the right AbstractRenderer;
     * this method is called everytime we clone a renderer
     */
    public function updatesAfterClone()
    {
        $this->updateHelpers();
        foreach ($this->renderer_adapters->all() as $ra) {
            $ra->onNewRenderer($this);
        }

        return $this;
    }

    /**
     * Register a renderer adapter ($rendererAdapter); this method also set
     * current $rendererAdapter as default adapter if it is not set.
     *
     * @param RendererAdapterInterface $rendererAdapter
     */
    public function addRendererAdapter(RendererAdapterInterface $rendererAdapter)
    {
        $key = $this->getRendererAdapterKey($rendererAdapter);
        if (!$this->renderer_adapters->has($key)) {
            $this->renderer_adapters->set($key, $rendererAdapter);
            $this->addManagedExtensions($rendererAdapter);
        }

        if (null === $this->default_adapter) {
            $this->default_adapter = $key;
        }
    }

    /**
     * @param string $ext
     *
     * @return RendererAdapterInterface
     */
    public function getAdapterByExt($ext)
    {
        if (null === $adapter = $this->determineWhichAdapterToUse('.'.$ext)) {
            throw new RendererException("Unable to find adapter for '.$ext'.", RendererException::SCRIPTFILE_ERROR);
        }

        return $adapter;
    }

    /**
     * Set the adapter referenced by $adapterKey as defaultAdapter to use in conflict
     * case; the default adapter is also considered by self::getRightAdapter().
     *
     * @param string $adapterKey
     *
     * @return boolean
     */
    public function defaultAdapter($adapterKey)
    {
        $exists = false;
        if (in_array($adapterKey, $this->renderer_adapters->keys())) {
            $this->default_adapter = $adapterKey;
            $exists = true;
        }

        return $exists;
    }

    /**
     * Return template file extension of the default adapter.
     *
     * @return String
     */
    public function getDefaultAdapterExt()
    {
        $managedExt = $this->renderer_adapters->get($this->default_adapter)->getManagedFileExtensions();

        return array_shift($managedExt);
    }

    /**
     * Getters of renderer adapter by $key.
     *
     * @return BackBee\Renderer\RendererAdapterInterface
     */
    public function getAdapter($key)
    {
        return $this->renderer_adapters->get($key);
    }

    /**
     * Getters of renderer adapters.
     *
     * @return array<BackBee\Renderer\RendererAdapterInterface>
     */
    public function getAdapters()
    {
        return $this->renderer_adapters->all();
    }

    /**
     * @see BackBee\Renderer\RendererInterface::render()
     */
    public function render(RenderableInterface $obj = null, $mode = null, $params = null, $template = null, $ignoreModeIfNotSet = false)
    {
        if (null === $obj) {
            return;
        }

        $application = $this->getApplication();
        if (!$obj->isRenderable() && null === $application->getBBUserToken()) {
            return;
        }

        $application->debug(sprintf(
            'Starting to render `%s(%s)` with mode `%s` (ignore if not available: %d).',
            get_class($obj),
            $obj->getUid(),
            $mode,
            $ignoreModeIfNotSet
        ));

        $parent = $this->getObject();

        $renderer = clone $this;

        $renderer->updatesAfterClone();

        $this->setRenderParams($renderer, $params);

        $renderer
            ->setObject($obj)
            ->setMode($mode, $ignoreModeIfNotSet)
            ->triggerEvent('prerender')
        ;

        if (null === $renderer->__render) {
            // Rendering a page with layout
            if ($obj instanceof Page) {
                $renderer->setCurrentPage($obj);
                $renderer->__render = $renderer->renderPage($template, $params);
                $renderer->insertExternalResources();
                $application->debug('Rendering Page OK');
            } else {
                // Rendering a content
                $renderer->__render = $renderer->renderContent($params, $template);
            }

            $renderer->triggerEvent('postrender', null, $renderer->__render);
        }

        $render = $renderer->__render;
        unset($renderer);

        $this->updatesAfterUnset();

        return $render;
    }

    public function tryResolveParentObject(AbstractClassContent $parent, AbstractClassContent $element)
    {
        foreach ($parent->getData() as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }

            foreach ($values as $value) {
                if ($value instanceof AbstractClassContent) {
                    if (!$value->isLoaded()) {
                        // try to load subcontent
                        if (null !== $subcontent = $this->getApplication()
                                ->getEntityManager()
                                ->getRepository(\Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($value))
                                ->load($value, $this->getRenderer()->getApplication()->getBBUserToken())) {
                            $value = $subcontent;
                        }
                    }

                    if ($element->equals($value)) {
                        $this->__currentelement = $key;
                        $this->__object = $parent;
                        $this->_parentuid = $parent->getUid();
                    } else {
                        $this->tryResolveParentObject($value, $element);
                    }
                }

                if (null !== $this->__currentelement) {
                    break;
                }
            }

            if (null !== $this->__currentelement) {
                break;
            }
        }
    }

    /**
     * @see BackBee\Renderer\RendererInterface::partial()
     */
    public function partial($template = null, $params = null)
    {
        $this->template_file = $template;
        File::resolveFilepath($this->template_file, null, array('include_path' => $this->_scriptdir));
        if (!is_file($this->template_file) || !is_readable($this->template_file)) {
            throw new RendererException(sprintf(
                'Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)
            ), RendererException::SCRIPTFILE_ERROR);
        }

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
     * @see BackBee\Renderer\RendererInterface::error()
     */
    public function error($errorCode, $title = null, $message = null, $trace = null)
    {
        $found = false;
        foreach ($this->manageable_ext->keys() as $ext) {
            $this->template_file = 'error'.DIRECTORY_SEPARATOR.$errorCode.$ext;
            if (true === $this->isValidTemplateFile($this->template_file, true)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            foreach ($this->manageable_ext->keys() as $ext) {
                $this->template_file = 'error'.DIRECTORY_SEPARATOR.'default'.$ext;
                if (true === $this->isValidTemplateFile($this->template_file)) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            return false;
        }

        $this->assign('error_code', $errorCode);
        $this->assign('error_title', $title);
        $this->assign('error_message', $message);
        $this->assign('error_trace', $trace);

        return $this->renderTemplate(false, true);
    }

    /**
     * Check if $filename exists.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function isTemplateFileExists($filename)
    {
        return $this->isValidTemplateFile($filename);
    }

    /**
     * Returns image url.
     *
     * @param string            $pathinfo
     * @param BackBee\Site\Site $site
     *
     * @return string image url
     */
    public function getImageUrl($pathinfo, Site $site = null)
    {
        return $this->getUri($pathinfo, null, $site, RouteCollection::IMAGE_URL);
    }

    /**
     * Returns image url.
     *
     * @param string            $pathinfo
     * @param BackBee\Site\Site $site
     *
     * @return string image url
     */
    public function getMediaUrl($pathinfo, Site $site = null)
    {
        return $this->getUri($pathinfo, null, $site, RouteCollection::MEDIA_URL);
    }

    /**
     * Returns resource url.
     *
     * @param string            $pathinfo
     * @param BackBee\Site\Site $site
     *
     * @return string resource url
     */
    public function getResourceUrl($pathinfo, Site $site = null)
    {
        return $this->getUri($pathinfo, null, $site, RouteCollection::RESOURCE_URL);
    }

    /**
     * Compute route which matched with routeName and replace every token by its values specified in routeParams;
     * You can also give base url (by default current site base url will be used).
     *
     * @param string      $routeName
     * @param array|null  $routeParams
     * @param string|null $baseUrl
     * @param boolean     $addExt
     * @param  \BackBee\Site\Site
     *
     * @return string
     */
    public function generateUrlByRouteName($routeName, array $routeParams = null, $baseUrl = null, $addExt = true, Site $site = null, $buildQuery = false)
    {
        return $this->application->getRouting()->getUrlByRouteName($routeName, $routeParams, $baseUrl, $addExt, $site, $buildQuery);
    }

    /**
     * Returns an array of template files according the provided pattern.
     *
     * @param string $pattern
     *
     * @return array
     */
    public function getTemplatesByPattern($pattern)
    {
        $templates = array();
        foreach ($this->manageable_ext->keys() as $ext) {
            $templates = array_merge($templates, parent::getTemplatesByPattern($pattern.$ext));
        }

        return $templates;
    }

    /**
     * Returns the list of available render mode for the provided object.
     *
     * @param  \BackBee\Renderer\RenderableInterface $object
     * @return array
     */
    public function getAvailableRenderMode(RenderableInterface $object)
    {
        $modes = parent::getAvailableRenderMode($object);
        foreach ($modes as &$mode) {
            $mode = str_replace($this->manageable_ext->keys(), '', $mode);
        }

        return array_unique($modes);
    }

    /**
     * @see BackBee\Renderer\RendererInterface::updateLayout()
     */
    public function updateLayout(Layout $layout)
    {
        $layoutFile = parent::updateLayout($layout);
        $adapter = $this->determineWhichAdapterToUse($layoutFile);

        if (!is_array($this->_layoutdir) || 0 === count($this->_layoutdir)) {
            throw new RendererException('None layout directory defined', RendererException::SCRIPTFILE_ERROR);
        }

        if (null === $adapter) {
            throw new RendererException(sprintf(
                'Unable to manage file \'%s\' in path (%s)', $layoutFile, $this->_layoutdir[0]
            ), RendererException::SCRIPTFILE_ERROR);
        }

        return $adapter->updateLayout($layout, $layoutFile);
    }

    /**
     * Adds provided href as stylesheet to add to current page head tag
     * Note: provided href will be added only if it does not already exist in stylesheet list.
     *
     * @param string $href
     */
    public function addStylesheet($href)
    {
        $this->addExternalResources(self::CSS_LINK, $href);
    }

    /**
     * Adds provided href as javascript script to add to current page head tag
     * Note: provided href will be added only if it does not already exist in javascript script list.
     *
     * @param string $href
     */
    public function addHeaderJs($href)
    {
        $this->addExternalResources(self::HEADER_JS, $href);
    }

    /**
     * Adds provided href as javascript script to add to current page footer
     * Note: provided href will be added only if it does not already exist in javascript script list.
     *
     * @param string $href
     */
    public function addFooterJs($href)
    {
        $this->addExternalResources(self::FOOTER_JS, $href);
    }

    /**
     * Alias to self::addHeaderJs().
     *
     * @deprecated since version 0.12
     */
    public function addHeaderScript($src)
    {
        $this->addHeaderJs($src);
    }

    /**
     * Alias to self::addFooterJs().
     *
     * @deprecated since version 0.12
     */
    public function addFooterScript($src)
    {
        $this->addFooterJs($src);
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required.
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return;
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method.
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = array())
    {
        return array(
            'template_directories' => $this->_scriptdir,
            'layout_directories'   => $this->_layoutdir,
        );
    }

    /**
     * Restore current service to the dump's state.
     *
     * @param array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                    restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->_scriptdir = $dump['template_directories'];
        $this->_layoutdir = $dump['layout_directories'];

        $this->is_restored = true;
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->is_restored;
    }

    /**
     * Return the file path to current layout, try to create it if not exists.
     *
     * @param Layout $layout
     *
     * @return string the file path
     *
     * @throws RendererException
     */
    protected function getLayoutFile(Layout $layout)
    {
        $layoutfile = $layout->getPath();
        if (null === $layoutfile && 0 < $this->manageable_ext->count()) {
            $adapter = null;
            if (null !== $this->default_adapter && null !== $adapter = $this->renderer_adapters->get($this->default_adapter)) {
                $extensions = $adapter->getManagedFileExtensions();
            } else {
                $extensions = $this->manageable_ext->keys();
            }

            if (0 === count($extensions)) {
                throw new RendererException(
                        'Declared adapter(s) (count:'.$this->renderer_adapters->count().') is/are not able to manage '.
                        'any file extensions at moment.'
                );
            }

            $layoutfile = String::toPath($layout->getLabel(), array('extension' => reset($extensions)));
            $layout->setPath($layoutfile);
        }

        return $layoutfile;
    }

    /**
     * Update every helpers and every registered renderer adapters with the right AbstractRenderer;
     * this method is called everytime we unset a renderer
     */
    protected function updatesAfterUnset()
    {
        $this->updateHelpers();
        foreach ($this->renderer_adapters->all() as $ra) {
            $ra->onRestorePreviousRenderer($this);
        }

        return $this;
    }

    /**
     * Generic method to add an external resource (css, javascript in page header or footer).
     *
     * @param string $type
     * @param string $href
     */
    private function addExternalResources($type, $href)
    {
        $resources = array();
        if ($this->external_resources->has($type)) {
            $resources = $this->external_resources->get($type);
        }

        if (!in_array($href, $resources)) {
            $resources[] = $href;
        }

        $this->external_resources->set($type, $resources);
    }

    /**
     * Insert every external resources: css and header js will be added before page '</head>' and
     * footer javascript will be added before page '</body>'.
     *
     * @return self
     */
    private function insertExternalResources()
    {
        $header_render = '';
        foreach ($this->external_resources->get(self::CSS_LINK, array()) as $href) {
            $header_render .= $this->generateStylesheetTag($href);
        }

        foreach ($header_js = $this->external_resources->get(self::HEADER_JS, array()) as $src) {
            $header_render .= $this->generateJavascriptTag($src);
        }

        if (!empty($header_render)) {
            $this->setRender(str_replace('</head>', "$header_render</head>", $this->getRender()));
        }

        $footer_render = '';
        $footer_js = array_diff($this->external_resources->get(self::FOOTER_JS, array()), $header_js);
        foreach ($footer_js as $src) {
            $footer_render .= $this->generateJavascriptTag($src);
        }

        if (!empty($footer_render)) {
            $this->setRender(str_replace('</body>', "$footer_render</body>", $this->getRender()));
        }

        $this->external_resources->remove(self::CSS_LINK);
        $this->external_resources->remove(self::HEADER_JS);
        $this->external_resources->remove(self::FOOTER_JS);

        return $this;
    }

    /**
     * Generates HTML5 link tag with provided href.
     *
     * @param string $href
     *
     * @return string
     */
    private function generateStylesheetTag($href)
    {
        return '<link rel="stylesheet" href="'.$href.'" type="text/css">';
    }

    /**
     * Generates HTML5 script tag with provided src.
     *
     * @param string $src
     *
     * @return string
     */
    private function generateJavascriptTag($src)
    {
        return '<script src="'.$src.'"></script>';
    }

    /**
     * Compute a key for renderer adapter ($rendererAdapter).
     *
     * @param  RendererAdapterInterface $rendererAdapter
     * @return string
     */
    private function getRendererAdapterKey(RendererAdapterInterface $rendererAdapter)
    {
        $key = explode(NAMESPACE_SEPARATOR, get_class($rendererAdapter));

        return strtolower($key[count($key) - 1]);
    }

    /**
     * Extract managed extensions from rendererAdapter and store it.
     *
     * @param RendererAdapterInterface $rendererAdapter
     */
    private function addManagedExtensions(RendererAdapterInterface $rendererAdapter)
    {
        $key = $this->getRendererAdapterKey($rendererAdapter);
        foreach ($rendererAdapter->getManagedFileExtensions() as $ext) {
            $rendererAdapters = array($key);
            if ($this->manageable_ext->has($ext)) {
                $rendererAdapters = $this->manageable_ext->get($ext);
                $rendererAdapters[] = $key;
            }

            $this->manageable_ext->set($ext, $rendererAdapters);
        }
    }

    /**
     * Returns an adapter containing in $adapeters; it will returns in prior
     * the defaultAdpater if it is in $adapters or the first adapter found.
     *
     * @param array $adapters contains object of type IRendererAdapter
     *
     * @return RendererAdapterInterface
     */
    private function getRightAdapter(array $adapters)
    {
        $adapter = null;
        if (1 < count($adapters) && in_array($this->default_adapter, $adapters)) {
            $adapter = $this->default_adapter;
        } else {
            $adapter = reset($adapters);
        }

        return $adapter;
    }

    /**
     * Returns the right adapter to use according to the filename extension.
     *
     * @return RendererAdapterInterface
     */
    private function determineWhichAdapterToUse($filename = null)
    {
        if (null === $filename || !is_string($filename)) {
            return;
        }

        $pieces = explode('.', $filename);
        if (1 > count($pieces)) {
            return;
        }

        $ext = '.'.$pieces[count($pieces) - 1];
        $adaptersForExt = $this->manageable_ext->get($ext);
        if (!is_array($adaptersForExt) || 0 === count($adaptersForExt)) {
            return;
        }

        $adapter = $this->getRightAdapter($adaptersForExt);

        return $this->renderer_adapters->get($adapter);
    }

    /**
     * Render a page object.
     *
     * @param string $layoutfile A force layout script to be rendered
     *
     * @return string The rendered output
     *
     * @throws RendererException
     */
    private function renderPage($layoutFile = null, $params = null)
    {
        $this->setNode($this->getObject());

        $application = $this->getApplication();
        // Rendering subcontent
        if (null !== $contentSet = $this->getObject()->getContentSet()) {
            $bbUserToken = $application->getBBUserToken();
            $revisionRepo = $application->getEntityManager()->getRepository('BackBee\ClassContent\Revision');
            if (null !== $bbUserToken && null !== $revision = $revisionRepo->getDraft($contentSet, $bbUserToken)) {
                $contentSet->setDraft($revision);
            }

            $layout = $this->getObject()->getLayout();
            $zones = $layout->getZones();
            $zoneIndex = 0;

            foreach ($contentSet->getData() as $content) {
                if (array_key_exists($zoneIndex, $zones)) {
                    $zone = $zones[$zoneIndex];
                    $isMain = null !== $zone && property_exists($zone, 'mainZone') && true === $zone->mainZone;
                    $this->container()->add($this->render($content, $this->getMode(), array(
                        'class' => 'rootContentSet',
                        'isRoot' => true,
                        'indexZone' => $zoneIndex++,
                        'isMainZone' => $isMain,
                    ), null, $this->_ignoreIfRenderModeNotAvailable));
                }
            }
        }

        // Check for a valid layout file
        $this->template_file = $layoutFile;
        if (null === $this->template_file) {
            $this->template_file = $this->getLayoutFile($this->getCurrentPage()->getLayout());
        }

        if (!$this->isValidTemplateFile($this->template_file, true)) {
            throw new RendererException(
                sprintf('Unable to read layout %s.', $this->template_file), RendererException::LAYOUT_ERROR
            );
        }

        $application->info(sprintf('Rendering page `%s`.', $this->getObject()->getNormalizeUri()));

        return $this->renderTemplate(false, true);
    }

    /**
     * Render a ClassContent object.
     *
     * @param array  $params   A Force set of parameters to render the object
     * @param string $template A force template script to be rendered
     *
     * @return string The rendered output
     *
     * @throws RendererException
     */
    private function renderContent($params = null, $template = null)
    {
        try {
            $mode = null !== $this->getMode() ? $this->getMode() : $this->_object->getMode();
            $this->template_file = $template;
            if (null === $this->template_file && null !== $this->_object) {
                $this->template_file = $this->getTemplateFile($this->_object, $mode);
                if (false === $this->template_file) {
                    $this->template_file = $this->getTemplateFile($this->_object, $this->getMode());
                }

                if (false === $this->template_file && false === $this->_ignoreIfRenderModeNotAvailable) {
                    $this->template_file = $this->getTemplateFile($this->_object);
                }
            }

            if (false === $this->isValidTemplateFile($this->template_file)) {
                throw new RendererException(sprintf(
                        'Unable to find file \'%s\' in path (%s)', $template, implode(', ', $this->_scriptdir)
                ), RendererException::SCRIPTFILE_ERROR);
            }
        } catch (RendererException $e) {
            $render = '';

            // Unknown template, try to render subcontent
            if (null !== $this->_object && is_array($this->_object->getData())) {
                foreach ($this->_object->getData() as $subcontents) {
                    $subcontents = (array) $subcontents;

                    foreach ($subcontents as $sc) {
                        if ($sc instanceof RenderableInterface) {
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

        $application = $this->getApplication();
        // Assign vars and parameters
        if (null !== $this->_object) {
            $draft = $this->_object->getDraft();
            $aClassContentClassname = 'BackBee\ClassContent\AbstractClassContent';
            if ($this->_object instanceof $aClassContentClassname && !$this->_object->isLoaded()) {
                // trying to refresh unloaded content
                $em = $application->getEntityManager();

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
            $this->setParam($this->_object->getAllParams());
        }

        if (null !== $application) {
            $application->debug(sprintf(
                'Rendering content `%s(%s)`.',
                get_class($this->_object),
                $this->_object->getUid()
            ));
        }

        return $this->renderTemplate();
    }

    /**
     * Set parameters to a renderer object in parameter.
     *
     * @param AbstractRenderer $render
     * @param array     $params
     */
    private function setRenderParams(AbstractRenderer $render, $params)
    {
        if (null !== $params) {
            $params = (array) $params;
            foreach ($params as $param => $value) {
                $render->setParam($param, $value);
            }
        }
    }

    /**
     * Try to compute and guess a valid filename for $object:
     * 		- on success return string which is the right filename with its extension
     * 		- on fail return false.
     *
     * @param  RenderableInterface    $object
     * @param  string         $mode
     * @return string|boolean string if successfully found a valid file name, else false
     */
    private function getTemplateFile(RenderableInterface $object, $mode = null)
    {
        $tmpStorage = $this->template_file;
        $template = $this->getTemplatePath($object);
        foreach ($this->manageable_ext->keys() as $ext) {
            $this->template_file = $template.(null !== $mode ? '.'.$mode : '').$ext;
            if ($this->isValidTemplateFile($this->template_file)) {
                $filename = $this->template_file;
                $this->template_file = $tmpStorage;

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
     * $filename is a valid template filename or not.
     *
     * @param string  $filename
     * @param boolean $isLayout if you want to check $filename in layout dir, default: false
     *
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
     * @param boolean $isPartial
     * @param boolean $isLayout
     *
     * @return string
     */
    private function renderTemplate($isPartial = false, $isLayout = false)
    {
        $adapter = $this->determineWhichAdapterToUse($this->template_file);
        $dirs = true === $isLayout ? $this->_layoutdir : $this->_scriptdir;

        if (null === $adapter) {
            throw new RendererException(sprintf(
                'Unable to manage file \'%s\' in path (%s)', $this->template_file, implode(', ', $dirs)
            ), RendererException::SCRIPTFILE_ERROR);
        }

        $this->getApplication()->debug(sprintf('Rendering file `%s`.', $this->template_file));
        if (false === $isPartial) {
            $this->triggerEvent();
        }

        return $adapter->renderTemplate(
            $this->template_file,
            $dirs,
            array_merge($this->getParam(), $this->getDefaultParams()),
            $this->getAssignedVars()
        );
    }

    /**
     * Returns default parameters that are availables in every templates.
     *
     * @return array
     */
    private function getDefaultParams()
    {
        return [
            'app'     => $this->getApplication(),
            'bbtoken' => $this->getApplication()->getBBUserToken(),
            'request' => $this->getApplication()->getContainer()->get('request'),
            'routing' => $this->getApplication()->getContainer()->get('routing'),
        ];
    }
}
