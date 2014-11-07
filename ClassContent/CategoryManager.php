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

namespace BackBuilder\ClassContent;

use BackBuilder\AutoLoader\Exception\ClassNotFoundException;
use BackBuilder\Cache\ACache;
use BackBuilder\IApplication as ApplicationInterface;
use BackBuilder\Util\File;

/**
 * CategoryManager provides every classcontent categories of the current application
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class CategoryManager
{
    /**
     * Contains every class content categories (type: BackBuilder\ClassContent\Category)
     * of current application and its bundles
     * @var array
     */
    private $categories;

    private $options;

    /**
     * CategoryManager's constructor
     *
     * @param ApplicationInterface $application application from where we will extract classcontent's categories
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->categories = array();
        $this->options = array(
            'thumbnail_url_pattern' => $application->getRouting()->getUrlByRouteName(
                'bb.classcontent_thumbnail', array(
                    'filename' => '%s.' . $application->getContainer()->getParameter('classcontent_thumbnail.extension')
                )
            ),
        );

        $this->loadCategoriesFromClassContentDirectories($application->getClassContentDir());
    }

    /**
     * Returns category by name or id
     *
     * @param  string $v category name or id
     *
     * @return Category|null return category object if provided name/id exists, else null
     */
    public function getCategory($v)
    {
        $v = $this->buildCategoryId($v);

        return isset($this->categories[$v]) ? $this->categories[$v] : null;
    }

    /**
     * Categories attribute getter
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Parse classcontent directories and hydrate categories attribute
     *
     * @param  array $directories classcontent directories
     */
    private function loadCategoriesFromClassContentDirectories($directories)
    {
        $classcontents = array();
        foreach ($directories as $directory) {
            $classcontents = array_merge($classcontents, array_map(
                function ($path) use ($directory) {
                    return str_replace(
                        DIRECTORY_SEPARATOR,
                        NAMESPACE_SEPARATOR,
                        'BackBuilder\ClassContent' . str_replace(array($directory, '.yml'), array('', ''), $path)
                    );
                },
                File::getFilesRecursivelyByExtension($directory, 'yml')
            ));
        }

        $categories = array();
        foreach ($classcontents as $class) {
            try {
                if (class_exists($class)) {
                    $this->buildCategoryFromClassContent(new $class());
                }
            } catch (ClassNotFoundException $e) { /* nothing to do */ }
        }
    }

    /**
     * Build and/or hydrate Category object with provided classcontent
     *
     * @param  AClassContent $content
     */
    private function buildCategoryFromClassContent(AClassContent $content)
    {
        foreach ((array) $content->getProperty('category') as $category) {
            $visible = true;
            if ('!' === $category[0]) {
                $visible = false;
                $category = substr($category, 1);
            }

            $id = $this->buildCategoryId($category);
            if (false === array_key_exists($id, $this->categories)) {
                $this->categories[$id] = new Category($category, $this->options);
                ksort($this->categories);
            }

            $this->categories[$id]->addBlock($content, $visible, $this->options);
        }
    }

    /**
     * Build id for category by sluggify its name
     *
     * @param  string $name category's name
     *
     * @return string
     */
    private function buildCategoryId($name)
    {
        return strtolower(str_replace(' ', '_', $name));
    }
}
