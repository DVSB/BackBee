<?php
namespace BackBuilder\DependencyInjection\Listener;

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

use BackBuilder\DependencyInjection\Container;
use BackBuilder\DependencyInjection\Dumper\PhpArrayDumper;
use BackBuilder\DependencyInjection\Exception\CannotCreateContainerDirectoryException;
use BackBuilder\DependencyInjection\Exception\ContainerDirectoryNotWritableException;
use BackBuilder\DependencyInjection\Loader\ContainerProxy;
use BackBuilder\Event\Event;
use BackBuilder\Exception\BBException;

/**
 *
 *
 * @category    BackBuilder
 * @package     BackBuilder\DependencyInjection
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class ContainerListener
{
    /**
     * [onApplicationInit description]
     * @param  Event  $event [description]
     * @return [type]        [description]
     */
    public static function onApplicationInit(Event $event)
    {
        $application = $event->getTarget();
        $container = $application->getContainer();

        if (false === $application->isDebugMode()) {
            if (false === ($container instanceof ContainerProxy)) {
                $container_filename = $container->getParameter('container.filename');
                $container_directory = $container->getParameter('container.dump_directory');

                if (false === is_dir($container_directory) && false === @mkdir($container_directory, 0755)) {
                    throw new CannotCreateContainerDirectoryException($container_directory);
                }

                if (false === is_writable($container_directory)) {
                    throw new ContainerDirectoryNotWritableException($container_directory);
                }

                $dumper = new PhpArrayDumper($container);
                file_put_contents(
                    $container_directory . DIRECTORY_SEPARATOR . $container_filename,
                    $dumper->dump(array(
                        'do_compile' => true
                    ))
                );
            } elseif (false === $container->isCompiled()) {
                $container->compile();
            }
        } else {
            $container->compile();
        }
    }

    /**
     * [loadExternalBundleServices description]
     * @param  Container $container [description]
     * @param  [type]    $config    [description]
     * @return [type]               [description]
     */
    // private static function loadExternalBundleServices(Container $container, array $config = null)
    // {
    //     if (null !== $config) {
    //         // Load external bundle services (Symfony2 Bundle)
    //         if (0 < count($config)) {
    //             foreach ($config as $key => $datas) {
    //                 $bundle = new $datas['class']();
    //                 if (false === ($bundle instanceof ExtensionInterface)) {
    //                     $errorMsg = sprintf(
    //                         'ContainerListener failed to load extension %s, it must implements `%s`',
    //                         $datas['class'],
    //                         'Symfony\Component\DependencyInjection\Extension\ExtensionInterface'
    //                     );

    //                     $container->get('logging')->debug($errorMsg);

    //                     throw new BBException($errorMsg);
    //                 }

    //                 $settings = true === isset($datas['config'])
    //                     ? array($key => $datas['config'])
    //                     : array()
    //                 ;

    //                 $bundle->load($settings, $container);
    //             }
    //         }
    //     }
    // }
}
