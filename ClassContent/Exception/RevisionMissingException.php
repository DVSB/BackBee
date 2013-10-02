<?php

namespace BackBuilder\ClassContent\Exception;

/**
 * Exception thrown if none draft is defined for the content
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class RevisionMissingException extends ClassContentException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::REVISION_MISSING;

}
