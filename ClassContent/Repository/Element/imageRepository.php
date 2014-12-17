<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\ClassContent\Repository\Element;

use BackBee\ClassContent\AClassContent;
use BackBee\ClassContent\Element\image as elementImage;

/**
 * image repository
 * @category    BackBee
 * @package     BackBee\ClassContent
 * @subpackage  Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class imageRepository extends fileRepository
{
    /**
     * Move an uploaded file to the temporary directory and update image content
     * @param  \BackBee\ClassContent\AClassContent                   $file
     * @param  string                                                    $newfilename
     * @param  string                                                    $originalname
     * @param  string                                                    $src
     * @return boolean|string
     * @throws \BackBee\ClassContent\Exception\ClassContentException Occures on invalid content type provided
     */
    public function updateFile(AClassContent $file, $newfilename, $originalname = null, $src = null)
    {
        if (false === ($file instanceof elementImage)) {
            throw new \BackBee\ClassContent\Exception\ClassContentException('Invalid content type');
        }

        if (false === $newfilename = parent::updateFile($file, $newfilename, $originalname, $src)) {
            return false;
        }

        /*$size = getimagesize($newfilename);
        list($width, $height) = $size;
        $file->setParam('width', $width, 'scalar');
        $file->setParam('height', $height, 'scalar');
        */

        return $newfilename;
    }
}
