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

        if (false === $application->isDebugMode()) {
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

// $starttime = microtime(true);
// self::dumpContainer($container);
// echo (microtime(true) - $starttime) .'s';
// die;


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

    private static function dumpContainer($container)
    {
        $container_dumper = array(
            'parameters' => array(),
            'services'   => array()
        );
        foreach ($container->getDefinitions() as $key => $definition) {
            $definition_array = array();

            if (true === $definition->isSynthetic()) {
                $definition_array['synthetic'] = true;
            } else {
                $definition_array['class'] = $definition->getClass();
                foreach ($definition->getArguments() as $arg) {
                    if (is_object($arg) && is_a($arg, 'Symfony\Component\DependencyInjection\Reference')) {
                        $definition_array['arguments'][] = '@' . $arg->__toString();
                    } else {
                        $definition_array['arguments'][] = $arg;
                    }
                }

                foreach ($definition->getTags() as $key => $tag) {
                    $definition_tag = array(
                        'name' => $key
                    );

                    foreach (array_shift($tag) as $key => $option) {
                        $definition_tag[$key] = $option;
                    }

                    $definition_array['tags'][] = $definition_tag;
                }

                foreach ($definition->getMethodCalls() as $method_to_call) {
                    $method_array = array();
                    $method_name = array_shift($method_to_call);
                    $method_array[] = $method_name;
                    $method_args = array();
                    foreach (array_shift($method_to_call) as $arg) {
                        if (true === is_object($arg)) {
                            $method_args[] = '@' . $arg->__toString();
                        } else {
                            $method_args[] = $arg;
                        }
                    }

                    $method_array[] = $method_args;
                    $definition_array['calls'][] = $method_array;
                }
            }

            $container_dumper['services'][$key] = $definition_array;
        }

        foreach ($container->getParameterBag()->all() as $key => $value) {
            $container_dumper['parameters'][$key] = $value;
        }

        file_put_contents('/var/www/backbee/container/services.yml', \Symfony\Component\Yaml\Yaml::dump($container_dumper));
    }
}