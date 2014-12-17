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

namespace BackBee\Logging;

use Doctrine\DBAL\Logging\SQLLogger;
use BackBee\BBApplication;
use BackBee\Logging\Appender\IAppender;
use BackBee\Logging\Exception\LoggingException;
use BackBee\FrontController\Exception\FrontControllerException;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * @category    BackBee
 * @package     BackBee/Logging
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Logger extends DebugStack implements LoggerInterface, SQLLogger
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
    private $_priorities_name;
    private $_errorHandling = false;
    private $_errorHandlers;
    private $_exceptionHandling = false;
    private $_exceptionHandlers;
    private $_buffer;
    private $_start;
    private $_mailto;

    public function __call($method, $args)
    {
        $priorityname = strtoupper($method);

        if (!($priority = array_search($priorityname, $this->_priorities))) {
            throw new LoggingException(sprintf('Unkown priority `%s`.', $priorityname));
        }

        if (0 == count($args)) {
            throw new LoggingException('None log message provided.');
        }

        $this->log($priority, $args[0], 0 < count($args) ? $args[1] : array());
    }

    public function __construct(BBApplication $application = null, IAppender $appender = null)
    {
        if (null !== $application) {
            $this->_application = $application;
        }

        if (null !== $appender) {
            $this->addAppender($appender);
        }

        $this->_uniqid = uniqid('', true);

        $r = new \ReflectionClass($this);
        $this->_priorities_name = $r->getConstants();
        $this->_priorities = array_flip($this->_priorities_name);

        $this->setLevel(self::ERROR);

        if (null !== $this->_application) {
            if (null !== $loggingConfig = $this->_application->getConfig()->getLoggingConfig()) {
                if ($this->_application->isDebugMode()) {
                    error_reporting(E_ALL);
                    $this->setLevel(Logger::DEBUG);
                } elseif (array_key_exists('level', $loggingConfig)) {
                    $this->setLevel(strtoupper($loggingConfig['level']));
                }

                if (array_key_exists('logfile', $loggingConfig)) {
                    if (false === realpath(dirname($loggingConfig['logfile']))) {
                        $loggingConfig['logfile'] = $application->getBaseDir().DIRECTORY_SEPARATOR.$loggingConfig['logfile'];
                    }
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
        if (null !== $this->_appenders) {
            foreach ($this->_appenders as $appender) {
                $appender->close();
            }
        }
    }

    private function _setErrorHandler()
    {
        if (true === $this->_errorHandling) {
            return;
        }

        $this->_errorHandlers = set_error_handler(array($this, 'errorHandler'));
        $this->_errorHandling = true;

        return $this;
    }

    private function _setExceptionHandler()
    {
        if ($this->_exceptionHandling) {
            return;
        }

        $this->_exceptionHandlers = set_exception_handler(array($this, 'exceptionHandler'));
        $this->_exceptionHandling = true;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @param  \BackBee\Logging\Appender\IAppender $appender
     * @return \BackBee\Logging\Logger
     */
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

        if (null !== $this->_errorHandlers) {
            return call_user_func($this->_errorHandlers, $errno, $errstr, $errfile, $errline, $errcontext);
        }

        return false;
    }

    public function exceptionHandler(\Exception $exception)
    {
        if ($exception instanceof FrontControllerException) {
            $httpCode = $exception->getCode() - FrontControllerException::UNKNOWN_ERROR;

            // Not logging when not found
            if ($httpCode !== 404) {
                $this->error(sprintf('Error occurred in file `%s` at line %d with message: %s', $exception->getFile(), $exception->getLine(), $exception->getMessage()));
            }

            $r = new \ReflectionClass($exception);
            $errors = array_flip($r->getConstants());

            $title = str_replace('_', ' ', $errors[$exception->getCode()]);
            $message = $exception->getMessage();
            $error_trace = '';

            if (null !== $this->_application) {
                $error_trace .= ' in '.$exception->getFile().' on line '.$exception->getLine().'</th></tr>';
                foreach ($exception->getTrace() as $trace) {
                    $this->getTemplateError($trace);
                }

                $previous = $exception->getPrevious();
                while (NULL !== $previous) {
                    // Not logging when not found
                    if ($httpCode !== 404) {
                        $this->error(sprintf('Cause By : Error occurred in file `%s` at line %d with message: %s', $previous->getFile(), $previous->getLine(), $previous->getMessage()));
                    }

                    $error_trace .= '<tr><th colspan="5">Caused by: '.$previous->getMessage().
                            ' in '.$previous->getFile().' on line '.$previous->getLine().'</td></tr>';
                    foreach ($previous->getTrace() as $trace) {
                        $this->getTemplateError($trace);
                    }
                    $previous = $previous->getPrevious();
                }
            }

            if (false === $content = $this->_application->getRenderer()->reset()->error($httpCode, $title, $message, $error_trace)) {
                if ($this->_application->isDebugMode()) {
                    $content = '<link type="text/css" rel="stylesheet" href="ressources/css/debug.css"/>';
                }
                $content .= '<h1>'.$httpCode.': '.$title.'<h1>';
                $content .= '<table>';
                $content .= '<tr><th colspan="5">'.$message;
                if ($this->_application->isDebugMode()) {
                    $content .= $error_trace;
                } else {
                    $content .= '</th></tr>';
                }
                $content .= '</table>';
            }

            $this->_sendErrorMail(
                $title,
                "<h1>$httpCode: $title</h1><h2>$message</h2><p>Referer: "
                .$this->_application->getContainer()->get('request')->server->get('HTTP_REFERER')."</p>$error_trace"
            );

            $response = new Response($content, $httpCode);
            $response->send();
            die();
        } else {
            $this->error(sprintf('Error occurred in file `%s` at line %d with message: %s', $exception->getFile(), $exception->getLine(), $exception->getMessage()));

            if (false === $this->_application->isClientSAPI()) {
                if (!headers_sent()) {
                    header("HTTP/1.0 500 Internal Server Error");
                } else {
                    $this->error(sprintf('Error occurred in file `%s` at line %d with message: %s', __FILE__, __LINE__, 'This error should not happend, headers already sents'));
                }

                if (false === $this->_application->isDebugMode() && 1 != ini_get('display_errors')) {
                    exit(-1);
                }
            }

            if (null !== $this->_application && $this->_application->isDebugMode()) {
                echo 'An error occured: '.$exception->getMessage().' (errNo: '.$exception->getCode().')'.PHP_EOL;
                foreach ($exception->getTrace() as $trace) {
                    echo 'Trace: line '.
                    (array_key_exists('line', $trace) ? $trace['line'] : '-').': '.
                    (array_key_exists('file', $trace) ? $trace['file'] : 'unset file').', '.
                    (array_key_exists('class', $trace) ? $trace['class'] : '').
                    (array_key_exists('type', $trace) ? $trace['type'] : '').
                    (array_key_exists('function', $trace) ? $trace['function'] : 'unknown_function').'()'.PHP_EOL;
                }

                $previous = $exception->getPrevious();
                while (null !== $previous) {
                    echo PHP_EOL.'Caused by: '.$previous->getMessage().' (errNo: '.$previous->getCode().')'.PHP_EOL;
                    foreach ($previous->getTrace() as $trace) {
                        echo 'Trace: line '.
                        (array_key_exists('line', $trace) ? $trace['line'] : '-').': '.
                        (array_key_exists('file', $trace) ? $trace['file'] : 'unset file').', '.
                        (array_key_exists('class', $trace) ? $trace['class'] : '').
                        (array_key_exists('type', $trace) ? $trace['type'] : '').
                        (array_key_exists('function', $trace) ? $trace['function'] : 'unknown_function').'()'.PHP_EOL;
                    }
                    $previous = $previous->getPrevious();
                }
            }
        }

//        if (null !== $this->_exceptionHandlers) {
//            return call_user_func($this->_exceptionHandlers, $exception);
//        }

        exit(-1);
    }

    private function getTemplateError($trace)
    {
        return '<tr>'.
                '<td>'.(array_key_exists('file', $trace) ? $trace['file'] : 'unset file').': '.
                (array_key_exists('line', $trace) ? $trace['line'] : '-').'</td>'.
                '<td>'.(array_key_exists('class', $trace) ? $trace['class'] : '').
                (array_key_exists('type', $trace) ? $trace['type'] : '').
                (array_key_exists('function', $trace) ? $trace['function'] : 'unknown_function').'()</td>'.
                '</tr>';
    }

    public function log($level, $message, array $context = array())
    {
        if (null !== $this->_buffer) {
            $buffer = $this->_buffer;
            $this->_buffer = null;
            $this->log($level, $buffer);
        }

        if (0 == count($this->_appenders)) {
            throw new LoggingException('None appenders defined.');
        }

        if (array_key_exists(strtoupper($level), $this->_priorities_name)) {
            $level = $this->_priorities_name[strtoupper($level)];
        }

        if (!array_key_exists($level, $this->_priorities)) {
            throw new LoggingException(sprintf('Unkown priority level `%d`.', $level));
        }

        if ($level > $this->_level) {
            return;
        }

        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $message = sprintf("http://%s%s : %s", $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $message);
        } elseif (PHP_SAPI == 'cli') {
            if (isset($argv)) {
                $message = sprintf("%s : %s", implode(" ", $argv), $message);
            }
        }

        foreach ($this->_appenders as $appender) {
            $appender->write(array('d' => @date('Y/m/d H:i:s'),
                'p' => $this->_priorities[$level],
                'm' => $message,
                'u' => $this->_uniqid, ));
        }
    }

    public function setLevel($level)
    {
        if (!is_numeric($level)) {
            $r = new \ReflectionClass($this);
            $level = $r->getConstant($level);
        }

        if (!array_key_exists($level, $this->_priorities)) {
            throw new LoggingException(sprintf('Unkown priority level `%d`.', $level));
        }

        $this->_level = $level;

        return $this;
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        if (self::DEBUG === $this->_level) {
            $this->_start = microtime(true);
            //$this->_buffer = '[Doctrine] ' . $sql . ' with ' . var_export($params, true); //old throw error  "Nesting level too deep - recursive dependency?"
            $this->_buffer = '[Doctrine] '.$sql;
        }
    }

    public function stopQuery()
    {
        if (self::DEBUG === $this->_level) {
            $buffer = $this->_buffer;
            $this->_buffer = null;
            $this->log(self::DEBUG, $buffer.' in '.(microtime(true) - $this->_start).'ms');
        }
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * @codeCoverageIgnore
     * @param type  $message
     * @param array $context
     */
    public function debug($message, array $context = array())
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

            if (is_array($from)) {
                $from = reset($from);
            }
            if (is_array($from_name)) {
                $from_name = reset($from_name);
            }

            $mail = \Swift_Message::newInstance($subject, $message, 'text/html', 'utf-8');
            $mail->addFrom($from, $from_name);

            foreach ($this->_mailto as $to) {
                $mail->addTo($to);
            }

            $mailer->send($mail);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get priority name by its code
     *
     * @param  int         $code
     * @return string|null
     */
    protected function getPriorityName($code)
    {
        return isset($this->_priorities[$code]) ? $this->_priorities[$code] : null;
    }
}
