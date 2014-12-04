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

use Twig_Loader_Filesystem;

/**
 * Extends twig default filesystem loader to override some behaviors
 *
 * @category    BackBuilder
 * @package     BackBuilder\Renderer
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class TwigLoaderFilesystem extends Twig_Loader_Filesystem
{
    /**
     * Do same stuff than Twig_Loader_Filesystem::exists() plus check if the file is
     * readable
     *
     * @see  Twig_Loader_Filesystem::exists()
     */
    public function exists($name)
    {
        $name = preg_replace('#/{2,}#', '/', strtr($name, '\\', '/'));
        $exists = parent::exists($name);
        $readable = false;
        if (true === $exists) {
            $readable = is_readable($this->cache[$name]);
        }

        return $readable;
    }

    public function removeAllPaths()
    {
        $this->paths = array();
        $this->cache = array();
    }

    /**
     * Do same stuff than Twig_Loader_Filesystem::exists() plus returns the file
     * itself if it is readable
     *
     * @see  Twig_Loader_Filesystem::findTemplate()
     */
    protected function findTemplate($name)
    {
        try {
            return parent::findTemplate($name);
        } catch (\Twig_Error_Loader $e) {
            $namespace = self::MAIN_NAMESPACE;

            if (true === is_readable($name)) {
                return $name;
            }

            throw new \Twig_Error_Loader(sprintf(
                'Unable to find template "%s" (looked into: %s).',
                $name,
                implode(', ',
                $this->paths[$namespace]))
            );
        }
    }
}
