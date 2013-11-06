<?php

namespace BackBuilder\Services\Content;

use Symfony\Component\Yaml\Yaml as parserYaml;
use BackBuilder\ClassContent\AClassContent;

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
        $dirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (is_array($dirs) && count($dirs)) {
            foreach ($dirs as $dir)
                $subdirs = $this->globRecursive($dir . '/' . basename($pattern), $flags);
            if (true === is_array($subdirs))
                $files = array_merge($files, $subdirs);
        }
        return $files;
    }

    private function initCategory()
    {
        $str = str_replace('\\', '/', $this->name);

        $file = $this->globRecursive($this->bbapp->getRepository() . '/ClassContent/' . $str . '.yml', 0);
        if (is_array($file) && count($file)) {
            $dataYaml = parserYaml::parse($file[0]);
            foreach ($dataYaml as $name => $item) {
                if (isset($item['properties']['category']))
                    return $item['properties']['category'];
            }
        }
    }

    public function initContentObject()
    {
        if (NULL === $this->content) {
            $classname = "BackBuilder\ClassContent\\" . $this->name;
            if (NULL !== $this->uid)
                $this->content = $this->bbapp->getEntityManager()->find($classname, $this->uid);

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
            if (NULL !== $draft = $this->bbapp->getEntityManager()->getRepository('BackBuilder\ClassContent\Revision')->getDraft($this->content, $this->bbapp->getBBUserToken())) {
                $this->content->setDraft($draft);
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
        $this->uid = (NULL === $uid) ? uniqid() : $uid;
        $this->name = $name;
        $this->renderer = $bbapp->getRenderer();
        $this->bbapp = $bbapp;
        $this->category = ($category === null) ? $this->initCategory() : $category;

        $this->editables = array();
        $this->mode = $mode;
        $this->content = NULL;

        $this->initContentObject();
        $this->initFields();
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
     * @param string $label
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
        $stdClass->label = $this->getLabel();
        $stdClass->description = $this->content->getProperty('description');
        $stdClass->category = $this->getCategory();
        $stdClass->editables = $this->getFields();
        $stdClass->uid = $this->content->getUid();
        $stdClass->render = ($withRender) ? $this->getRender() : "";
        $stdClass->sortable = array(
            'created' => 'Creation date',
            'modified' => 'Last modification date'
        );

        if (is_array($this->content->getProperty()) && array_key_exists('indexation', $this->content->getProperty())) {
            foreach ($this->content->getProperty('indexation') as $indexedElement) {
                $indexedElement = (array) $indexedElement;
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

        return $stdClass;
    }

}
