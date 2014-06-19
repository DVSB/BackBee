<?php

namespace BackBuilder\DependencyInjection\Listener;

use BackBuilder\DependencyInjection\Container,
    BackBuilder\Event\Event,
    BackBuilder\Exception\BBException;

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

        if (true === $container->getParameter('container.do_compile') && false === $application->isDebugMode()) {
            self::loadExternalBundleServices($container, $application->getConfig()->getSection('external_bundles'));

            $container_class = $container->getParameter('container.class');
            $container_file = $container->getParameter('container.file');
            $container_dir = $container->getParameter('container.dir');

            if (false === file_exists($container_dir)) {
                @mkdir($container_dir, 0755);
            }

            if (true === is_writable($container_dir)) {
                $dump = new \Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
                file_put_contents(
                    $container_dir . DIRECTORY_SEPARATOR . $container_file,
                    $dump->dump(array(
                        'class'      => $container_class,
                        'base_class' => '\BackBuilder\DependencyInjection\Container'
                    ))
                );
            }
        }

        $container->compile();
    }

    /**
     * [loadExternalBundleServices description]
     * @param  Container $container [description]
     * @param  [type]    $config    [description]
     * @return [type]               [description]
     */
    private static function loadExternalBundleServices(Container $container, array $config = null)
    {
        if (null !== $config) {
            // Load external bundle services (Symfony2 Bundle)
            if (0 < count($config)) {
                foreach ($config as $key => $datas) {
                    $bundle = new $datas['class']();
                    if (false === ($bundle instanceof ExtensionInterface)) {
                        $errorMsg = sprintf(
                            'ContainerListener failed to load extension %s, it must implements `%s`',
                            $datas['class'],
                            'Symfony\Component\DependencyInjection\Extension\ExtensionInterface'
                        );

                        $container->get('logging')->debug($errorMsg);

                        throw new BBException($errorMsg);
                    }

                    $settings = true === isset($datas['config'])
                        ? array($key => $datas['config'])
                        : array();

                    $bundle->load($settings, $container);
                }
            }
        }
    }
}