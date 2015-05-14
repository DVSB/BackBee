<?php

namespace BackBee\ClassContent\Exception;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class InvalidContentTypeException extends \InvalidArgumentException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($contentType, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('`%s` is not a valid content type.', $contentType), $code, $previous);
    }
}
