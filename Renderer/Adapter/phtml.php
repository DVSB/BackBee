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

use Exception;

use Symfony\Component\HttpFoundation\ParameterBag;

use BackBuilder\Renderer\ARenderer,
    BackBuilder\Renderer\ARendererAdapter,
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
 *              e.chau <eric.chau@lp-digital.fr>
 */
class phtml extends ARendererAdapter
{
    /**
     * Extensions to include searching file
     * @var array
     */
    protected $includeExtensions = array('.phtml', '.php');

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $vars;

    /**
     * [__construct description]
     * @param ARenderer $renderer [description]
     */
    public function __construct(ARenderer $renderer)
    {
        parent::__construct($renderer);

        $this->params = array();
        $this->vars = array();
    }

    /**
     * Magic method to get an assign var
     * @param string $var the name of the variable
     * @return mixed the value
     */
    public function __get($var)
    {
        return isset($this->vars[$var]) ? $this->vars[$var] : null;
    }

    /**
     * Magic method to test the setting of an assign var
     * @codeCoverageIgnore
     * @param string $var the name of the variable
     * @return boolean
     */
    public function __isset($var)
    {
        return isset($this->vars[$var]);
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
        $this->vars[$var] = $value;

        return $this;
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

        File::resolveFilepath($filename, null, array('include_path' => $templateDir));

        return is_readable($filename);
    }

    public function renderTemplate($filename, array $templateDir, array $params = array(), array $vars = array())
    {
        foreach ($params as $key => $v) {
            $this->setParam($key, $v);
        }

        foreach ($vars as $k => $v) {
            $this->assign($k, $v);
        }

        try {
            File::resolveFilepath($filename, null, array('include_path' => $templateDir));
            ob_start();
            include $filename;

            return ob_get_clean();
        } catch (\BackBuilder\FrontController\Exception\FrontControllerException $fe) {
            ob_end_clean();
            throw $fe;
        } catch (Exception $e) {
            ob_end_clean();

            throw new RendererException(
                $e->getMessage() . ' in ' . $filename, RendererException::RENDERING_ERROR, $e
            );

        }
    }

    /**
     * Assign one or more variables
     * @param mixed $var A variable name or an array of variables to set
     * @param mixed $value The variable value to set
     * @return ARenderer The current renderer
     */
    public function assign($var, $value = null)
    {
        if (true === is_string($var)) {
            $this->vars[$var] = $value;
            return $this;
        }

        if (true === is_array($var)) {
            foreach ($var as $key => $value) {
                $this->vars[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Return the current page to be rendered
     * @codeCoverageIgnore
     * @return null|BackBuilder\NestedNode\Page
     */
    public function setParam($param, $value = null)
    {
        if (true === is_string($param)) {
            $this->params[$param] = $value;

            return $this;
        }

        if (true === is_array($param)) {
            foreach ($param as $key => $value) {
                $this->params[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Return parameters
     * @param string $param The parameter to return
     * @return mixed The parameter value asked or array of the parameters
     */
    public function getParam($param = null)
    {
        if (null === $param) {
            return $this->params;
        }

        return isset($this->params[$param]) ? $this->params[$param] : null;
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::updateLayout()
     */
    public function updateLayout(Layout $layout, $layoutFile)
    {
        if (false === $layoutFile) {
            return false;
        }

        $mainLayoutRow = $layout->getDomDocument();
        if (false === $layout->isValid() || null === $mainLayoutRow) {
            throw new RendererException('Malformed data for the layout layout.');
        }

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
        //$domlayout->loadHTMLFile($layoutFile);
        $layoutcontent = str_replace(array('<?php', '?>'), array('&lt;?php', '?&gt;'), file_get_contents($layoutFile));
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

        if (!file_put_contents($layoutFile, preg_replace_callback('/(&lt;|<)\?php(.+)\?(&gt;|>)/iu', create_function('$matches', 'return "<?php".html_entity_decode(urldecode($matches[2]))."?".">";'), $domlayout->saveHTML())))
            throw new RendererException(sprintf('Unable to save layout %s.', $layoutFile), RendererException::LAYOUT_ERROR);

        libxml_clear_errors();

        return $layoutFile;
    }

    /**
     * @see BackBuilder\Renderer\IRendererAdapter::onRestorePreviousRenderer()
     */
    public function onRestorePreviousRenderer(ARenderer $renderer)
    {
        parent::onRestorePreviousRenderer($renderer);

        $this->vars = $renderer->getAssignedVars();
        $this->params = $renderer->getParam();
    }
}
