<?php
namespace BackBuilder\FrontController\Exception;

use BackBuilder\Exception\BBException;

use Symfony\Component\HttpFoundation\Request;

/**
 * Exception thrown when an HTTP request can not be handled
 * The associated HTTP Code error is obtain by decreasing the error code by 6000
 *
 * @category    BackBuilder
 * @package     BackBuilder\FrontController\Exception
 * @copyright   Lp system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 */
class FrontControllerException extends BBException {
    const UNKNOWN_ERROR     = 6000;
    const BAD_REQUEST       = 6400;
    const NOT_FOUND         = 6404;
    const INTERNAL_ERROR    = 6500;

    protected $_code = self::UNKNOWN_ERROR;

    /**
     * The current request handled
     * @var Request
     */
    private $_request;

    /**
     * Set the current request
     * @param Request $request
     */
    public function setRequest(Request $request) {
        $this->_request = $request;
    }

    /**
     * Return the current request
     * @return Request The current request generating an error
     */
    public function getRequest() {
        return $this->_request;
    }
}