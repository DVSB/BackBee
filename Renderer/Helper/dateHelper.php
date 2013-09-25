<?php

namespace BackBuilder\Renderer\Helper;

use BackBuilder\Renderer\ARenderer;
use BackBuilder\Renderer\Exception\RendererException;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of date
 *
 * @author nicolas
 */
class dateHelper extends AHelper {

    private static $DATE_ERROR = 0;
    private static $DATE_TIMESTAMP = 1;
    private static $DATE_STRING = 2;
    private static $DATE_OBJECT = 3;
    private static $DATE_ELEMENT = 4;
    public static $CULTURE_FR = "fr";
    public static $CULTURE_EN = "en";
    private $format;
    private $timezone;
    private $date;
    private $culture;

    private function initFormat($format = NULL) {
        if (NULL === $format) {
            $configDate = $this->_renderer->getApplication()->getConfig()->getDateConfig();

            if (array_key_exists('format', $configDate))
                $this->format = $configDate['format'];
            else
                throw new RendererException("Config from date 'format' is incorrect");
        }
        else
            $this->format = $format;
    }

    private function initCulture($culture = NULL) {
        if (NULL !== $culture) {
            $configCulture = $this->_renderer->getApplication()->getConfig()->getCultureConfig();
            if (array_key_exists('default', $configCulture))
                $this->culture = $configCulture['default'];
            else
                throw new RendererException("Config from culture 'default' is incorrect");
        }
        else
            $this->culture = $culture;
    }

    private function initTimezone($timezone = NULL) {
        if (NULL === $timezone) {
            $config = $this->_renderer->getApplication()->getConfig()->getDateConfig();
            if (array_key_exists('timezone', $config))
                $this->timezone = $config['timezone'];
            else
                throw new RendererException("Config from date 'timezone' is incorrect");
        }
        else
            $this->timezone = $timezone;
    }

    private function getType($date) {
        if (is_a($date, "DateTime"))
            return self::$DATE_OBJECT;
        if (preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $date))
            return self::$DATE_STRING;
        if ($date instanceof \BackBuilder\ClassContent\Element\date)
            return self::$DATE_ELEMENT;
        if (preg_match('/^[\d]+$/', $date))
            return self::$DATE_TIMESTAMP;
        return self::$DATE_ERROR;
    }

    private function setDateTime($date = "") {
        $type = $this->getType($date);

        switch ($type) {
            case self::$DATE_ERROR:
                $this->date = NULL;
//                throw new RendererException("Type date wrong for 'DateTime' object");
                break;
            case self::$DATE_OBJECT:
                $this->date = $date;
                break;
            case self::$DATE_STRING:
                $this->date = new \DateTime($date);
                break;
            case self::$DATE_TIMESTAMP:
                $this->date = new \DateTime();
                $this->date->setTimestamp($date);
                break;
            case self::$DATE_ELEMENT:
                if ('' == $date->value) {
                    $this->date = NULL;
                } else {
                    $this->date = new \DateTime();
                    $this->date->setTimestamp($date->value ? $date->value : 0);
                }
                break;
            default: $this->date = NULL;
                break;
        }
    }

    private function initDateTimezone($date, $format = NULL, $timezone = NULL, $culture = NULL) {
        $this->setDateTime($date);
        $this->initFormat($format);
        $this->initTimezone($timezone);
        $this->initCulture($culture);
    }

    /**
     *
     * @param string|DateTime|timestamp $date
     * @param string $format
     * @param string $timezone
     * @return string 
     */
    public function __invoke($date = NULL, $format = NULL, $timezone = NULL, $culture = NULL) {
        if (NULL === $date)
            return '';

        $this->initDateTimezone($date, $format, $timezone, $culture);
        return $this->getDate($format, $timezone, $culture);
    }

    public function getDate($format = NULL, $timezone = NULL, $culture = NULL) {
        if (NULL === $this->date)
            return '';
        if (NULL !== $this->timezone)
            $this->date->setTimezone(new \DateTimeZone($this->timezone));
        if (NULL !== $timezone)
            $this->date->setTimezone($timezone);

        //if (NULL !== $culture && self::$CULTURE_FR == $culture)
        //return strftime((NULL !== $format) ? $format: $this->format, $this->date->getTimestamp());
        $format = (NULL !== $format) ? $format : $this->format;
        if (strpos($format, "%") === false) {
            return $this->date->format($format);
        } else {
            $matches = array();
            if (preg_match_all('/%[a-z]/i', $format, $matches)) {
                foreach ($matches[0] as $match) {
                    $format = str_replace($match, strftime($match, $this->date->format("U")), $format);
                }
            }
            return $format;
        }
    }

}
