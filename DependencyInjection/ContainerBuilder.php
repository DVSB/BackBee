<?php

namespace BackBuilder\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\Exception\RuntimeException,
    Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Extended Symfony Dependency injection component
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ContainerBuilder extends SfContainerBuilder
{

    /**
     * Change current method default behavior: if we try to get a synthetic service it will return
     * null instead of throwing an exception;
     * 
     * @see Symfony\Component\DependencyInjection\ContainerBuilder::get()
     */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $service = null;
        try {
            $service = parent::get($id, $invalidBehavior);
        } catch (RuntimeException $e) {
            $definition = $this->getDefinition($id);
            if (false === $definition->isSynthetic()) {
                throw $e;
            }
        } catch (InvalidArgumentException $ex) {
            $method = 'get' . ucfirst($id) . 'Service';
            if (true === method_exists($this, $method)) {
                return $this->$method();
            }

            throw $ex;
        }

        return $service;
    }

    /**
     * Giving a string, try to return the container service or parameter if exists
     * This method can be call by array_walk or array_walk_recursive
     * @param mixed $item
     * @return mixed
     */
    public function getContainerValues(&$item)
    {
        if (false === is_object($item) && false === is_array($item)) {
            $item = $this->_getContainerServices($this->_getContainerParameters($item));
        }

        return $item;
    }

    /**
     * Replaces known container parameters key by their values
     * @param string $item
     * @return string
     */
    private function _getContainerParameters($item)
    {
        $matches = array();
        if (preg_match('/^%([^%]+)%$/', $item, $matches)) {
            if ($this->hasParameter($matches[1])) {
                return $this->getParameter($matches[1]);
            }
        }

        if (preg_match_all('/%([^%]+)%/', $item, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[1] as $expr) {
                if ($this->hasParameter($expr)) {
                    $item = str_replace('%' . $expr . '%', $this->getParameter($expr), $item);
                }
            }
        }

        return $item;
    }

    /**
     * Returns the associated service to item if exists, item itself otherwise
     * @param string $item
     * @return mixed
     */
    private function _getContainerServices($item)
    {
        if (false === is_string($item)) {
            return $item;
        }

        $matches = array();
        if (preg_match('/^@([a-z0-9.-]+)$/i', trim($item), $matches)) {
            if ($this->has($matches[1])) {
                return $this->get($matches[1]);
            }
        }

        return $item;
    }

    /**
     * Returns true if the given service is loaded.
     *
     * @param string $id The service identifier
     *
     * @return Boolean true if the service is loaded, false otherwise
     */
    public function isLoaded($id)
    {
        $id = strtolower($id);

        return isset($this->services[$id]) || method_exists($this, 'get' . strtr($id, array('_' => '', '.' => '_')) . 'Service');
    }

}
