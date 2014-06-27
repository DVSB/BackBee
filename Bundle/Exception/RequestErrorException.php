<?php
namespace BackBuilder\Bundle\Exception;

use BackBuilder\Exception\BBException;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class RequestErrorException extends BBException
{
    /**
     * @var integer
     */
    private $statusCode;

    /**
     * RequestErrorException's constructor
     * 
     * @param string  $message    
     * @param integer $statusCode 
     */
    public function __construct($message, $statusCode)
    {
        parent::__construct($message);
        
        $this->statusCode = intval($statusCode);
    }

    /**
     * Status code getter
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
