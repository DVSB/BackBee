<?php

namespace BackBuilder\Renderer\Helper;

use BackBuilder\ClassContent\ContentSet;

/**
 * Helper providing HTML attributes to online-edited content
 * 
 * If a valid BB5 user is rendering the content and parameter bb5.editable not 
 * set to false following attributes would be sets :
 *   * data-uid:            The unique identifier of the content
 *   * data-parent:         The unique identifier of the content owner
 *   * data-draftuid:       The unique identifier of the revision
 *   * data-type:           The classname of the content (unprefix from BackBuilder\ClassContent)
 *   * data-minentry:       The minimum subcontents allowed
 *   * data-maxentry:       The maximum subcontents allowed
 *   * data-accept:         The accepted subcontents
 *   * data-rteconfig       The rte config to be used
 *   * data-element:        The name or index of this content
 *   * data-isloaded:       Is the content is contained by the entity manager
 *   * data-forbidenactions:The editing forbiden actions
 *   * data-contentplugins: The edting plugins to load
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Renderer\Helper
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class datacontent extends AHelper
{

    /**
     * The content to be rendered
     * @var \BackBuilder\ClassContent\AClassContent
     */
    private $_content;

    /**
     * An array of attributes
     * @var array 
     */
    private $_attributes;

    /**
     * An array of attributes
     * @var array 
     */
    private $_basic_attributes;

    /**
     * The BB5 content class markups
     * @var array
     */
    private $_classmarkup;

    /**
     * Returns the HTML formated attributes for content
     * @param array $datacontent Optional attributes to add
     * @param array $params Optional parameters
     * @return string The HTML formated attributes for content
     */
    public function __invoke($datacontent = array(), $params = array())
    {
        $this->_content = $this->_renderer->getObject();
        $this->_attributes = $this->_toRegularBag($datacontent);
        $this->_addValueToAttribute('class', $this->_renderer->getParam('class'));

        // Store initial basic attributes to content
        $this->_basic_attributes = $this->_attributes;

        // If a valid BB user is granted to access this content
        if (null !== $this->_content
                && true === $this->_isGranted()
                && false !== $this->_renderer->getParam('bb5.editable')) {
            $this->_addCommonContentMarkup($params)
                    ->_addContentSetMarkup($params)
                    ->_addClassContainerMarkup($params)
                    ->_addElementFileMarkup($params)
                    ->_addAlohaMarkup($params);
            //->_addRteMarkup($params);
        }

        return implode(' ', array_map(array($this, '_formatAttributes'), array_keys($this->_attributes), array_values($this->_attributes)));
    }

    /**
     * Adds common BB5 content markups to contents
     * @param array $params Optional parameters
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addCommonContentMarkup($params = array())
    {
        $categories = $this->_content->getProperty('category');
        if (true === is_array($categories)
                && 0 < count($categories)) {
            $this->_addValueToAttribute('class', $this->_getClassMarkup('contentclass'));
        }

        $this->_addValueToAttribute('data-uid', $this->_content->getUid())
                ->_addValueToAttribute('data-type', $this->_getDataType())
                ->_addValueToAttribute('data-parent', $this->_renderer->getParentUid())
                ->_addValueToAttribute('data-element', $this->_renderer->getCurrentElement())
                ->_addValueToAttribute('data-isloaded', $this->_content->isLoaded() ? 'true' : 'false')
                ->_addValueToAttribute('data-rendermode', (null !== $this->_content->getMode()) ? $this->_content->getMode() : (string) $this->_renderer->getMode())
                ->_addValueToAttribute('data-forbidenactions', implode(',', (array) $this->_content->getProperty('forbiden-actions')))
                ->_addBoundariesEntries();

        if (null !== $draft = $this->_content->getDraft()) {
            $this->_addValueToAttribute('data-draftuid', $draft->getUid());
        }

        return $this;
    }

    /**
     * Adds BB5 content markups to contentsets
     * @param array $params Optional parameters
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addContentSetMarkup($params = array())
    {
        if ($this->_content instanceof ContentSet) {
            $this->_addValueToAttribute('class', 'bb5-droppable-item')
                    ->_addValueToAttribute('data-contentplugins', 'contentsetEdit');

            // itemcontainer is used when items in a contentset are not directly appended to the contentset 
            $itemcontainer = $this->_content->getParam('itemcontainer');
            if (true === is_array($itemcontainer)) {
                $param = array_pop($itemcontainer);
                $this->_addValueToAttribute('data-itemcontainer', (string) $param);

                // if the contentset has an itemcontainer, it MUST not be droppable (its itemcontainer SHOULD BE)
                if (false === empty($param)) {
                    $this->_addValueToAttribute('class', $this->_getClassMarkup('droppableclass'));
                }

                if (true === array_key_exists("useItemcontainer", $params)) {
                    $this->_attributes = $this->_basic_attributes;
                    $this->_addValueToAttribute('class', $this->_getClassMarkup('droppableclass'))
                            ->_addValueToAttribute('data-refparent', $this->_content->getUid());
                }
            }

            if (null !== $this->_content->getAccept()) {
                $this->_addValueToAttribute('data-accept', implode(',', array_map(array($this, '_unprefixClassname'), (array) $this->_content->getAccept())));
            }
        }

        return $this;
    }

    /**
     * Adds draggable and resizable class
     * @todo Add test on autobloc (new forbiddenaction ?)
     * @todo Add test on resizable (new forbiddenaction ?)
     * @param array $params
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addClassContainerMarkup($params = array())
    {
        if (null !== $class_container = $this->_renderer->getClassContainer()) {
            if ($class_container instanceof ContentSet) {
                if (false === ($this->_content instanceof \BackBuilder\ClassContent\Bloc\autobloc)) {
                    $this->_addValueToAttribute('class', $this->_getClassMarkup('draggableclass'));
                }

                // @todo Add test on resizable (new forbiddenaction ?)
                //$this->_addValueToAttribute('class', $this->_getClassMarkup('resizableclass'));
            }
        }

        return $this;
    }

    /**
     * Adds specific markup to Element\file
     * @param array $params Optional parameters
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addElementFileMarkup($params = array())
    {
        if ($this->_content instanceof \BackBuilder\ClassContent\Element\file) {
            $em = $this->_renderer->getApplication()->getEntityManager();
            $this->_addValueToAttribute('data-library', $em->getRepository('BackBuilder\ClassContent\Element\file')->isInMediaLibrary($this->_content));
        }

        return $this;
    }

    /**
     * Adds specific Aloha markup on content
     * @param array $params Optional parameters
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addAlohaMarkup($params = array())
    {
        $this->_addValueToAttribute('class', 'contentAloha');

        if (false === ($this->_content instanceof ContentSet)
                && null !== $this->_renderer->getCurrentElement()) {
            $this->_addValueToAttribute('data-aloha', $this->_renderer->getCurrentElement());
        }
        return $this;
    }

    /**
     * adds specific rte markup on content
     * @params aray $params Optional parameters
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addRteMarkup($params = array())
    {
        return $this;
    }

    /**
     * Adds the boundary entries if exist
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addBoundariesEntries()
    {
        $min_entry = $this->_content->getMinEntry();
        if (true === is_array($min_entry) && true === array_key_exists('value', $min_entry)) {
            $this->_addValueToAttribute('data-minentry', $min_entry['value']);
        } else {
            $this->_addValueToAttribute('data-minentry', '');
        }

        $max_entry = $this->_content->getMaxEntry();
        if (true === is_array($max_entry) && true === array_key_exists('value', $max_entry)) {
            $this->_addValueToAttribute('data-maxentry', $max_entry['value']);
        } else {
            $this->_addValueToAttribute('data-maxentry', '');
        }

        return $this;
    }

    /**
     * Returns the BB5 class markup from config file
     * @param string $markup The markup asked
     * @return string|NULL
     */
    private function _getClassMarkup($markup)
    {
        if (null === $this->_classmarkup) {
            $content_markup = $this->_renderer->getApplication()->getConfig()->getContentmarkupConfig();

            $this->_classmarkup = array();
            if (true === is_array($content_markup)) {
                $this->_classmarkup = $content_markup;
            }
        }

        return (true === array_key_exists($markup, $this->_classmarkup)) ? $this->_classmarkup[$markup] : null;
    }

    /**
     * Adds new values to an attribute, creates it if don't exist
     * @param string $key
     * @param mixed $value
     * @return \BackBuilder\Renderer\Helper\datacontent
     */
    private function _addValueToAttribute($key, $value = NULL)
    {
        if (null !== $value) {
            $values = (array) $value;

            if (false === array_key_exists($key, $this->_attributes)) {
                $this->_attributes[$key] = array();
            }
            $this->_attributes[$key] = array_unique(array_merge($this->_attributes[$key], $values));
        }

        return $this;
    }

    /**
     * Splits the values of the provided data by boundary spaces
     * @param array $data
     * @return array
     */
    private function _toRegularBag($data = array())
    {
        foreach ($data as $key => $value) {
            if (false === is_array($value)) {
                $data[$key] = array_unique(explode(' ', $value));
            }
        }

        return $data;
    }

    /**
     * Format an $key/$value association to HTML compliant attribute string
     * @param string $key
     * @param mixed $value
     * @return string
     * @codeCoverageIgnore
     */
    private function _formatAttributes($key, $value)
    {
        if (true === is_array($value)) {
            $value = implode(' ', $value);
        }

        return $key . '="' . $value . '"';
    }

    /**
     * Checks if a valid BB user is granted to access the provided content
     * @return Boolean TRUE if granted, FALSE otherwise
     */
    private function _isGranted()
    {
        $securityContext = $this->_renderer->getApplication()->getSecurityContext();

        try {
            return (true === $securityContext->isGranted('sudo')
                    || null === $securityContext->getACLProvider()
                    || true === $securityContext->isGranted('VIEW', $this->_content));
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }

    /**
     * Gets the data type (short class name) of the content
     * @return string
     * @codeCoverageIgnore
     */
    private function _getDataType()
    {
        $classname = \Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($this->_content);
        return $this->_unprefixClassname($classname);
    }

    /**
     * Unprefixes a BackBuilder content classname
     * @param string $classname
     * @return string
     */
    private function _unprefixClassname($classname)
    {
        return str_replace('BackBuilder\ClassContent\\', '', $classname);
    }

}
