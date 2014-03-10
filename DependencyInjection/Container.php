<?php

namespace BackBuilder\DependencyInjection;

use BackBuilder\Event\Event;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Extended Symfony Dependency injection component
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class Container extends ContainerBuilder
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
            if (false === $this->hasDefinition($id)) {
                throw $e;
            }

            if (false === $this->getDefinition($id)->isSynthetic()) {
                throw $e;
            }
        }
        
        if (true === in_array('event.dispatcher', array_keys($this->services))) {
            if (null !== $service && true === $this->hasDefinition($id)) {
                $definition = $this->getDefinition($id);
                if (0 < count($tags = $definition->getTags())) {
                    foreach ($tags as $tag => $datas) {
                        $this->services['event.dispatcher']->dispatch(
                            'service.tagged.' . $tag,
                            new Event($service)
                        );
                    }
                }
            }
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
        $matches = array();
        if (preg_match('/^@([a-z0-9.-]+)$/i', trim($item), $matches)) {
            if ($this->has($matches[1])) {
                return $this->get($matches[1]);
            }
        }

        return $item;
    }
}
