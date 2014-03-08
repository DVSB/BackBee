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

    /**
     * All content categories for this BBApp instance
     * @var Category[]
     */
    private static $_categories;

    private static $_classnames_by_category = array();
    /**
     * Array of content classnames for this category
     * @var array
     */
    private $_classnames;
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
        $this->_classnames = array();
        $this->contents = array();
        $this->name = $name;
        $this->label = $name;
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

    public static function getContentsByCategory(array $classcontent_dirs, $category)
    {
        self::getCategories($classcontent_dirs);
        if (false === array_key_exists($category, self::$_classnames_by_category)) {
            self::$_classnames_by_category[$category] = array();
        }

        $contents = array();
        foreach (self::$_classnames_by_category[$category] as $classname) {
            $contentRender = new ContentRender($classname, $this->application, $this->name);
            $contentRender->setLabel($label);
            $contents[] = $contentRender;
        }

        return $contents;
    }

    /**
     * Returns an array of defined categories for the BBApp instance
     * @param array $classcontent_dirs
     * @return array
     */
    public static function getCategories(array $classcontent_dirs)
    {
        if (null === self::$_categories) {
            self::$_categories = array("tous" => new Category("Tous", null, true));
            foreach ($classcontent_dirs as $classcontentdir) {
                $files = File::getFilesRecursivelyByExtension($classcontentdir, 'yml');
                if (true === is_array($files) && 0 < count($files)) {
                    self::_getCategoriesFromFiles($files, $classcontentdir);
                }
            }
        }
        return self::$_categories;
    }

    /**
     * Returns the content classname according to a file
     * Can be call by array_walk
     * @param string $item
     * @param string $key
     * @param string $basedir
     * @return string
     */
    private static function _getClassNameFromFile(&$item, $key, $basedir)
    {
        $item = str_replace(array($basedir, DIRECTORY_SEPARATOR), array('\BackBuilder\ClassContent', NAMESPACE_SEPARATOR), File::removeExtension($item));
        return $item;
    }

    /**
     * Returns an array of Category from ClassContent files
     * @param array $files
     * @param string $basedir
     * @return array
     */
    private static function _getCategoriesFromFiles(array $files, $basedir)
    {
        array_walk($files, array('\BackBuilder\Services\Content\Category', '_getClassNameFromfile'), $basedir);
        foreach ($files as $classname) {
            if (false === $properties = self::_getContentCategories($classname)) {
                continue;
            }

            foreach ($properties as $property) {
                self::_addClassnameToCategory($classname, $property);
                if (true === array_key_exists($property, self::$_categories)) {
                    self::$_categories[$property]->addClassname($classname);
                    continue;
                }

                self::$_categories[$property] = new Category($property);
                self::$_categories[$property]->addClassname($classname);
            }
        }

        return self::$_categories;
    }

    /**
     * Returns categories of a content by its classname
     * @param string $classname
     * @return array|FALSE
     */
    private static function _getContentCategories($classname)
    {
        if (false === class_exists($classname)) {
            return false;
        }

        $content = new $classname();
        if (null === $categories = $content->getProperty('category')) {
            return false;
        }

        if (false === is_array($categories)) {
            $categories = array($categories);
        }

        foreach ($categories as &$category) {
            $category = ucfirst($category);
        }
        unset($category);

        return $categories;
    }

    /**
     * Adds a new classname for category
     * @param string $classname
     * @param string $category
     */
    private static function _addClassnameToCategory($classname, $category)
    {
        if (false === array_key_exists($category, self::$_classnames_by_category)) {
            self::$_classnames_by_category[$category] = array();
        }

        if (false === in_array($classname, self::$_classnames_by_category[$category])) {
            self::$_classnames_by_category[$category][] = $classname;
        }
    }

    /**
     * Returns the content classnames for this category
     * @return array
     * @codeCoverageIgnore
     */
    public function getClassnames()
    {
        return $this->_classnames;
    }

    /**
     * Add a new content classname for this category
     * @param string $classname
     * @return \BackBuilder\Services\Content\Category
     */
    public function addClassname($classname)
    {
        if (false === in_array($classname, $this->_classnames)) {
            $this->_classnames[] = $classname;
        }
        return $this;
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
