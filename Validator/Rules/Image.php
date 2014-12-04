<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Respect\Validation\Rules;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Image extends AbstractRule
{
    public $mimeTypes = 'image/*';
    public $minWidth = null;
    public $maxWidth = null;
    public $maxHeight = null;
    public $minHeight = null;
    public $maxSize = null;

    public function __construct($mimeTypes = null, $maxSize = null, $minWidth = null, $maxWidth = null, $minHeight = null, $maxHeight = null)
    {
        parent::__construct();
        $this->mimeTypes = $mimeTypes;
        $this->maxSize = $maxSize;
        $this->minWidth = $minWidth;
        $this->maxWidth = $maxWidth;
        $this->minHeight = $minHeight;
        $this->maxHeight = $maxHeight;
    }

    public function validate($input)
    {
        if (null === $input || '' === $input) {
            return false;
        }
        if ($input instanceof UploadedFile) {
            if (null !== $this->maxSize && $this->maxSize < $input->getClientSize()) {
                return false;
            }

            if (null !== $this->mimeTypes && !in_array($input->getClientMimeType(), $this->mimeTypes)) {
                return false;
            }

            $size = \getimagesize($input);

            if (empty($size) || ($size[0] === 0) || ($size[1] === 0)) {
                return false;
            }

            $width = $size[0];
            $height = $size[1];

            if (($this->maxHeight < $height || $this->maxWidth < $width) && ($this->minHeight > $height || $this->minWidth > $width)) {
                return false;
            }
        }

        return true;
    }
}
