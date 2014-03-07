<?php

namespace BackBuilder\DependencyInjection;

use Exception;

use BackBuilder\BBApplication,
    BackBuilder\DependencyInjection\Container,
    BackBuilder\Exception\BBException;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\Extension\ExtensionInterface,
    Symfony\Component\DependencyInjection\Loader\YamlFileLoader,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\Yaml\Yaml;

/**
 * @author e.chau <eric.chau@lp-digital.fr>
 */
class ContainerBuilder
{
    /**
     * Every time you invoke this method it will return a new BackBuilder\DependencyInjection\Container
     * @return BackBuilder\DependencyInjection\Container
     */
    public static function getContainer(BBApplication $application)
    {
        // Construct container
        $container = new Container();

        $dirToLookingFor = array();
        $dirToLookingFor[] = $application->getBBDir() . DIRECTORY_SEPARATOR . 'Config';
        $dirToLookingFor[] = $application->getBaseRepository() . DIRECTORY_SEPARATOR . 'Config';

        if ($application->getBaseRepository() !== $application->getRepository()) {
            $dirToLookingFor[] = $application->getRepository() . DIRECTORY_SEPARATOR . 'Config';
        }

        // Loop into every directory where we can potentially found a services.yml or services.xml
        foreach ($dirToLookingFor as $dir) {
            if (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.yml')) {
                self::loadServicesFromYamlFile($container, $dir);
            } elseif (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.xml')) {
                self::loadServicesFromXmlFile($container, $dir);
            }
        }

        self::initApplicationVarsIntoContainer($application, $container);

        // register container listener directory namespace
        $container->get('autoloader')->registerNamespace(
            'BackBuilder\Event\Listener',
            implode(DIRECTORY_SEPARATOR, array($application->getBBDir(), 'DependencyInjection', 'Listener'))
        );

        // add ContainerListener event (bbapplication.init)
        /*$container->get('event.dispatcher')->addListeners(array(
            'bbapplication.init' => array(
                'listeners' => array(
                    array(
                        'BackBuilder\Event\Listener\ContainerListener',
                        'onApplicationInit'
                    )
                )
            )
        ));*/

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
     * [initApplicationVarsIntoContainer description]
     * @param  BBApplication $application [description]
     * @return [type]                     [description]
     */
    private static function initApplicationVarsIntoContainer(BBApplication $application, Container $container)
    {
        // Add BBApplication to container
        $container->set('bbapp', $application);

        // Set application others variables' values
        
        // define context
        $container->setParameter('bbapp.context', $application->getContext());

        // define cache directory
        try {
            $cachedir = $container->getParameter('bbapp.cache.dir');
            if (true === empty($cachedir)) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $container->setParameter(
                'bbapp.cache.dir', 
                $application->getBaseDir() . DIRECTORY_SEPARATOR . 'cache'
            );
        }

        // define config directory
        $container->setParameter('bbapp.config.dir', $application->getConfigDir());

        // define repository directory
        $container->setParameter('bbapp.repository.dir', $application->getRepository());

        // define data directory
        try {
            $datadir = $container->getParameter('bbapp.data.dir');
            if (true === empty($datadir)) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $container->setParameter(
                'bbapp.data.dir', 
                $application->getRepository() . DIRECTORY_SEPARATOR . 'Data'
            );
        }

        //$container->setParameter('bbapp.cachecontrol.class', $application->getCacheProvider());
    }
}
