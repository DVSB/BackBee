<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\Tests\Mock\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use BackBee\Event\Dispatcher;

/**
 * @category    BackBee
 * @package     BackBee\Tests\Mock\EventDispatcher
 * @copyright   Lp system
 * @author      k.golovin
 */
class MockNoopEventDispatcher extends Dispatcher
{
    /**
     * Noop event dispatcher
     * @see EventDispatcherInterface::dispatch
     *
     * @api
     */
    public function dispatch($eventName, Event $event = null)
    {
        return $event;
    }
}
