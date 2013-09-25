<?php

namespace BackBuilder\Services\Gabarit;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Gabarit
 * 
 * @author Nicolas BREMONT<nicolas.bremont@group-lp.com>
 */
class RenderBackgroud {
    
    private static $BG_COLOR        = array('R' => 255, 'G' => 204, 'B' => 102);      // orange
    private static $GUTTER_COLOR    = array('R' => 255, 'G' => 255, 'B' => 255);    // gray
    private static $BG_HEIGHT       = 1;

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
        $img_width  = (($col_width + $gutter_width) * $cols) - $gutter_width;
        $img_source = imagecreate($img_width, self::$BG_HEIGHT);
        $bgcolor    = imagecolorallocate($img_source, self::$BG_COLOR['R'], self::$BG_COLOR['G'], self::$BG_COLOR['B']);

        $img_gutter = imagecreate($col_width, self::$BG_HEIGHT);
        $grey       = imagecolorallocate($img_gutter, self::$GUTTER_COLOR['R'], self::$GUTTER_COLOR['R'], self::$GUTTER_COLOR['R']);

        $step       = $col_width;

        imagecopymerge($img_source, $img_gutter, $step, 0, 0, 0, $gutter_width, self::$BG_HEIGHT, 100);

        $i = 1;
        while ($i < $cols)
        {
            imagecopymerge($img_source, $img_gutter, $step + ($gutter_width + $col_width) * $i, 0, 0, 0, $gutter_width, self::$BG_HEIGHT, 100);
            $i++;
        }
        
        $this->imageBackGround = $img_source;
        imagepng($img_source, $pathToDest);

    }
    
    public function getImageBackGround() {
        return $this->imageBackGround;
    }

    public function getCols() {
        return $this->cols;
    }

    public function setCols($cols) {
        $this->cols = $cols;
    }

    public function getColWidth() {
        return $this->colWidth;
    }

    public function setColWidth($colWidth) {
        $this->colWidth = $colWidth;
    }

    public function getGutterWidth() {
        return $this->gutterWidth;
    }

    public function setGutterWidth($gutterWidth) {
        $this->gutterWidth = $gutterWidth;
    }


}

?>
