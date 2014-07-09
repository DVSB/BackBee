<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Event;

use BackBuilder\BBApplication;
use BackBuilder\Config\Config;
use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event as sfEvent;

/**
 * An event dispatcher for BB application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Dispatcher extends EventDispatcher implements DumpableServiceInterface
{
    /**
     * Current BackBuilder application
     * @var BackBuilder\BBApplication
     */
    protected $_application;

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application The current instance of BB application
     */
    public function __construct(BBApplication $application = null, Config $config = null)
    {
        $this->_application = $application;

        if (null === $config && null !== $application) {
            $config = $application->getConfig();
        }

        if (null !== $config) {
            if (null !== $events_config = $config->getRawSection('events')) {
                $this->addListeners($events_config);
            }
        }
    }

    /**
     * Add listener to events
     * @param array $eventsConfig
     */
    public function addListeners(array $eventsConfig)
    {
        foreach ($eventsConfig as $name => $listeners) {
            if (FALSE === array_key_exists('listeners', $listeners)) {
                $this->_application->warning(sprintf('None listener found for `%s` event.', $name));
                continue;
            }

            $listeners['listeners'] = (array) $listeners['listeners'];
            foreach ($listeners['listeners'] as $listener) {
                $this->addListener($name, $listener);
            }
        }
    }

    /**
     * @see EventDispatcherInterface::dispatch
     * @api
     */
    public function dispatch($eventName, sfEvent $event = null)
    {
        if (null !== $this->_application)
            $this->_application->debug(sprintf('Dispatching `%s` event.', $eventName));

        return parent::dispatch($eventName, $event);
    }

    /**
     * Trigger a BackBuilder\Event\Event depending on the entity and event name
     * @param string    $eventName The doctrine event name
     * @param Object    $entity    The entity instance
     * @param EventArgs $eventArgs The doctrine event arguments
     */
    public function triggerEvent($eventName, $entity, $eventArgs = null)
    {
        $event = new Event($entity, $eventArgs);
        if (is_a($entity, 'BackBuilder\ClassContent\AClassContent')) {
            $this->dispatch(strtolower('classcontent.' . $eventName), $event);

            foreach (class_parents($entity) as $class) {
                if ($class === 'BackBuilder\ClassContent\AClassContent') {
                    break;
                }

                $this->dispatch($this->formatEventName($eventName, $class), $event);
            }
        }

        $this->dispatch($this->formatEventName($eventName, $entity), $event);
    }

    private function formatEventName($eventName, $entity)
    {
        if (is_object($entity)) {
            $eventName = strtolower(str_replace(NAMESPACE_SEPARATOR, '.', get_class($entity)) . '.' . $eventName);

            if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
                $prefix = str_replace(NAMESPACE_SEPARATOR, '.', $this->_application->getEntityManager()->getConfiguration()->getProxyNamespace());
                $prefix .= '.' . $entity::MARKER . '.';

                $eventName = str_replace(strtolower($prefix), '', $eventName);
            }
        } else {
            $eventName = strtolower(str_replace(NAMESPACE_SEPARATOR, '.', $entity) . '.' . $eventName);
        }

        if (0 === strpos($eventName, 'backbuilder.')) {
            $eventName = substr($eventName, 12);
        }

        if (0 === strpos($eventName, 'classcontent.')) {
            $eventName = substr($eventName, 13);
        }

        return $eventName;
    }

    /**
     * Return the current instance of BBapplication
     * @return \Backbuilder\BBApplication
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * return the normalize prefix of the eventname depending on classname
     * @param Object $entity
     * @return string
     */
    public function getEventNamePrefix($entity)
    {
        if (is_object($entity)) {
            $eventPrefix = strtolower(str_replace(NAMESPACE_SEPARATOR, '.', get_class($entity)) . '.');
        } else {
            $eventPrefix = strtolower(str_replace(NAMESPACE_SEPARATOR, '.', $entity) . '.');
        }
        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            $prefix = str_replace(NAMESPACE_SEPARATOR, '.', $this->_application->getEntityManager()->getConfiguration()->getProxyNamespace());
            $prefix .= '.' . $entity::MARKER . '.';

            $eventPrefix = str_replace(strtolower($prefix), '', $eventPrefix);
        }

        if (0 === strpos($eventPrefix, 'backbuilder.'))
            $eventPrefix = substr($eventPrefix, 12);
        if (0 === strpos($eventPrefix, 'classcontent.'))
            $eventPrefix = substr($eventPrefix, 13);

        return $eventPrefix;
    }

    public function getListeners($eventName = null)
    {
        $listeners = parent::getListeners($eventName);

        // retrieve services
        foreach ($listeners as &$listener) {
            if (is_string($listener[0]) && 0 === strpos($listener[0], '@')) {
                $listener[0] = $this->_application->getContainer()->get(substr($listener[0], 1));
            }
        }

        return $listeners;
    }


    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return '\BackBuilder\Event\DispatcherProxy';
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = array())
    {
        return array('listeners' => $this->getListeners());
    }

    /**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param array[callback] $listeners The event listeners.
     * @param string          $eventName The name of the event to dispatch.
     * @param Event           $event     The event object to pass to the event handlers/listeners.
     */
    protected function doDispatch($listeners, $eventName, sfEvent $event)
    {
        foreach ($listeners as $listener) {
            $callable = array_shift($listener);
            if (true === is_array($callable)) {
                $listener = $callable;
                $callable = array_shift($listener);
            }

            if (true === is_string($callable)) {
                if (1 === preg_match('#^@(.+)#', $callable, $matches) && true === isset($matches[1])) {
                    $callable = $this->_application->getContainer()->get($matches[1]);
                }
            }

            array_unshift($listener, $callable);
            call_user_func($listener, $event);

            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }
}