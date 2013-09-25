<?php

namespace BackBuilder\Services\Content;

use Symfony\Component\Yaml\Yaml as parserYaml,
    BackBuilder\Services\Content\ContentRender,
    BackBuilder\Util\File;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Category
 *
 * @author Nicolas BREMONT<nicolas.bremont@group-lp.com>
 */
class Category {

    private $name;
    private $label;
    private $contents;
    private $bbapp;
    private $selected;

    private function lowerCatNames($catArray) {
        $result = array();
        $lambda = create_function('$cat', 'return strtolower($cat);');
        if (isset($catArray) && is_array($catArray)) {
            $result = array_map(function($cat) {
                        return strtolower($cat);
                    }, $catArray);
        }
        return $result;
    }

    private function setContents() {
        $contents = array();
        foreach($this->bbapp->getClassContentDir() as $classcontentdir) {
            $files = self::globRecursive($classcontentdir . DIRECTORY_SEPARATOR . '*.yml');
            foreach ($files as $file) {
                File::resolveFilepath($file);
                $dataYaml = parserYaml::parse($file);
                foreach ($dataYaml as $name => $item) {
                    if (isset($item['properties']['category']) && in_array(strtolower($this->name), $this->lowerCatNames($item['properties']['category']))) {
                        $str = str_replace($classcontentdir . DIRECTORY_SEPARATOR, '', $file);
                        $str = str_replace('/', '\\', $str);
                        $name = substr($str, 0, -4);
                        if ($name) {
                            $label = array_key_exists('name', $item['properties']) ? $item['properties']['name'] : $name;
                            $contentRender = new ContentRender($name, $this->bbapp, $this->name);
                            $contentRender->setLabel($label);
                            $contents[] = $contentRender;
                        }
                    }
                }
            }
        }
        $this->contents = $contents;
    }

    public function __construct($name, $bbapp = null, $selected = false) {
        $this->contents = array();
        $this->name = $name;
        $this->bbapp = $bbapp;
        $this->selected = $selected;
    }

    static function getCacheKey() {
        return md5(__METHOD__);
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getBBapp() {
        return $this->bbapp;
    }

    public function setBBapp($bbapp) {
        $this->bbapp = $bbapp;
    }

    public function getSelected() {
        return $this->selected;
    }

    public function setSelected($selected) {
        $this->selected = $selected;
    }

    public function getLabel() {
        return $this->label;
    }

    public function setLabel($label) {
        $this->label = $label;
    }

    public function getContents() {
        if ($this->contents === array())
            $this->setContents();
        return $this->contents;
    }

    public static function getCategories($bbapp) {
        $categories = array("tous" => new Category("Tous", $bbapp, true));
        foreach($bbapp->getClassContentDir() as $classcontentdir) {
            $files = self::globRecursive($classcontentdir . DIRECTORY_SEPARATOR . '*.yml');
            if (is_array($files)) {
                $categories = array_merge($categories, self::getFilesCategory($files));
            }
        }
        return $categories;
    }

    static function getFilesCategory($files = array()) {
        $categories = array();
        if (!isset($files) && !is_array($files))
            return;
        foreach ($files as $file) {
            $dataYaml = parserYaml::parse($file);
            foreach ($dataYaml as $name => $item) {
                if (isset($item['properties']['category'])) {
                    foreach ($item['properties']['category'] as $cat) {
                        $category = new Category(ucfirst($cat));
                        $category->setLabel(ucfirst($cat));
                        $categories[$cat] = $category;
                    }
                }
            }
        }
        return $categories;
    }

    public static function globRecursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        if (!$files) $files = array();
        if (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT))
                foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
                        $files = array_merge($files, self::globRecursive($dir . '/' . basename($pattern), $flags));
        return $files;
    }

    public function __toStdObject() {
        $stdClass = new \stdClass();
        $stdClass->name = $this->getname();
        $stdClass->uid = uniqid();
        $stdClass->selected = $this->getSelected();
        $stdClass->label = $this->getLabel();
        return $stdClass;
    }

//    static function getContentsCategories($contentnames=array(),$flag=0){
//     $result = array();
//     if(empty($contentnames) && !is_array($contentnames)) return $result;
//     $filesCtn = array();
//     foreach($contentnames as $content){
//        $pattern = BB_REPOSITORY."/ClassContent/".$content.".yml";
//        $files = self::globRecursive($pattern);
//        $filesCtn = array_merge($filesCtn,$files);
//     }
//     if(!$filesCtn) return $result;
//     $categories = self::getFilesCategory($filesCtn);
//     return $categories;
//    }
}

?>
