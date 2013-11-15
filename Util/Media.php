<?php

namespace BackBuilder\Util;

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
    public static function getPathFromContent(\BackBuilder\ClassContent\Element\file $content, $folder_size = 3)
    {
        if (null === $content->getUid() || null === $content->originalname) {
            throw new \BackBuilder\Exception\InvalidArgumentException('Ènable to compute path, the provided element file is not yet initialized');
        }

        $folder = '';
        $filename = (null !== $content->getDraft()) ? $content->getDraft()->getUid() : $content->getUid();

        if (0 < $folder_size && strlen($content->getUid()) > $folder_size) {
            $folder = substr($content->getUid(), 0, $folder_size) . '/';
            $filename = substr($content->getUid(), $folder_size);
        }

        $extension = File::getExtension($content->originalname, true);

        return $folder . $filename . $extension;
    }

}