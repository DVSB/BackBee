<?php
namespace BackBuilder\Event\Listener;

use BackBuilder\BBApplication,
    BackBuilder\Event\Event;

use Doctrine\ORM\Events;

/**
 * Listener of Doctrine events
 * The EntityManager and UnitOfWork trigger a bunch of events during the life-time of their registered entities.
 *   - preRemove: The preRemove event occurs for a given entity before the respective EntityManager remove
 *                operation for that entity is executed. It is not called for a DQL DELETE statement.
 *   - postRemove: The postRemove event occurs for an entity after the entity has been deleted. It will be
 *                 invoked after the database delete operations. It is not called for a DQL DELETE statement.
 *   - prePersist: The prePersist event occurs for a given entity before the respective EntityManager persist
 *                 operation for that entity is executed.
 *   - postPersist: The postPersist event occurs for an entity after the entity has been made persistent. It
 *                  will be invoked after the database insert operations. Generated primary key values are
 *                  available in the postPersist event.
 *   - preUpdate: The preUpdate event occurs before the database update operations to entity data. It is not
 *                called for a DQL UPDATE statement.
 *   - postUpdate: The postUpdate event occurs after the database update operations to entity data. It is not
 *                 called for a DQL UPDATE statement.
 *   - postLoad: The postLoad event occurs for an entity after the entity has been loaded into the current
 *               EntityManager from the database or after the refresh operation has been applied to it.
 *   - loadClassMetadata: The loadClassMetadata event occurs after the mapping metadata for a class has been
 *                        loaded from a mapping source (annotations/xml/yaml).
 *   - onFlush: The onFlush event occurs after the change-sets of all managed entities are computed. This event
 *              is not a lifecycle callback.
 *   - onClear: The onClear event occurs when the EntityManager#clear() operation is invoked, after all references
 *              to entities have been removed from the unit of work.
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event\Listener
 * @copyright   Lp system
 * @author      c.rouillon
 * @see         http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html
 */
class DoctrineListener {
    /**
     * Current BackBuilder application
     * @var BackBuilder\BBApplication
     */
    private $_application;
    
    /**
     * Class constructor
     * @access public
     * @param BBApplication $application The current instance of BB application
     */
    public function __construct(BBApplication $application = null) {
        $this->_application = $application;
    }
    
    /**
     * Trigger a BackBuilder\Event\Event depending on the entity and event name
     * @access protected
     * @param string    $eventName The doctrine event name
     * @param Object    $entity    The entity instance
     * @param EventArgs $eventArgs The doctrine event arguments
     */
    protected function _triggerEvent($eventName, $entity, $eventArgs) {
        if (NULL === $this->_application) return;
        
        $dispatcher = $this->_application->getEventDispatcher();
        if (NULL != $dispatcher) $dispatcher->triggerEvent($eventName, $entity, $eventArgs);
    }
    
    /**
     * Occur after the mapping metadata for a class has been loaded from a mapping source
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function loadClassMetadata($eventArgs) { }
    
    /**
     * Occur when the EntityManager#clear() operation is invoked
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function onClear($eventArgs) { }
    
    /**
     * Occur on preFlush events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function preFlush($eventArgs) {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->_triggerEvent(Events::preFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $this->_triggerEvent(Events::preFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityDeletions() AS $entity) {
            $this->_triggerEvent(Events::preFlush, $entity, $eventArgs);
        }
    }
    
    /**
     * Occur on onFlush events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function onFlush($eventArgs) {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->_triggerEvent(Events::onFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $this->_triggerEvent(Events::onFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityDeletions() AS $entity) {
            $this->_triggerEvent(Events::onFlush, $entity, $eventArgs);
        }
    }
    
    /**
     * Occur on postFlush events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function postFlush($eventArgs) {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->_triggerEvent(Events::postFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $this->_triggerEvent(Events::postFlush, $entity, $eventArgs);
        }

        foreach ($uow->getScheduledEntityDeletions() AS $entity) {
            $this->_triggerEvent(Events::postFlush, $entity, $eventArgs);
        }
    }
    
    /**
     * Occur on postLoad events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function postLoad($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::postLoad, $eventArgs->getEntity(), $eventArgs);
        
        if (is_a($eventArgs->getEntity(), 'BackBuilder\ClassContent\AClassContent')) {
            $eventArgs->getEntity()->postLoad();
        }
    }
    
    /**
     * Occur on postPersist events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function postPersist($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::postPersist, $eventArgs->getEntity(), $eventArgs);
    }
    
    /**
     * Occur on preRemove events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function preRemove($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::preRemove, $eventArgs->getEntity(), $eventArgs);
    }
    
    /**
     * Occur on postRemove events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function postRemove($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::postRemove, $eventArgs->getEntity(), $eventArgs);
    }
    
    /**
     * Occur on postUpdate events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function postUpdate($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::postUpdate, $eventArgs->getEntity(), $eventArgs);
    }
    
    /**
     * Occur on prePersist events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function prePersist($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::prePersist, $eventArgs->getEntity(), $eventArgs);
    }
    
    /**
     * Occur on preUpdate events
     * @access public
     * @param Doctrine\Common\EventArgs $eventArgs
     */
    public function preUpdate($eventArgs) {
        if (method_exists($eventArgs, 'getEntity'))
            $this->_triggerEvent(Events::preUpdate, $eventArgs->getEntity(), $eventArgs);
    }
}