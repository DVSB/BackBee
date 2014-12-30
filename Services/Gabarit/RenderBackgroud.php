<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Services\Gabarit;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Gabarit
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class RenderBackgroud
{
    private static $BG_COLOR = array('R' => 255, 'G' => 204, 'B' => 102);      // orange
    private static $GUTTER_COLOR = array('R' => 255, 'G' => 255, 'B' => 255);    // gray
    private static $BG_HEIGHT = 1;
    private $imageBackGround;
    private $cols;
    private $colWidth;
    private $gutterWidth;

    public function __construct($cols, $col_width, $gutter_width, $pathToDest)
    {
        $this->generateImageBackGround($cols, $col_width, $gutter_width, $pathToDest);
    }

    private function generateImageBackGround($cols, $col_width, $gutter_width, $pathToDest)
    {
        $img_width = (($col_width + $gutter_width) * $cols) - $gutter_width;
        $img_source = imagecreate($img_width, self::$BG_HEIGHT);
        $bgcolor = imagecolorallocate($img_source, self::$BG_COLOR['R'], self::$BG_COLOR['G'], self::$BG_COLOR['B']);

        $img_gutter = imagecreate($col_width, self::$BG_HEIGHT);
        $grey = imagecolorallocate($img_gutter, self::$GUTTER_COLOR['R'], self::$GUTTER_COLOR['R'], self::$GUTTER_COLOR['R']);

        $step = $col_width;

        imagecopymerge($img_source, $img_gutter, $step, 0, 0, 0, $gutter_width, self::$BG_HEIGHT, 100);

        $i = 1;
        while ($i < $cols) {
            imagecopymerge($img_source, $img_gutter, $step + ($gutter_width + $col_width) * $i, 0, 0, 0, $gutter_width, self::$BG_HEIGHT, 100);
            $i++;
        }

        $this->imageBackGround = $img_source;
        imagepng($img_source, $pathToDest);
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getImageBackGround()
    {
        return $this->imageBackGround;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCols()
    {
        return $this->cols;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                      $cols
     * @return \BackBee\Services\Gabarit\RenderBackgroud
     */
    public function setCols($cols)
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getColWidth()
    {
        return $this->colWidth;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                      $colWidth
     * @return \BackBee\Services\Gabarit\RenderBackgroud
     */
    public function setColWidth($colWidth)
    {
        $this->colWidth = $colWidth;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getGutterWidth()
    {
        return $this->gutterWidth;
    }

    /**
     * @codeCoverageIgnore
     * @param  type                                      $gutterWidth
     * @return \BackBee\Services\Gabarit\RenderBackgroud
     */
    public function setGutterWidth($gutterWidth)
    {
        $this->gutterWidth = $gutterWidth;

        return $this;
    }
}
