<?php
namespace BackBuilder\Site\Repository;

use BackBuilder\BBApplication,
    BackBuilder\Site\Layout,
    BackBuilder\Util\File;

use Doctrine\ORM\EntityRepository;

class LayoutRepository extends EntityRepository {
    /**
     * Draw a filled rect on image
     *
     * @access private
     * @param ressource $image    The image ressource
     * @param array $clip         The clip rect to draw
     * @param int $background     The background color
     * @param boolean $nowpadding If true don't insert a right padding
     * @param boolean $nohpadding If true don't insert a bottom padding
     */
    private function _drawRect(&$image, $clip, $background, $nowpadding = true, $nohpadding = true) {
        imagefilledrectangle($image,
                             $clip[0],
                             $clip[1],
                             $clip[0] + $clip[2] - (!$nowpadding*1),
                             $clip[1] + $clip[3] - (!$nohpadding*1),
                             $background);
    }
    
    /**
     * Draw a final layout zone on its thumbnail
     *
     * @access private
     * @param ressource $thumbnail The thumbnail ressource
     * @param DOMNode $node        The current node zone
     * @param array $clip          The clip rect to draw
     * @param int $background      The background color
     * @param int $gridcolumn      The number of columns in the grid
     * @param boolean $lastChild   True if the current node is the last child of its parent node
     * @return int The new X axis position;
     */
    private function _drawThumbnailZone(&$thumbnail, $node, $clip, $background, $gridcolumn, $lastChild = false) {
        $x = $clip[0];
        $y = $clip[1];
        $width = $clip[2];
        $height = $clip[3];
        
        if (NULL !== $spansize = preg_replace('/[^0-9]+/', '', $node->getAttribute('class')))
            $width = floor($width * $spansize / $gridcolumn);
        
        if (FALSE !== strpos($node->getAttribute('class'), 'Child'))
            $height = floor($height / 2);
        
        if (!$node->hasChildNodes()) {
            $this->_drawRect($thumbnail, array($x, $y, $width, $height), $background, ($width == $clip[2] || strpos($node->getAttribute('class'), 'hChild')), $lastChild);
            return $width+2;
        }
        
        foreach($node->childNodes as $child) {
            if (is_a($child, 'DOMText'))
                continue;
            
            if ('clear' == $child->getAttribute('class')) {
                $x = $clip[0];
                $y = $clip[1] + floor($height / 2) + 2;
                continue;
            }
            
            $x += $this->_drawThumbnailZone($thumbnail, $child, array($x, $y, $clip[2], $height), $background, $gridcolumn, $node->isSameNode($node->parentNode->lastChild));
        }
        
        return $x + $width - 2;
    }
    
    /**
     * Generate a layout thumbnail according to the configuration
     *
     * @access public
     * @param Layout $layout     The layout to treate
     * @param BBApplication $app The current instance of BBApplication
     * @return mixed FALSE if something wrong, the ressource path of the thumbnail elsewhere
     */
    public function generateThumbnail(Layout $layout, BBApplication $app) {
        // Is the layout valid ?
        if (!$layout->isValid()) return FALSE;
        
        // Is some layout configuration existing ?
        if (NULL === $app->getConfig()->getSection('layout')) return FALSE;
        $layoutconfig = $app->getConfig()->getSection('layout');
        
        // Is some thumbnail configuration existing ?
        if (!isset($layoutconfig['thumbnail'])) return FALSE;
        $thumbnailconfig = $layoutconfig['thumbnail'];
        
        // Is gd available ?
        if (!function_exists('gd_info')) return FALSE;
        $gd_info = gd_info();
        
        // Is the selected format supported by gd ?
        if (!isset($thumbnailconfig['format'])) return FALSE;
        if (TRUE !== $gd_info[strtoupper($thumbnailconfig['format']).' Support']) return FALSE;
        
        // Is the template file existing ?
        if (!isset($thumbnailconfig['template'])) return FALSE;
        $templatefile = $thumbnailconfig['template'];
        $thumbnaildir = dirname($templatefile);
        File::resolveFilepath($templatefile, NULL, array('include_path' => $app->getResourceDir()));
        if (FALSE === file_exists($templatefile) || false === is_readable($templatefile)) return FALSE;
        
        try {
            $gd_function = 'imagecreatefrom'.strtolower($thumbnailconfig['format']);
            $thumbnail = $gd_function($templatefile);
            $thumbnailfile = $thumbnaildir.'/'.$layout->getUid().'.'.strtolower($thumbnailconfig['format']);
            
            // Is a background color existing ?
            if (!isset($thumbnailconfig['background']) || !is_array($thumbnailconfig['background']) || 3 != count($thumbnailconfig['background'])) return FALSE;
            $background = imagecolorallocate($thumbnail, $thumbnailconfig['background'][0],  $thumbnailconfig['background'][1],  $thumbnailconfig['background'][2]);
            
            // Is a clipping zone existing ?
            if (!isset($thumbnailconfig['clip']) || !is_array($thumbnailconfig['clip']) || 4 != count($thumbnailconfig['clip'])) return FALSE;
            
            $gridcolumn = 12;
            if (NULL !== $lessconfig = $app->getConfig()->getSection('less')) {
                if (isset($lessconfig['gridcolumn'])) $gridcolumn = $lessconfig['gridcolumn'];
            }
            
            $domlayout = $layout->getDomDocument();
            if (!$domlayout->hasChildNodes() || !$domlayout->firstChild->hasChildNodes())
                $this->_drawRect($thumbnail, $thumbnailconfig['clip'], $background);
            else
                $this->_drawThumbnailZone($thumbnail, $domlayout->firstChild, $thumbnailconfig['clip'], $background, $gridcolumn);
            
            imagesavealpha($thumbnail, TRUE);
            
            $thumbnaildir = dirname(File::normalizePath($app->getCurrentResourceDir().'/'.$thumbnailfile));
            if (false === is_dir($thumbnaildir)) mkdir($thumbnaildir, 0755, true);
            
            imagepng($thumbnail, File::normalizePath($app->getCurrentResourceDir().'/'.$thumbnailfile));
        } catch (\Exception $e) { return FALSE; }
        
        $layout->setPicPath($thumbnailfile);
        
        return $layout->getPicPath();
    }
    
    public function removeThumbnail(Layout $layout, BBApplication $app)
    {
        $thumbnailfile = $layout->getPicPath();
        File::resolveFilepath($thumbnailfile, NULL, array('include_path' => $app->getResourceDir()));
        
        while (TRUE === file_exists($thumbnailfile) && TRUE === is_writable($thumbnailfile)) {
            @unlink($thumbnailfile);
            
            $thumbnailfile = $layout->getPicPath();
            File::resolveFilepath($thumbnailfile, NULL, array('include_path' => $app->getResourceDir()));
        }
        
        return TRUE;
    }
    
    /**
     * Returns layout models
     *
     * @access public
     * @return array Array of Layout
     */
    public function getModels() {
        try {
            $q = $this->createQueryBuilder('l')
                    ->where('l._site IS NULL')
                    ->orderBy('l._label', 'ASC')
                    ->getQuery();
            return $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}