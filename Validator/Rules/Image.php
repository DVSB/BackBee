<?php

namespace Respect\Validation\Rules;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Respect\Validation\Rules\AbstractRule;

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
