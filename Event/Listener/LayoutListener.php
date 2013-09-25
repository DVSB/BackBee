<?php
namespace BackBuilder\Event\Listener;

use BackBuilder\Event\Event;

/**
 * Listener to Layout events :
 *    - site.layout.beforesave: occurs before a layout entity is saved
 *    - site.layout.postremove: occurs after a layout entity has been removed
 *
 * @category    BackBuilder
 * @package     BackBuilder\Event\Listener
 * @copyright   Lp system
 * @author      c.rouillon
 */
class LayoutListener {
    /**
     * Occur on site.layout.beforesave events
     *
     * @access public
     * @param Event $event
     */
    public static function onBeforeSave(Event $event) {
        $layout = $event->getTarget();
        if (!is_a($layout, 'BackBuilder\Site\Layout')) return;
        
        $dispatcher = $event->getDispatcher();
        if (NULL !== $dispatcher->getApplication()) {
            if (is_a($event->getEventArgs(), 'Doctrine\ORM\Event\PreUpdateEventArgs')) {
                if (!$event->getEventArgs()->hasChangedField('_data')) return;
            }
            
            // Update the layout thumbnail - Beware of generate thumbnail before any other operation
            $thumb = $dispatcher->getApplication()->getEntityManager()
                                                  ->getRepository('BackBuilder\Site\Layout')
                                                  ->generateThumbnail($layout, $dispatcher->getApplication());
            
            // Update the layout file
            $dispatcher->getApplication()->getRenderer()->updateLayout($layout);
            
            if (is_a($event->getEventArgs(), 'Doctrine\ORM\Event\PreUpdateEventArgs')) {
                if ($event->getEventArgs()->hasChangedField('_picpath'))
                    $event->getEventArgs()->setNewValue('_picpath', $thumb);
            }
        }
    }
    
    /**
     * Occur on site.layout.postremove events
     *
     * @access public
     * @param Event $event
     */
    public static function onAfterRemove(Event $event) {
        $layout = $event->getTarget();
        if (!is_a($layout, 'BackBuilder\Site\Layout')) return;
        
        $dispatcher = $event->getDispatcher();
        if (NULL !== $dispatcher->getApplication()) {
            $dispatcher->getApplication()->getEntityManager()
                                         ->getRepository('BackBuilder\Site\Layout')
                                         ->removeThumbnail($layout, $dispatcher->getApplication());
        }
        
        $renderer = $dispatcher->getApplication()->getRenderer()->removeLayout($layout);
    }
}