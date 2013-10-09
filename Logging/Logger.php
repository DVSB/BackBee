<?php

namespace BackBuilder\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use BackBuilder\BBApplication,
    BackBuilder\Logging\Appender\IAppender,
    BackBuilder\Logging\Exception\LoggingException,
    BackBuilder\FrontController\Exception\FrontControllerException;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface, SQLLogger
{

    const ERROR = 1;
    const WARNING = 2;
    const NOTICE = 3;
    const INFO = 4;
    const DEBUG = 5;

    private $_application;
    private $_uniqid;
    private $_appenders;
    private $_level;
    private $_priorities;
    private $_errorHandling = FALSE;
    private $_errorHandlers;
    private $_exceptionHandling = FALSE;
    private $_exceptionHandlers;
    private $_buffer;
    private $_start;
    private $_mailto;

    public function __call($method, $args)
    {
        $priorityname = strtoupper($method);

        if (!($priority = array_search($priorityname, $this->_priorities)))
            throw new LoggingException(sprintf('Unkown priority `%s`.', $priorityname));

        if (0 == count($args))
            throw new LoggingException('None log message provied.');

        $this->log($priority, $args[0], 0 < count($args) ? $args[1] : array());
    }

    public function __construct(BBApplication $application = NULL, IAppender $appender = NULL)
    {
        if (NULL !== $application)
            $this->_application = $application;
        if (NULL !== $appender)
            $this->addAppender($appender);

        $this->_uniqid = uniqid();

        $r = new \ReflectionClass($this);
        $this->_priorities = array_flip($r->getConstants());

        $this->setLevel(self::ERROR);
        if (NULL !== $this->_application) {
            if (NULL !== $loggingConfig = $this->_application->getConfig()->getLoggingConfig()) {
                if ($this->_application->debugMode()) {
                    error_reporting(E_ALL);
                    $this->setLevel(Logger::DEBUG);
                } else if (array_key_exists('level', $loggingConfig)) {
                    $this->setLevel(strtoupper($loggingConfig['level']));
                }
                if (array_key_exists('appender', $loggingConfig)) {
                    $appenders = (array) $loggingConfig['appender'];
                    foreach ($appenders as $appender) {
                        $this->addAppender(new $appender($loggingConfig));
                    }
                }
                
                if (array_key_exists('mailto', $loggingConfig)) {
                    $this->_mailto = $loggingConfig['mailto'];
                }
            }
        }

        $this->_setErrorHandler()
                ->_setExceptionHandler();
    }

    public function __destruct()
    {
        foreach ($this->_appenders as $appender)
            $appender->close();
    }

    private function _setErrorHandler()
    {
        if (TRUE === $this->_errorHandling)
            return;

        $this->_errorHandlers = set_error_handler(array($this, 'errorHandler'));
        $this->_errorHandling = TRUE;

        return $this;
    }

    private function _setExceptionHandler()
    {
        if ($this->_exceptionHandling)
            return;

        $this->_exceptionHandlers = set_exception_handler(array($this, 'exceptionHandler'));
        $this->_exceptionHandling = TRUE;

        return $this;
    }

    public function addAppender(IAppender $appender)
    {
        $this->_appenders[] = $appender;
        return $this;
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (error_reporting() && $errno) {
            switch ($errno) {
                case E_ERROR:
                case E_USER_ERROR:
                case E_CORE_ERROR:
                    $priority = self::ERROR;
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                case E_CORE_WARNING:
                    $priority = self::WARNING;
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    $priority = self::NOTICE;
                    break;
                default:
                    $priority = self::INFO;
                    break;
            }

            $this->log($priority, sprintf('%s:%d: %s', $errfile, $errline, $errstr));
        }

        if (NULL !== $this->_errorHandlers) {
            return call_user_func($this->_errorHandlers, $errno, $errstr, $errfile, $errline, $errcontext);
        }

        return false;
    }

    public function exceptionHandler(\Exception $exception)
    {
        $this->error(sprintf('Error occurred in file `%s` at line %d with message: %s', $exception->getFile(), $exception->getLine(), $exception->getMessage()));

        if ($exception instanceof FrontControllerException) {
            $httpCode = $exception->getCode() - FrontControllerException::UNKNOWN_ERROR;

            $r = new \ReflectionClass($exception);
            $errors = array_flip($r->getConstants());

            $title = str_replace('_', ' ', $errors[$exception->getCode()]);
            $message = $exception->getMessage();
            $error_trace = '';

            if (NULL !== $this->_application && $this->_application->debugMode()) {
                $error_trace .= '<p>Trace:</p>';
                $error_trace .= '<ul>';
                $error_trace .= '<li>line ' . $exception->getLine() . ': ' . $exception->getFile() . '</li>';
                foreach ($exception->getTrace() as $trace) {
                    $error_trace .= '<li>line ' .
                            (array_key_exists('line', $trace) ? $trace['line'] : '-') . ': ' .
                            (array_key_exists('file', $trace) ? $trace['file'] : 'unset file') . ', ' .
                            (array_key_exists('class', $trace) ? $trace['class'] : '') .
                            (array_key_exists('type', $trace) ? $trace['type'] : '') .
                            (array_key_exists('function', $trace) ? $trace['function'] : 'unknown_function') . '()</li>';
                }
                $error_trace .= '</ul>';

                $previous = $exception->getPrevious();
                while (NULL !== $previous) {
                    $this->error(sprintf('Cause By : Error occurred in file `%s` at line %d with message: %s', $previous->getFile(), $previous->getLine(), $previous->getMessage()));
                    $error_trace .= '<p>Caused by: ' . $previous->getMessage() . '</p>';
                    $error_trace .= '<ul>';
                    $error_trace .= '<li>line ' . $previous->getLine() . ': ' . $previous->getFile() . '</li>';
                    foreach ($previous->getTrace() as $trace) {
                        $error_trace .= '<li>line ' .
                                (array_key_exists('line', $trace) ? $trace['line'] : '-') . ': ' .
                                (array_key_exists('file', $trace) ? $trace['file'] : 'unset file') . ', ' .
                                (array_key_exists('class', $trace) ? $trace['class'] : '') .
                                (array_key_exists('type', $trace) ? $trace['type'] : '') .
                                (array_key_exists('function', $trace) ? $trace['function'] : 'unknown_function') . '()</li>';
                    }
                    $error_trace .= '</ul>';
                    $previous = $previous->getPrevious();
                }
            }

            if (false === $content = $this->_application->getRenderer()->reset()->error($httpCode, $title, $message, $error_trace)) {
                $content = '<h1>' . $httpCode . ': ' . $title . '<h1>';
                $content .= '<h2>' . $message . '<h2>';
                $content .= $error_trace;
            }

            $this->_sendErrorMail($title,  '<h1>' . $httpCode . ': ' . $title . '<h1><h2>' . $message . '<h2>'.$error_trace);
            
            $response = new Response($content, $httpCode);
            $response->send();
            die();
        } else {
            echo 'An error occured: ' . $exception->getMessage() . ' (errNo: ' . $exception->getCode() . ')';
        }

        if (NULL !== $this->_exceptionHandlers) {
            return call_user_func($this->_exceptionHandlers, $exception);
        }

        die();
    }

    public function log($level, $message, array $context = array())
    {
        if (null !== $this->_buffer) {
            $buffer = $this->_buffer;
            $this->_buffer = null;
            $this->log($level, $buffer);
        }

        if (0 == count($this->_appenders))
            throw new LoggingException('None appenders defined.');

        if (!array_key_exists($level, $this->_priorities))
            throw new LoggingException(sprintf('Unkown priority level `%d`.'), $level);

        if ($level > $this->_level)
            return;

        foreach ($this->_appenders as $appender) {
            $appender->write(array('d' => date('Y/m/d H:i:s'),
                'p' => $this->_priorities[$level],
                'm' => $message,
                'u' => $this->_uniqid));
        }
    }

    public function setLevel($level)
    {
        if (!is_numeric($level)) {
            $r = new \ReflectionClass($this);
            $level = $r->getConstant($level);
        }

        if (!array_key_exists($level, $this->_priorities))
            throw new LoggingException(sprintf('Unkown priority level `%d`.', $level));

        $this->_level = $level;

        return $this;
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->_start = microtime(true);
        $this->_buffer = '[Doctrine] ' . $sql . ' with ' . var_export($params, true); //old throw error  "Nesting level too deep - recursive dependency?"
        //$this->_buffer = '[Doctrine] '.$sql;
    }

    public function stopQuery()
    {
        $buffer = $this->_buffer;
        $this->_buffer = null;
        $this->log(self::DEBUG, $buffer . ' in ' . (microtime(true) - $this->_start) . 'ms');
    }

    function emergency($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    function alert($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    function critical($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    private function _sendErrorMail($subject, $message) 
    {
        $application = $this->_application;
        
        if (false === is_array($this->_mailto) || 0 === count($this->_mailto)) {
            return;
        }
        
        try {
            $mailer = $application->getMailer();
            $mailerconfig = $application->getConfig()->getMailerConfig();
            $from = isset($mailerconfig['from']) ? $mailerconfig['from'] : 'no-reply@anonymous.com';
            $from_name = isset($mailerconfig['from_name']) ? $mailerconfig['from_name'] : null;
            
            if (is_array($from)) $from = reset($from);
            if (is_array($from_name)) $from_name = reset($from_name);

            $mail = \Swift_Message::newInstance($subject, $message, 'text/html', 'utf-8');
            $mail->addFrom($from, $from_name);
            
            foreach($this->_mailto as $to) {
                $mail->addTo($to);
            }

            $mailer->send($mail);
        } catch (\Exception $e) {
            return false;
        }
    }
}