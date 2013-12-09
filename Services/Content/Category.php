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

use Symfony\Component\Yaml\Yaml as parserYaml,
    BackBuilder\Services\Content\ContentRender,
    BackBuilder\Util\File;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Content
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class Category
{

    private $name;
    private $label;
    private $contents;
    private $application;
    private $selected;

    private function lowerCatNames($catArray)
    {
        $result = array();
        $lambda = create_function('$cat', 'return strtolower($cat);');
        if (isset($catArray) && is_array($catArray)) {
            $result = array_map(function($cat) {
                        return strtolower($cat);
                    }, $catArray);
        }
        return $result;
    }

    private function setContents()
    {
        $contents = array();
        foreach ($this->application->getClassContentDir() as $classcontentdir) {
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
                            $contentRender = new ContentRender($name, $this->application, $this->name);
                            $contentRender->setLabel($label);
                            $contents[] = $contentRender;
                        }
                    }
                }
            }
        }
        $this->contents = $contents;
    }

    public function __construct($name, $application = null, $selected = false)
    {
        $this->contents = array();
        $this->name = $name;
        $this->application = $application;
        $this->selected = $selected;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    static function getCacheKey()
    {
        return md5(__METHOD__);
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @codeCoverageIgnore
     * @param string $name
     * @return \BackBuilder\Services\Content\Category
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @deprecated since version 1.0
     * @codeCoverageIgnore
     * @return \BackBuilder\BBApplication
     */
    public function getBBapp()
    {
        return $this->application;
    }

    /**
     * return $this->application;
     * @return \BackBuilder\BBApplication
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @deprecated since version 1.0
     * @codeCoverageIgnore
     * @param \BackBuilder\BBApplication $application
     */
    public function setBBapp($application)
    {
        $this->application = $application;
    }

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\BBApplication $application
     * @return \BackBuilder\Services\Content\Category
     */
    public function setApplication($application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @codeCoverageIgnore
     * @param type $selected
     * @return \BackBuilder\Services\Content\Category
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @codeCoverageIgnore
     * @param string $label
     * @return \BackBuilder\Services\Content\Category
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getContents()
    {
        if ($this->contents === array())
            $this->setContents();
        return $this->contents;
    }

    public static function getCategories($application)
    {
        $categories = array("tous" => new Category("Tous", $application, true));
        foreach ($application->getClassContentDir() as $classcontentdir) {
            //$files = self::globRecursive($classcontentdir . DIRECTORY_SEPARATOR . '*.yml');
            $files = glob($classcontentdir . DIRECTORY_SEPARATOR . '{*,*' . DIRECTORY_SEPARATOR . '*}.[yY][mM][lL]', GLOB_BRACE);
            if (is_array($files)) {
                $categories = array_merge($categories, self::getFilesCategory($files));
            }
        }
        return $categories;
    }

    static function getFilesCategory($files = array())
    {
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

    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        if (!$files)
            $files = array();
        if (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT))
            foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
                $files = array_merge($files, self::globRecursive($dir . '/' . basename($pattern), $flags));
        return $files;
    }

    public function __toStdObject()
    {
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
