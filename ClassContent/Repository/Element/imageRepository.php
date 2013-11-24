<?php

namespace BackBuilder\ClassContent\Repository\Element;

use BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\image as elementImage;

/**
 * image repository
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class imageRepository extends fileRepository
{

    /**
     * Move an uploaded file to the temporary directory and update image content
     * @param \BackBuilder\ClassContent\AClassContent $file
     * @param string $newfilename
     * @param string $originalname
     * @return boolean|string
     * @throws \BackBuilder\ClassContent\Exception\ClassContentException Occures on invalid content type provided
     */
    public function updateFile(AClassContent $file, $newfilename, $originalname = null)
    {
        if (false === ($file instanceof elementImage)) {
            throw new \BackBuilder\ClassContent\Exception\ClassContentException('Invalid content type');
        }

        if (false === $newfilename = parent::updateFile($file, $newfilename, $originalname)) {
            return false;
        }

        $size = getimagesize($newfilename);
        list($width, $height) = $size;
        $file->setParam('width', $width, 'scalar');
        $file->setParam('height', $height, 'scalar');

        return $newfilename;
    }

}