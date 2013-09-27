<?php

namespace BackBuilder\ClassContent\Exception;

/**
 * Exception thrown if the revision is conflicted
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Exception
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class RevisionConflictedException extends ClassContentException
{

    /**
     * The default error code
     * @var int
     */
    protected $_code = self::REVISION_CONFLICTED;

}