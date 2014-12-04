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

namespace BackBuilder\Services\Content;

use Symfony\Component\Yaml\Yaml as parserYaml;
use BackBuilder\ClassContent\AClassContent;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Content
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class ContentRender
{
    private $category;
    private $name;
    private $editables;
    private $bbapp;
    private $renderer;
    private $mode;
    private $uid;
    private $content;
    private $label;

    private function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        $dirs = glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (is_array($dirs) && count($dirs)) {
            foreach ($dirs as $dir) {
                $subdirs = $this->globRecursive($dir.'/'.basename($pattern), $flags);
            }
            if (true === is_array($subdirs)) {
                $files = array_merge($files, $subdirs);
            }
        }

        return $files;
    }

    private function initCategory()
    {
        $str = str_replace('\\', '/', $this->name);

        $file = $this->globRecursive($this->bbapp->getRepository().'/ClassContent/'.$str.'.yml', 0);
        if (is_array($file) && count($file)) {
            $dataYaml = parserYaml::parse($file[0]);
            foreach ($dataYaml as $name => $item) {
                if (isset($item['properties']['category'])) {
                    return $item['properties']['category'];
                }
            }
        }
    }

    public function initContentObject()
    {
        if (NULL === $this->content) {
            $classname = "BackBuilder\ClassContent\\".$this->name;
            if (NULL !== $this->uid) {
                $this->content = $this->bbapp->getEntityManager()->find($classname, $this->uid);
            }
//            if (NULL !== $content) {
//
//                if (NULL !== $draft = $this->bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $this->bbapp->getBBUserToken())) {
//                    $content->setDraft($draft);
//                    $this->content = $content;
//                }
//            }
            if (NULL === $this->content) {
                $this->content = new $classname();
            }

            if (null !== $this->bbapp->getBBUserToken()) {
                if (NULL !== $draft = $this->bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft($this->content, $this->bbapp->getBBUserToken())) {
                    $this->content->setDraft($draft);
                }
            }
        }
    }

    private function initFields()
    {
        $fields = array();
        $alohaConf = $this->bbapp->getConfig()->getSection('alohapluginstable');
        $content = $this->content;
        if (!is_a($content, 'BackBuilder\ClassContent\ContentSet')) {
            $elements = $content->getData();
            foreach ($elements as $key => $item) {
                if (is_a($content->$key, "BackBuilder\ClassContent\AClassContent")) {
                    if (is_object($content->$key) && ($content->{$key}->getParam('editable', 'boolean') == TRUE && NULL !== ($content->{$key}->getParam('aloha', 'scalar')))) {
                        $stdClassObj = new \stdClass();
                        $stdClassObj->{$key} = $alohaConf[$content->{$key}->getParam('aloha', 'scalar')];
                        $fields[] = $stdClassObj;
                    }
                }
            }
        }

        $this->editables = $fields;
    }

    public function __construct($name, $bbapp, $category = null, $mode = null, $uid = null)
    {
        $this->uid = (NULL === $uid) ? uniqid(rand()) : $uid;
        $this->name = $name;
        $this->renderer = $bbapp->getRenderer();
        $this->bbapp = $bbapp;
        $this->category = ($category === null) ? $this->initCategory() : $category;

        $this->editables = array();
        $this->mode = $mode;
        $this->content = null;

        $this->initContentObject();
        // Useless init because it's already done by BackBuilder\Services\Local\ClassContent::getContentsRteParams(); and it used the wrong config.yml's section
        // (alohapluginstable instead of rteconfig)
        //$this->initFields();
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getContentObject()
    {
        return $this->content;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @codeCoverageIgnore
     * @param  string                                      $label
     * @return \BackBuilder\Services\Content\ContentRender
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getFields()
    {
        return $this->editables;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getRender()
    {
        return $this->renderer->render($this->content, $this->mode);
    }

    public function __toStdObject($withRender = true)
    {
        $withRender = is_bool($withRender) ? $withRender : true; //send render by default
        $stdClass = new \stdClass();
        $stdClass->name = $this->getname();
        $stdClass->label = null === $this->content->getProperty('name') ? $this->getName() : $this->content->getProperty('name');
        $stdClass->description = $this->content->getProperty('description');
        $stdClass->category = $this->getCategory();
        $stdClass->editables = $this->getFields();
        $stdClass->uid = $this->content->getUid();
        $stdClass->render = ($withRender) ? $this->getRender() : "";
        $stdClass->sortable = array(
            'created' => 'Creation date',
            'modified' => 'Last modification date',
        );
        $stdClass->isVisible = (!is_null($this->content->getProperty("is-visible"))) ? (boolean) $this->content->getProperty("is-visible") : true;
        if (is_array($this->content->getProperty()) && array_key_exists('indexation', $this->content->getProperty())) {
            foreach ($this->content->getProperty('indexation') as $indexedElement) {
                $indexedElement = (array) $indexedElement;
                if ('@' !== substr($indexedElement[0], 0, 1)) {
                    $elements = explode('->', $indexedElement[0]);
                    $element = $elements[0];

                    $value = $this->content->$element;
                    if ($value instanceof AClassContent) {
                        $stdClass->sortable[$indexedElement[0]] = $value->getLabel();
                    } else {
                        $stdClass->sortable[$indexedElement[0]] = $indexedElement[0];
                    }
                }
            }
        }

        return $stdClass;
    }
}
