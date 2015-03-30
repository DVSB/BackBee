<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Tests\Fixtures\Provider;

use Faker\Provider\Base;
use Faker\Provider\Lorem;

class Theme extends Base
{
    public function uid()
    {
        return md5(mt_rand());
    }

    public function themeName()
    {
        return Lorem::word();
    }

    public function description()
    {
        return implode(' ', Lorem::words(mt_rand(10, 15)));
    }

    public function screenshot()
    {
        return 'screenshot.png';
    }

    public function folder()
    {
        return Lorem::word();
    }

    public function extend()
    {
        $themes = array('default', 'test-themes');

        return $themes[mt_rand(0, (count($themes) - 1))];
    }

    public function architecture()
    {
        return array(
            'listeners_dir' => 'listeners',
            'helpers_dir' => 'helpers',
            'template_dir' => 'scripts',
            'css_dir' => 'css',
            'less_dir' => 'less',
            'js_dir' => 'js',
            'img_dir' => 'img',
        );
    }

    public function themeEntity()
    {
        return array(
            'uid' => $this->uid(),
            'name' => $this->themeName(),
            'site_uid' => $this->uid(),
            'folder' => $this->folder(),
            'description' => $this->description(),
            'architecture' => $this->architecture(),
            'dependency' => $this->extend(),
            'screenshot' => $this->screenshot(),
        );
    }
}
