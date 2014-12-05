<?php

namespace BackBuilder\Tests\Fixtures\Provider;

use Faker\Provider\Lorem;
use Faker\Provider\Base;

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
