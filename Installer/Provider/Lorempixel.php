<?php
namespace BackBuilder\Installer\Provider;

use Faker\Provider\Base;

class Lorempixel extends Base
{
    const URL = "http://lorempixel.com/";
    const WITDH = 400;
    const HEIGHT = 200;
    const CATEGORY = 'abstract';

    public function picture($params = array())
    {
        $witdh = array_key_exists('width', $params) ? $params['width'] : static::WITDH;
        $height = array_key_exists('height', $params) ? $params['height'] : static::HEIGHT;
        $category = array_key_exists('category', $params) ? $params['category'] : static::CATEGORY;

        return static::URL . '/' . $witdh . '/' . $height . '/' . $category;
    }
}
