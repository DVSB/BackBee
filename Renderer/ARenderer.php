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

namespace BackBuilder\Renderer;

use BackBuilder\BBApplication,
    BackBuilder\NestedNode\ANestedNode,
    BackBuilder\Renderer\Exception\RendererException,
    BackBuilder\Site\Layout,
    BackBuilder\Util\File,
    BackBuilder\Util\String;

/**
 * Abstract class for a renderer
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
abstract class ARenderer implements IRenderer
{

    /**
     * Current BackBuilder application
     * @var BackBuilder\BBApplication
     */
    protected $_application;

    /**
     * The loaded helpers
     * @var array
     */
    protected $_helpers = array();

    /**
     * The current object to be render
     * @var Irenderable
     */
    protected $_object;

    /**
     * The current page to be render
     * @var BackBuilder\NestedNode\Page
     */
    protected $_currentpage;

    /** The rendering mode
     * @var string
     */
    protected $_mode;

    /**
     * Ignore the rendering if specified render mode is not
     * available if TRUE, use the default template otherwise
     * @var Boolean
     */
    protected $_ignoreIfRenderModeNotAvailable;
    protected $_node;

    /**
     * The content parameters
     * @var array
     */
    protected $_params = array();
    protected $_parentuid;

    /**
     * The file path to look for templates
     * @var array
     */
    protected $_scriptdir = array();

    /**
     * The file path to look for layouts
     * @var array
     */
    protected $_layoutdir = array();

    /**
     * Extensions to include searching file
     * @var array
     */
    protected $_includeExtensions = array();

    /**
     * The assigned variables
     * @var array
     */
    protected $_vars = array();
    private $__currentelement;
    private $__object = null;
    private $__vars = array();
    private $__params = array();
    private $__overloaded = 0;
    protected $__render;

    public function __call($method, $argv)
    {
        if (!array_key_exists($method, $this->_helpers)) {
            $classhelper = '\BackBuilder\Renderer\Helper\\' . $method;
            if (class_exists($classhelper)) {
                $this->_helpers[$method] = new $classhelper($this, $argv);
            }
        }

        if (is_callable($this->_helpers[$method])) {
            return call_user_func_array($this->_helpers[$method], $argv);
        }

        return $this->_helpers[$method];
    }

    public function __($string)
    {
        return $this->translate($string);
    }

    public function __clone()
    {
        $this->_cache()
                ->reset();

        foreach ($this->_helpers as $method => $helper) {
            $this->_helpers[$method] = clone $this->_helpers[$method];
            $this->_helpers[$method]->setRenderer($this);
        }
    }

    public function overload($new_dir)
    {
        array_unshift($this->_scriptdir, $new_dir);
        $this->__overloaded = $this->__overloaded + 1;
    }

    /**
     * Add new helpre directory in the choosen position.
     *
     * @codeCoverageIgnore
     * @param string $new_dir location of the new directory
     * @param integer $position position in the array
     */
    public function addHelperDir($new_dir, $position = 0)
    {
        $this->getApplication()->getAutoloader()->registerNamespace('BackBuilder\Renderer\Helper', $new_dir);
        return $this;
        //$this->insertInArrayOnPostion($this->_helpers, $new_dir, $position);
    }

    /**
     * Add new layout directory in the choosen position.
     *
     * @codeCoverageIgnore
     * @param string $new_dir location of the new directory
     * @param integer $position position in the array
     */
    public function addLayoutDir($new_dir, $position = 0)
    {
        $this->insertInArrayOnPostion($this->_layoutdir, $new_dir, $position);
        return $this;
    }

    /**
     * Add new script directory in the choosen position.
     *
     * @codeCoverageIgnore
     * @param strimg $new_dir location of the new directory
     * @param integer $position position in the array
     */
    public function addScriptDir($new_dir, $position = 0)
    {
        $this->insertInArrayOnPostion($this->_scriptdir, $new_dir, $position);
        return $this;
    }

    /**
     * Add new entry in the choosen position.
     *
     * @param array $array Arry to modify
     * @param string $new_value location of the new directory
     * @param integer $position position in the array
     */
    protected function insertInArrayOnPostion(array &$array, $new_value, $position)
    {
        if (in_array($new_value, $array)) {
            foreach ($array as $key => $value) {
                if ($value == $new_value) {
                    unset($array[$key]);
                    for ($i = ($key + 1); $i < count($array); $i++) {
                        $array[$i - 1] = $array[$i];
                    }
                    break;
                }
            }
        }
        if ($position <= 0) {
            $position = 0;
            array_unshift($array, $new_value);
        } elseif ($position >= count($array)) {
            $array[count($array) - 1] = $new_value;
        } else {
            for ($i = (count($array) - 1); $i >= $position; $i--) {
                $array[$i + 1] = $array[$i];
            }
            $array[$position] = $new_value;
        }
        ksort($array);
    }

    public function release()
    {
        for ($i = 0; $i < $this->__overloaded; $i++) {
            unset($this->_scriptdir[$i]);
        }
        $this->_scriptdir = array_values($this->_scriptdir);
        $this->__overloaded = 0;
    }

    /**
     * Class constructor
     * @param BBAplication $application The current BBapplication
     * @param array $config Optional configurations overriding
     */
    public function __construct(BBApplication $application = null, $config = null)
    {
        $repdir = '';

        if (null !== $application) {
            $this->_application = $application;
            $repdir = $this->_application->getRepository();

            $rendererConfig = $this->_application->getConfig()->getRendererConfig();
            if (is_array($rendererConfig) && isset($rendererConfig['path'])) {
                $config = (null === $config) ? $rendererConfig['path'] : array_merge_recursive($config, $rendererConfig['path']);
            }
        }

        if (is_array($config)) {
            foreach ($config as $dir => $path) {
                $dir = '_' . strtolower($dir);
                if (false !== strpos($dir, 'dir') && property_exists($this, $dir)) {
                    $path = (array) $path;
                    array_walk($path, array('\BackBuilder\Util\File', 'resolveFilepath'), array('base_dir' => $repdir));
                    $this->$dir = $path;
                }
            }
        }

        if (null !== $this->_application) {
            $bb5script = $this->_application->getBaseDir() . '/BackBuilder/Resources' . DIRECTORY_SEPARATOR . 'scripts';
            File::resolveFilepath($bb5script);
            $this->_scriptdir[] = $bb5script;
        }
    }

    /**
     * Magic method to get an assign var
     * @param string $var the name of the variable
     * @return mixed the value
     */
    public function __get($var)
    {
        return isset($this->_vars[$var]) ? $this->_vars[$var] : null;
    }

    /**
     * Magic method to test the setting of an assign var
     * @codeCoverageIgnore
     * @param string $var the name of the variable
     * @return boolean
     */
    public function __isset($var)
    {
        return isset($this->_vars[$var]);
    }

    /**
     * Magic method to assign a var
     * @codeCoverageIgnore
     * @param string $var the name of the variable
     * @param mixed $value the value of the variable
     * @return ARenderer the current renderer
     */
    public function __set($var, $value = null)
    {
        $this->_vars[$var] = $value;
        return $this;
    }

    /**
     * Magic method to unset an assign var
     * @param string $var the name of the variable
     */
    public function __unset($var)
    {
        if (isset($this->_vars[$var])) {
            unset($this->_vars[$var]);
        }
    }

    /**
     * Return the file path to current layout, try to create it if not exists
     * @param Layout $layout
     * @return string the file path
     * @throws RendererException
     */
    protected function _getLayoutFile(Layout $layout)
    {
        $layoutfile = $layout->getPath();
        if (null === $layoutfile && 0 < count($this->_includeExtensions)) {
            $ext = reset($this->_includeExtensions);
            $layoutfile = String::toPath($layout->getLabel(), array('extension' => $ext));
            $layout->setPath($layoutfile);
        }

        return $layoutfile;
    }

    protected function _triggerEvent($name = 'render', $object = null, $render = null)
    {
        if (null === $this->_application)
            return;

        $dispatcher = $this->_application->getEventDispatcher();
        if (null != $dispatcher) {
            $dispatcher->triggerEvent($name, null != $object ? $object : $this->getObject(), null === $render ? $this : array($this, $render));
        }
    }

    protected function _cache()
    {
        if (null !== $this->_object) {
            $this->_parentuid = $this->_object->getUid();
            $this->__object = $this->_application->getEntityManager()->find(get_class($this->_object), $this->_object->getUid());
        }

        $this->__vars[] = $this->_vars;
        $this->__params[] = $this->_params;

        return $this;
    }

    protected function _restore()
    {
        $this->_object = $this->__object;
        $this->_vars = array_pop($this->__vars);
        $this->_params = array_pop($this->__params);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return \BackBuilder\Renderer\ARenderer
     */
    private function _resetVars()
    {
        $this->_vars = array();
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return \BackBuilder\Renderer\ARenderer
     */
    private function _resetParams()
    {
        $this->_params = array();
        return $this;
    }

    /**
     * Assign one or more variables
     * @param mixed $var A variable name or an array of variables to set
     * @param mixed $value The variable value to set
     * @return ARenderer The current renderer
     */
    public function assign($var, $value = null)
    {
        if (is_string($var)) {
            $this->_vars[$var] = $value;
            return $this;
        }

        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $this->_vars[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return \BackBuilder\BBApplication
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Return the assigned variables
     * @codeCoverageIgnore
     * @return array Array of assigned variables
     */
    public function getAssignedVars()
    {
        return $this->_vars;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getClassContainer()
    {
        return $this->__object;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCurrentElement()
    {
        return $this->__currentelement;
    }

    public function getUri($pathinfo = null)
    {
        if (null !== $pathinfo && preg_match('/^http[s]?:\/\//', $pathinfo)) {
            return $pathinfo;
        }

        if ('/' !== substr($pathinfo, 0, 1)) {
            $pathinfo = '/' . $pathinfo;
        }

        if ($this->_application->isStarted() && null !== $this->_application->getRequest()) {
            $request = $this->_application->getRequest();

            if (null === $pathinfo) {
                $pathinfo = $request->getBaseUrl();
            }

            if (basename($request->getBaseUrl()) == basename($request->server->get('SCRIPT_NAME'))) {
                return $request->getSchemeAndHttpHost() . substr($request->getBaseUrl(), 0, -1 * (1 + strlen(basename($request->getBaseUrl())))) . $pathinfo;
            } else {
                return $request->getUriForPath($pathinfo);
            }
        }

        return $pathinfo;
    }

    public function getRelativeUrl($uri)
    {
        $url = $uri;

        if ($this->_application->isStarted() && null !== $this->_application->getRequest()) {
            $request = $this->_application->getRequest();
            $baseurl = str_replace('\\', '/', $request->getSchemeAndHttpHost() . dirname($request->getBaseUrl()));
            $url = str_replace($baseurl, '', $uri);

            if (false !== $ext = strrpos($url, '.')) {
                $url = substr($url, 0, $ext);
            }

            if ('/' != substr($url, 0, 1)) {
                $url = '/' . $url;
            }
        }

        return $url;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getMaxEntry()
    {
        return $this->_maxentry;
    }

    /**
     * Return the current rendering mode
     * @codeCoverageIgnore
     * @return string
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getNode()
    {
        return $this->_node;
    }

    /**
     * Return the object to be rendered
     * @codeCoverageIgnore
     * @return IRenderable
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getParentUid()
    {
        return $this->_parentuid;
    }

    /**
     * Return the previous object to be rendered
     * @codeCoverageIgnore
     * @return IRenderable or null
     */
    public function getPreviousObject()
    {
        return $this->__object;
    }

    /**
     * Return the current page to be rendered
     * @codeCoverageIgnore
     * @return null|BackBuilder\NestedNode\Page
     */
    public function getCurrentPage()
    {
        return $this->_currentpage;
    }

    /**
     * Return the current root of the page to be rendered
     * @return null|BackBuilder\NestedNode\Page
     */
    public function getCurrentRoot()
    {
        if (null !== $this->getCurrentPage()) {
            return $this->getCurrentPage()->getRoot();
        } else if (null === $this->getCurrentSite()) {
            return null;
        } else {
            return $this->_application->getEntityManager()
                            ->getRepository('BackBuilder\NestedNode\Page')
                            ->getRoot($this->getCurrentSite());
        }
    }

    /**
     * return the current rendered site
     * @codeCoverageIgnore
     * @return null|BackBuilder\Site\Site
     */
    public function getCurrentSite()
    {
        return $this->_application->getSite();
    }

    /**
     * Return parameters
     * @param string $param The parameter to return
     * @return mixed The parameter value asked or array of the parameters
     */
    public function getParam($param = null)
    {
        if (null === $param)
            return $this->_params;

        return isset($this->_params[$param]) ? $this->_params[$param] : null;
    }

    /**
     * Processes a view script and returns the output.
     *
     * @access public
     * @param IRenderable $content The object to be rendered
     * @param string $mode The rendering mode
     * @param array $params  A force set of parameters
     * @param string $template A force template script to be rendered
     * @param Boolean $ignoreIfRenderModeNotAvailable Ignore the rendering if specified render mode is not
     *                                                available if TRUE, use the default template otherwise
     * @return string The view script output
     */
    public function render(IRenderable $content = null, $mode = null, $params = null, $template = null, $ignoreIfRenderModeNotAvailable = true)
    {
        // Nothing to do
    }

    public function partial($template = null, $params = null)
    {
        // Nothing to do
    }

    /**
     * Render an error layout according to code
     *
     * @param int $error_code  Error code
     * @param string $title    Optional error title
     * @param string $message  Optional error message
     * @param string $trace    Optional error trace
     * @return boolean|string false if none layout found or the rendered layout
     */
    public function error($error_code, $title = null, $message = null, $trace = null)
    {
        return false;
    }

    public function reset()
    {
        $this->_resetVars()
             ->_resetParams();

        $this->__render = null;

        return $this;
    }

    /**
     * Set the rendering mode
     * @codeCoverageIgnore
     * @param string $mode
     * @param Boolean $ignoreIfRenderModeNotAvailable Ignore the rendering if specified render mode is not
     *                                                available if TRUE, use the default template otherwise
     * @return ARenderer The current renderer
     */
    public function setMode($mode = null, $ignoreIfRenderModeNotAvailable = true)
    {
        $this->_mode = $mode;
        $this->_ignoreIfRenderModeNotAvailable = $ignoreIfRenderModeNotAvailable;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\NestedNode\ANestedNode $node
     * @return \BackBuilder\Renderer\ARenderer
     */
    public function setNode(ANestedNode $node)
    {
        $this->_node = $node;
        return $this;
    }

    /**
     * Set the object to render
     * @param IRenderable $object
     * @return ARenderer The current renderer
     */
    public function setObject(IRenderable $object = null)
    {
        $this->_object = $object;

        if (is_array($this->__vars)) {
            foreach ($this->__vars[count($this->__vars) - 1] as $key => $var) {
                if ($var === $object) {
                    $this->__currentelement = $key;
                }
            }
        }

        return $this;
    }

    /**
     * Set the current page
     * @param BackBuilder\NestedNode\Page $page
     * @return ARenderer The current renderer
     */
    public function setCurrentPage(\BackBuilder\NestedNode\Page $page = null)
    {
        $this->_currentpage = $page;

        return $this;
    }

    /**
     * Set one or set of parameters
     * @param mixed $param A parameter name or an array of parameters to set
     * @param mixed $value The parameter value to set
     * @return ARenderer The current renderer
     */
    public function setParam($param, $value = null)
    {
        if (is_string($param)) {
            $this->_params[$param] = $value;
            return $this;
        }

        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->_params[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param type $render
     * @return \BackBuilder\Renderer\ARenderer
     */
    public function setRender($render)
    {
        $this->__render = $render;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRender()
    {
        return $this->__render;
    }

    /**
     * Updates a file script of a layout
     * @param Layout $layout The layout to update
     * @return string The filename of the updated script
     */
    public function updateLayout(Layout $layout)
    {
        if (null === $layout->getSite())
            return false;

        $layoutfile = $this->_getLayoutFile($layout);
        File::resolveFilepath($layoutfile, null, array('base_dir' => $this->_layoutdir[0]));

        if (false === file_exists($layoutfile) && false === touch($layoutfile)) {
            throw new RendererException(sprintf('Unable to create file %s.', $layoutfile), RendererException::LAYOUT_ERROR);
        }

        if (!is_writable($layoutfile)) {
            throw new RendererException(sprintf('Unable to open file %s in writing mode.', $layoutfile), RendererException::LAYOUT_ERROR);
        }

        return $layoutfile;
    }

    /**
     * Unlink a file script of a layout
     * @param Layout $layout The layout to update
     */
    public function removeLayout(Layout $layout)
    {
        if (null === $layout->getSite())
            return false;

        $layoutfile = $this->_getLayoutFile($layout);
        @unlink($layoutfile);
    }

    /**
     * Return the relative path from the classname of an object
     * @param \BackBuilder\Renderer\IRenderable $object
     * @return string
     */
    protected function _getTemplatePath(IRenderable $object)
    {
        return str_replace(array('BackBuilder' . NAMESPACE_SEPARATOR . 'ClassContent' . NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array('', DIRECTORY_SEPARATOR), get_class($object));
    }

    /**
     * Returns an array of template files according the provided pattern
     * @param string $pattern
     * @return array
     */
    public function getTemplatesByPattern($pattern)
    {
        File::resolveFilepath($pattern);

        $templates = array();
        foreach ($this->_scriptdir as $dir) {
            if (true === is_array(glob($dir . DIRECTORY_SEPARATOR . $pattern)))
                $templates = array_merge($templates, glob($dir . DIRECTORY_SEPARATOR . $pattern));
        }

        return $templates;
    }

    /**
     * Return the current token
     * @codeCoverageIgnore
     * @return \Symfony\Component\Security\Core\Authentication\Token\AbstractToken
     */
    public function getToken()
    {
        return $this->getApplication()->getSecurityContext()->getToken();
    }

    /**
     * Returns the list of available render mode for the provided object
     * @param \BackBuilder\Renderer\IRenderable $object
     * @return array
     */
    public function getAvailableRenderMode(IRenderable $object)
    {
        $templatePath = $this->_getTemplatePath($object);
        $templates = $this->getTemplatesByPattern($templatePath . '.*');
        foreach ($templates as &$template) {
            $template = basename(str_replace($templatePath . '.', '', $template));
        }
        unset($template);

        return $templates;
    }

    /**
     * Returns the ignore state of rendering if render mode is not available
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function getIgnoreIfNotAvailable()
    {
        return $this->_ignoreIfRenderModeNotAvailable;
    }

}