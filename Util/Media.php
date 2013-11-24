<?php

namespace BackBuilder\Util;

use BackBuilder\ClassContent\Element\file as elementFile,
    BackBuilder\Exception\InvalidArgumentException;

/**
 * Set of utility methods to deal with media files
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class Media
{

    /**
     * Returns the computed storage filename of an element file
     * @param \BackBuilder\ClassContent\Element\file $content
     * @param int $folder_size Optional, size in characters of the storing folder
     * @return string
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if the provided element file is empty
     */
    public static function getPathFromContent(elementFile $content, $folder_size = 3)
    {
        if (null === $content->getUid() 
                || null === $content->originalname) {
            throw new InvalidArgumentException('Enable to compute path, the provided element file is not yet initialized');
        }

        $folder = '';
        $filename = $content->getUid();
        if (null !== $draft = $content->getDraft()) {
            $filename = $draft->getUid();
        }

        if (0 < $folder_size && strlen($filename) > $folder_size) {
            $folder = substr($filename, 0, $folder_size) . '/';
            $filename = substr($filename, $folder_size);
        }

        $extension = File::getExtension($content->originalname, true);

        return $folder . $filename . $extension;
    }

}