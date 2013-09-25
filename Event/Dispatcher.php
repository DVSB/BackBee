<?php
namespace BackBuilder\Event;

use BackBuilder\BBApplication;

use Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\EventDispatcher\Event as sfEvent;

/**
 * An event dispatcher for BB application
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class Dispatcher extends EventDispatcher
{
    /**
     * Current BackBuilder application
     * @var BackBuilder\BBApplication
     */
    private $_application;

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application The current instance of BB application
     */
    public function __construct(BBApplication $application = null)
    {
        $this->_application = $application;

        if (NULL !== $application) {
            if (NULL !== $eventsConfig = $this->_application->getConfig()->getEventsConfig()) {
                $this->addListeners($eventsConfig);
            }
        }
    }

    /**
     * Add listener to events
     * @param array $eventsConfig
     */
    public function addListeners(array $eventsConfig)
    {
        foreach($eventsConfig as $name => $listeners) {
            if (FALSE === array_key_exists('listeners', $listeners)) {
                $this->_application->warning(sprintf('None listener found for `%s` event.', $name));
                continue;
            }

            $listeners['listeners'] = (array) $listeners['listeners'];
            foreach($listeners['listeners'] as $listener) {
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
    public function triggerEvent($eventName, $entity, $eventArgs = null) {
        if (is_a($entity, 'BackBuilder\ClassContent\AClassContent')) {
            $this->dispatch( strtolower('classcontent.'.$eventName), new Event($entity, $eventArgs) );

            if (get_parent_class($entity) != 'BackBuilder\ClassContent\AClassContent') {
                $this->triggerEvent($eventName, get_parent_class($entity), $eventArgs);
            }
        }

        if (is_object($entity)) {
            $eventName = strtolower(str_replace(NAMESPACE_SEPARATOR ,'.', get_class($entity)).'.'.$eventName);
        } else {
            $eventName = strtolower(str_replace(NAMESPACE_SEPARATOR ,'.', $entity).'.'.$eventName);
        }
        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            $prefix = str_replace(NAMESPACE_SEPARATOR, '.', $this->_application->getEntityManager()->getConfiguration()->getProxyNamespace());
            $prefix .= '.'.$entity::MARKER.'.';
            
            $eventName = str_replace(strtolower($prefix), '', $eventName);
        }
        
        if (0 === strpos($eventName, 'backbuilder.')) $eventName = substr($eventName, 12);
        if (0 === strpos($eventName, 'classcontent.')) $eventName = substr($eventName, 13);
        
        $this->dispatch( $eventName, new Event($entity, $eventArgs) );
//        $this->dispatch($this->getEventNamePrefix($entity).$eventName, new Event($entity, $eventArgs) );

    }

    /**
     * Return the current instance of BBapplication
     * @codeCoverageIgnore
     * @return \Backbuilder\BBApplication
     */
    public function getApplication() {
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
            $eventPrefix = strtolower(str_replace(NAMESPACE_SEPARATOR ,'.', get_class($entity)).'.');
        } else {
            $eventPrefix = strtolower(str_replace(NAMESPACE_SEPARATOR ,'.', $entity).'.');
        }
        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            $prefix = str_replace(NAMESPACE_SEPARATOR, '.', $this->_application->getEntityManager()->getConfiguration()->getProxyNamespace());
            $prefix .= '.'.$entity::MARKER.'.';

            $eventPrefix = str_replace(strtolower($prefix), '', $eventPrefix);
        }

        if (0 === strpos($eventPrefix, 'backbuilder.')) $eventPrefix = substr($eventPrefix, 12);
        if (0 === strpos($eventPrefix, 'classcontent.')) $eventPrefix = substr($eventPrefix, 13);

        return $eventPrefix;
    }
}