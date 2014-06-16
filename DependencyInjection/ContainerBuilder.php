<?php

namespace BackBuilder\DependencyInjection;

use BackBuilder\BBApplication;
use BackBuilder\DependencyInjection\Container;
use BackBuilder\Exception\BBException;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class ContainerBuilder
{
    /**
     * Every time you invoke this method it will return a new BackBuilder\DependencyInjection\Container
     * @return BackBuilder\DependencyInjection\Container
     */
    public static function getContainer(BBApplication $application, $force_reload = false)
    {
        // Construct container
        $container = new Container();

        if (false === $container_dir = getenv('BB_CONTAINERDIR')) {
            $container_dir = $application->getBaseDir() . '/container/';
        }

        $container_class = 'bb' . md5('__container__' . $application->getContext() . $application->getEnvironment());
        $container_file = $container_class . '.php';

        if (
            false === $force_reload
            && true === is_readable($container_dir . DIRECTORY_SEPARATOR . $container_file)
        ) {
            $loader = new PhpFileLoader($container, new FileLocator(array($container_dir)));
            $loader->load($container_file);

            $container = new $container_class();

            // Add current BBApplication into container
            $container->set('bbapp', $application);
            $container->set('service_container', $container);

            $container->get('config')
                      ->setContainer($container)
                      ->setEnvironment($application->getEnvironment())
                      ->extend($container->getParameter('bbapp.config.dir'));

            $container->setDefinition('site', new Definition())->setSynthetic(true);
            $container->setDefinition('routing', new Definition())->setSynthetic(true);
            $container->setDefinition('bb_session', new Definition())->setSynthetic(true);

            if (false === $container->getParameter('debug')) {
                return $container;
            }
        }

        self::loadApplicationServices($application, $container);

        self::initApplicationVarsIntoContainer($application, $container);

        self::loadLogger($container);

        if (false === $container->getParameter('debug')) {
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

        return $container;
    }

    /**
     * [loadServicesFromYamlFile description]
     * @param  Container $container [description]
     * @param  [type]    $dir       [description]
     * @return [type]               [description]
     */
    public static function loadServicesFromYamlFile(Container $container, $dir)
    {
        $loader = new YamlFileLoader($container, new FileLocator(array($dir)));
        $loader->load('services.yml');
    }

    /**
     * [loadServicesFromXmlFile description]
     * @param  Container $container [description]
     * @param  [type]    $dir       [description]
     * @return [type]               [description]
     */
    public static function loadServicesFromXmlFile(Container $container, $dir)
    {
        $loader = new XmlFileLoader($container, new FileLocator(array($dir)));
        $loader->load('services.xml');
    }

    /**
     * [loadApplicationServices description]
     * @param  BBApplication $application [description]
     * @param  Container     $container   [description]
     * @return [type]                     [description]
     */
    private static function loadApplicationServices(BBApplication $application, Container $container)
    {
        $dirToLookingFor = array();
        $dirToLookingFor[] = $application->getBBDir() . DIRECTORY_SEPARATOR . 'Config';
        $dirToLookingFor[] = $application->getBaseRepository() . DIRECTORY_SEPARATOR . 'Config';

        if ($application->getBaseRepository() !== $application->getRepository()) {
            $dirToLookingFor[] = $application->getRepository() . DIRECTORY_SEPARATOR . 'Config';
        }

        if (BBApplication::DEFAULT_ENVIRONMENT !== $application->getEnvironment()) {
            $dirToLookingFor[] = $application->getRepository()
                . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . $application->getEnvironment()
            ;
        }

        // Loop into every directory where we can potentially found a services.yml or services.xml
        foreach ($dirToLookingFor as $dir) {
            if (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.yml')) {
                self::loadServicesFromYamlFile($container, $dir);
            } elseif (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.xml')) {
                self::loadServicesFromXmlFile($container, $dir);
            }
        }
    }

    /**
     * [initApplicationVarsIntoContainer description]
     * @param  BBApplication $application [description]
     * @return [type]                     [description]
     */
    private static function initApplicationVarsIntoContainer(BBApplication $application, Container $container)
    {
        // Add BBApplication to container
        $container->set('bbapp', $application);
        $container->set('service_container', $container);

        // Set application others variables' values

        // define context and environment parameters
        $container->setParameter('environment', $application->getEnvironment());
        $container->setParameter('bbapp.context', $application->getContext());

        // define config and repository directory
        $container->setParameter('bbapp.config.dir', $application->getConfigDir());
        $container->setParameter('bbapp.repository.dir', $application->getRepository());

        // Retrieving config.yml without calling Config services
        $config = self::getRawConfig($application);

        // Set debug into container
        $debug = $application->isDebugMode();
        if (array_key_exists('parameters', $config) && array_key_exists('debug', $config['parameters'])) {
            $debug = $config['parameters']['debug'];
        }

        $container->setParameter('debug', $debug);

        // Set timezone
        if (true === isset($config['date']) && true === isset($config['date']['timezone'])) {
            date_default_timezone_set($config['date']['timezone']);
        }

        // define cache directory
        $cachedir = implode(DIRECTORY_SEPARATOR, array(
            $application->getBaseDir(),
            'cache',
            $application->getEnvironment()
        ));
        if (true === isset($config['parameters']['cache_dir']) && false === empty($config['parameters']['cache_dir'])) {
            $cachedir = $config['parameters']['cache_dir'];
        }

        $container->setParameter('bbapp.cache.dir', $cachedir);

        if (
            true === isset($config['parameters'])
            && true === array_key_exists('cache_auto_generate', $config['parameters'])
        ) {
            $container->setParameter('bbapp.cache.autogenerate', $config['parameters']['cache_auto_generate']);
        }

        // define bb base dir
        $container->setParameter('bbapp.base.dir', $application->getBBDir());

        // define data directory
        $datadir = $application->getRepository() . DIRECTORY_SEPARATOR . 'Data';
        if (true === isset($config['parameters']['data_dir']) && false === empty($config['parameters']['data_dir'])) {
            $datadir = $config['parameters']['data_dir'];
        }

        $container->setParameter('bbapp.data.dir', $datadir);
    }

    /**
     * [loadLogger description]
     * @param  Container $container [description]
     * @return [type]               [description]
     */
    private static function loadLogger(Container $container)
    {
        $logger_class = $container->getParameter('bbapp.logger.class');
        if (true === $container->getParameter('debug')) {
            $logger_class = $container->getParameter('bbapp.logger_debug.class');
        }

        $container->setDefinition('logging', new Definition(
            $logger_class,
            array(new \Symfony\Component\DependencyInjection\Reference('bbapp'))
        ));
    }

    /**
     * [getRawConfig description]
     * @return [type] [description]
     */
    private static function getRawConfig(BBApplication $application)
    {
        $config = array();
        $file_exists = false;
        $filepath = null;

        if (BBApplication::DEFAULT_CONTEXT !== $application->getContext()) {
            if (BBApplication::DEFAULT_ENVIRONMENT !== $application->getEnvironment()) {
                $filepath = $application->getRepository()
                    . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . $application->getEnvironment()
                    . DIRECTORY_SEPARATOR . 'config.yml'
                ;
            }

            if (false === $file_exists = file_exists($filepath)) {
                $filepath = $application->getRepository()
                    . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'config.yml'
                ;
            }
        }

        if (
            (false === $file_exists = file_exists($filepath))
            && BBApplication::DEFAULT_ENVIRONMENT !== $application->getEnvironment()
        ) {
            $filepath = $application->getBaseRepository()
                . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . $application->getEnvironment()
                . DIRECTORY_SEPARATOR . 'config.yml'
            ;
        }

        if (false === $file_exists = file_exists($filepath)) {
            $filepath = $application->getBaseRepository()
                . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . 'config.yml'
            ;
        }

        if (false === $file_exists = file_exists($filepath)) {
            throw new \Exception('Unable to find a config.yml!');
        }

        if (true === is_readable($filepath)) {
            $config = Yaml::parse($filepath);
        } else {
            throw new \Exception("config.yml is not readable! ($filepath)");
        }

        return $config;
    }
}
