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

namespace BackBuilder;

use Exception;
use BackBuilder\AutoLoader\AutoLoader,
    BackBuilder\Config\Config,
    BackBuilder\Event\Listener\DoctrineListener,
    BackBuilder\Exception\BBException,
    BackBuilder\Exception\DatabaseConnectionException,
    BackBuilder\Site\Site,
    BackBuilder\Theme\Theme,
    BackBuilder\Util\File;
use Doctrine\Common\EventManager,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;
use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Extension\ExtensionInterface,
    Symfony\Component\DependencyInjection\Loader\YamlFileLoader,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\HttpFoundation\Session\Session;

/**
 * The main BackBuilder5 application
 *
 * @category    BackBuilder
 * @package     BackBuilder
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBApplication
{

    const VERSION = '0.8.0';

    /**
     * @var Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $_container;
    private $_context;
    private $_debug;
    private $_isinitialized;
    private $_isstarted;
    private $_autoloader;
    private $_bbdir;
    private $_cachedir;
    private $_mediadir;
    private $_repository;
    private $_base_repository;
    private $_resourcedir;
    private $_starttime;
    private $_storagedir;
    private $_tmpdir;
    private $_bundles;
    private $_classcontentdir;
    private $_theme;
    private $_overwrite_config;

    public function __call($method, $args)
    {
        if ($this->getContainer()->has('logging')) {
            call_user_func_array(array($this->getContainer()->get('logging'), $method), $args);
        }
    }

    /**
     * @param string $context
     * @param true $debug
     * @param true $overwrite_config set true if you need overide base config with the context config
     */
    public function __construct($context = null, $debug = false, $overwrite_config = false)
    {
        $this->_starttime = time();
        $this->_context = (null === $context) ? 'default' : $context;
        $this->_debug = (Boolean) $debug;
        $this->_isinitialized = false;
        $this->_isstarted = false;
        $this->_overwrite_config = $overwrite_config;

        $this->_initContainer()
                ->_initContextConfig()
                ->_initAutoloader()
                ->_initContentWrapper()
                ->_initBundles();

        // Force container to create SecurityContext object to activate listener
        $this->getSecurityContext();

        if (null !== $encoding = $this->getConfig()->getEncodingConfig()) {
            if (array_key_exists('locale', $encoding))
                setLocale(LC_ALL, $encoding['locale']);
        }
        $this->debug(sprintf('BBApplication (v.%s) initialization with context `%s`, debugging set to %s', self::VERSION, $this->_context, var_export($this->_debug, true)));
        $this->debug(sprintf('  - Base directory set to `%s`', $this->getBaseDir()));
        $this->debug(sprintf('  - Repository directory set to `%s`', $this->getRepository()));

        $this->_compileContainer();
        $this->_isinitialized = true;
    }

    public function runImport()
    {
        if (null !== $bundles = $this->getConfig()->getSection('importbundles')) {
            foreach ($bundles as $classname) {
                new $classname($this);
            }
        }
    }

    public function __destruct()
    {
        if ($this->_isstarted) {
            $this->info('BackBuilder application ended');
        }
    }

    private function _initContainer()
    {
        // Construct service container
        $this->_container = new ContainerBuilder();

        $dir_to_looking_for = array();
        $dir_to_looking_for[] = $this->getBBDir() . DIRECTORY_SEPARATOR . 'Config';
        $dir_to_looking_for[] = $this->getBaseRepository() . DIRECTORY_SEPARATOR . 'Config';
        $dir_to_looking_for[] = $this->getRepository() . DIRECTORY_SEPARATOR . 'Config';

        foreach ($dir_to_looking_for as $dir) {
            if (true === is_readable($dir . DIRECTORY_SEPARATOR . 'services.yml')) {
                // Define where to looking for services.yml
                $loader = new YamlFileLoader($this->_container, new FileLocator(array($dir)));
                // Load every services definitions into our container
                $loader->load('services.yml');
            }
        }

        // Add current BBApplication into container
        $this->_container->set('bbapp', $this);

        $this->_initBBAppParamsIntoContainer();

        $this->_initExternalBundleServices();

        return $this;
    }

    private function _initBBAppParamsIntoContainer()
    {
        // Set every bbapp parameters
        $this->_container->setParameter('bbapp.context', $this->getContext());
        $this->_container->setParameter('bbapp.cache.dir', $this->getCacheDir());
        $this->_container->setParameter('bbapp.config.dir', $this->getConfigDir());
        //$this->_container->setParameter('bbapp.cachecontrol.class', $this->getCacheProvider());
    }

    private function _initExternalBundleServices()
    {
        // Load external bundle services (Symfony2 Bundle)
        $externalServices = $this->getConfig()->getSection('external_bundles');
        if (null !== $externalServices && 0 < count($externalServices)) {
            foreach ($externalServices as $key => $datas) {
                $bundle = new $datas['class']();
                if (false === ($bundle instanceof ExtensionInterface)) {
                    $errorMsg = sprintf(
                            'BBApplication::_initContainer(): failed to load extension %s, it must implements `%s`', $datas['class'], 'Symfony\Component\DependencyInjection\Extension\ExtensionInterface'
                    );
                    $this->debug($errorMsg);

                    throw new BBException($errorMsg);
                }

                $config = true === isset($datas['config']) ? array($key => $datas['config']) : array();
                $bundle->load($config, $this->_container);
            }
        }
    }

    private function _compileContainer()
    {
        // Compile container
        $this->_container->compile();
        // Create new one
        $newContainer = new ContainerBuilder();
        // Transfert every existing services from old to new container
        foreach ($this->_container->getServiceIds() as $id) {
            $newContainer->set($id, $this->_container->get($id));
        }

        // Replace old container by new one
        $this->_container = $newContainer;
    }

    /**
     * @return \BackBuilder\BBApplication
     */
    private function _initAutoloader()
    {
        $this->getAutoloader()
                ->register()
                ->registerNamespace('BackBuilder\Bundle', implode(DIRECTORY_SEPARATOR, array($this->getBaseDir(), 'bundle')))
                ->registerNamespace('BackBuilder\ClassContent\Repository', implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'ClassContent', 'Repositories')))
                ->registerNamespace('BackBuilder\Renderer\Helper', implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'Templates', 'helpers')))
                ->registerNamespace('BackBuilder\Event\Listener', implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'Listeners')))
                ->registerNamespace('BackBuilder\Services\Public', implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'Services', 'Public')))
                ->setEventDispatcher($this->getEventDispatcher())
                ->setLogger($this->getLogging());

        return $this;
    }

    /**
     * Returns the associated theme
     * @param boolean $force_reload Force to reload the theme if true
     * @return \BackBuilder\Theme\Theme
     */
    public function getTheme($force_reload = false)
    {
        if (false === is_object($this->_theme) || true === $force_reload) {
            $this->_theme = new Theme($this);
        }
        return $this->_theme;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer()
    {
        if (false === $this->getContainer()->has('mailer')) {
            if (null !== $mailer_config = $this->getConfig()->getSection('mailer')) {
                $smtp = (is_array($mailer_config['smtp'])) ? reset($mailer_config['smtp']) : $mailer_config['smtp'];
                $port = (is_array($mailer_config['port'])) ? reset($mailer_config['port']) : $mailer_config['port'];

                $transport = \Swift_SmtpTransport::newInstance($smtp, $port);
                if (array_key_exists('username', $mailer_config) && array_key_exists('password', $mailer_config)) {
                    $username = (is_array($mailer_config['username'])) ? reset($mailer_config['username']) : $mailer_config['username'];
                    $password = (is_array($mailer_config['password'])) ? reset($mailer_config['password']) : $mailer_config['password'];

                    $transport->setUsername($username)->setPassword($password);
                }

                $this->getContainer()->set('mailer', \Swift_Mailer::newInstance($transport));
            }
        }

        return $this->getContainer()->get('mailer');
    }

    /**
     * @return boolean
     */
    public function isDebugMode()
    {
        return (bool) $this->_debug;
    }

    /**
     * @param string $configdir
     * @return \BackBuilder\BBApplication
     */
    private function _initContextConfig()
    {
        if (NULL !== $this->_context && 'default' != $this->_context) {
            $this->getContainer()->get('config')->extend($this->getRepository(), $this->_overwrite_config);
        }
        return $this;
    }

    /**
     * @return \BackBuilder\BBApplication
     * @throws BBException
     */
    private function _initContentWrapper()
    {
        if (null === $contentwrapperConfig = $this->getConfig()->getContentwrapperConfig())
            throw new BBException('None class content wrapper found');

        $namespace = isset($contentwrapperConfig['namespace']) ? $contentwrapperConfig['namespace'] : '';
        $protocol = isset($contentwrapperConfig['protocol']) ? $contentwrapperConfig['protocol'] : '';
        $adapter = isset($contentwrapperConfig['adapter']) ? $contentwrapperConfig['adapter'] : '';

        $this->getAutoloader()->registerStreamWrapper($namespace, $protocol, $adapter);

        return $this;
    }

    /**
     * @return \BackBuilder\BBApplication
     * @throws BBException
     */
    private function _initEntityManager()
    {
        if (null === $doctrineConfig = $this->getConfig()->getDoctrineConfig())
            throw new BBException('None database configuration found');

        // New database configuration
        $config = new Configuration;
        $driverImpl = $config->newDefaultAnnotationDriver();
        $config->setMetadataDriverImpl($driverImpl);

        $proxiesPath = $this->getCacheDir() . DIRECTORY_SEPARATOR . 'Proxies';
        $config->setProxyDir($proxiesPath);
        $config->setProxyNamespace('Proxies');

        $config->setSQLLogger($this->getLogging());

        // Init ORM event
        $evm = new EventManager();
        $r = new \ReflectionClass('Doctrine\ORM\Events');
        $evm->addEventListener($r->getConstants(), new DoctrineListener($this));

        // Create EntityManager
        $connectionOptions = isset($doctrineConfig['dbal']) ? $doctrineConfig['dbal'] : array();
        $this->getContainer()->set('em', EntityManager::create($connectionOptions, $config, $evm));

        try {
            $this->getContainer()->get('em')->getConnection()->connect();
        } catch (\Exception $e) {
            throw new DatabaseConnectionException('Enable to connect to the database.', 0, $e);
        }

        if (isset($doctrineConfig['dbal']) && isset($doctrineConfig['dbal']['charset'])) {
            try {
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_client = "' . addslashes($doctrineConfig['dbal']['charset']) . '";');
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_connection = "' . addslashes($doctrineConfig['dbal']['charset']) . '";');
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_results = "' . addslashes($doctrineConfig['dbal']['charset']) . '";');
            } catch (\Exception $e) {
                throw new BBException(sprintf('Invalid database character set `%s`', $doctrineConfig['dbal']['charset']), BBException::INVALID_ARGUMENT, $e);
            }
        }

        if (isset($doctrineConfig['dbal']) && isset($doctrineConfig['dbal']['collation'])) {
            try {
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION collation_connection = "' . addslashes($doctrineConfig['dbal']['collation']) . '";');
            } catch (\Exception $e) {
                throw new BBException(sprintf('Invalid database collation `%s`', $doctrineConfig['dbal']['collation']), BBException::INVALID_ARGUMENT, $e);
            }
        }

        $this->debug(sprintf('%s(): Doctrine EntityManager initialized', __METHOD__));

        return $this;
    }

    private function _initBundles()
    {
        if (null === $this->_bundles)
            $this->_bundles = array();

        if (null !== $bundles = $this->getConfig()->getBundlesConfig()) {
            foreach ($bundles as $name => $classname) {
                $bundle = new $classname($this);
                if ($bundle->init()) {
                    $this->getContainer()->set('bundle.' . $bundle->getId(), $bundle);
                    $this->_bundles['bundle.' . $bundle->getId()] = $bundle;
                }
            }
        }

        $this->initBundlesServices();
    }

    /**
     * Load every service definition defined in bundle
     */
    private function initBundlesServices()
    {
        foreach ($this->_bundles as $b) {
            $xml = $b->getResourcesDir() . DIRECTORY_SEPARATOR . 'services.xml';
            if (true === is_file($xml)) {
                $loader = new XmlFileLoader($this->_container, new FileLocator(array($b->getResourcesDir())));
                try {
                    $loader->load('services.xml');
                } catch (Exception $e) { /* nothing to do, just ignore it */
                }

                unset($loader);
            }
        }
    }

    /**
     * @param type $name
     * @return Bundle\ABundle
     */
    public function getBundle($name)
    {
        $bundle = null;
        if ($this->getContainer()->has('bundle.' . $name)) {
            $bundle = $this->getContainer()->get('bundle.' . $name);
        }

        return $bundle;
    }

    public function getBundles()
    {
        return $this->_bundles;
    }

    /**
     * @return boolean
     */
    public function debugMode()
    {
        return $this->_debug;
    }

    /**
     * @param \BackBuilder\Site\Site $site
     */
    public function start(Site $site = null)
    {
        if (null === $site) {
            $site = $this->getEntityManager()->getRepository('BackBuilder\Site\Site')->findOneBy(array());
        }

        if (null !== $site) {
            $this->getContainer()->set('site', $site);
        }

        $this->_isstarted = true;
        $this->info(sprintf('BackBuilder application started (Site Uid: %s)', (null !== $site) ? $site->getUid() : 'none'));

        if (null !== $this->_bundles) {
            foreach ($this->_bundles as $bundle)
                $bundle->start();
        }

        $this->getTheme()->init();

        $this->getController()->handle();
    }

    /**
     * @return BackBuilder\FrontController\FrontController
     */
    public function getController()
    {
        return $this->getContainer()->get('controller');
    }

    /**
     * @return AutoLoader
     */
    public function getAutoloader()
    {
        if (null === $this->_autoloader) {
            $this->_autoloader = new AutoLoader($this);
        }

        return $this->_autoloader;
    }

    public function getBBDir()
    {
        if (null === $this->_bbdir) {
            $r = new \ReflectionObject($this);
            $this->_bbdir = dirname($r->getFileName());
        }

        return $this->_bbdir;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return dirname($this->getBBDir());
    }

    public function getContext()
    {
        return $this->_context;
    }

    /**
     * @return BackBuilder\Security\Token\BBUserToken|null
     */
    public function getBBUserToken()
    {
        $token = null;

        if ($this->getContainer()->has('bb_session')) {
            if (null !== $token = $this->getContainer()->get('bb_session')->get('_security_bb_area')) {
                $token = unserialize($token);

                if (!is_a($token, 'BackBuilder\Security\Token\BBUserToken')) {
                    $token = null;
                }
            }
        }

        return $token;
    }

    /**
     * Get cache provider from config
     * @return string Cache provider config name or \BackBuilder\Cache\DAO\Cache if not found
     */
    public function getCacheProvider()
    {
        $conf = $this->getConfig()->getCacheConfig();
        $defaultClass = '\BackBuilder\Cache\DAO\Cache';
        $parentClass = '\BackBuilder\Cache\AExtendedCache';
        return (isset($conf['provider']) && is_subclass_of($conf['provider'], $parentClass) ? $conf['provider'] : $defaultClass);
    }

    /**
     * @return \BackBuilder\Cache\DAO\Cache
     */
    public function getCacheControl()
    {
        return $this->getContainer()->get('cache-control');
    }

    /**
     *
     * @return \BackBuilder\Cache\ACache
     */
    public function getBootstrapCache()
    {
        return $this->getContainer()->get('cache.bootstrap');
    }

    public function getCacheDir()
    {
        if (null === $this->_cachedir) {
            $this->_cachedir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'cache';
        }
        return $this->_cachedir;
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    public function getConfigDir()
    {
        return $this->getBaseRepository() . DIRECTORY_SEPARATOR . 'Config';
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->getContainer()->has('em')) {
            $this->_initEntityManager();
        }

        return $this->getContainer()->get('em');
    }

    /**
     * @return Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->getContainer()->get('ed');
    }

    /**
     * @return Logger
     */
    public function getLogging()
    {
        return $this->getContainer()->get('logging');
    }

    public function getMediaDir()
    {
        if (null === $this->_mediadir) {
            $this->_mediadir = implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'Data', 'Media'));
        }

        return $this->_mediadir;
    }

    /**
     * @return Renderer\ARenderer
     */
    public function getRenderer()
    {
        return $this->getContainer()->get('renderer');
    }

    public function getRepository()
    {
        if (null === $this->_repository) {
            $this->_repository = $this->getBaseRepository();
            if (null !== $this->_context && 'default' != $this->_context) {
                $this->_repository .= DIRECTORY_SEPARATOR . $this->_context;
            }
        }

        return $this->_repository;
    }

    public function getBaseRepository()
    {
        if (NULL === $this->_base_repository) {
            $this->_base_repository = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'repository';
        }
        return $this->_base_repository;
    }

    /**
     * Return the classcontent repositories path for this instance
     * @return array
     */
    public function getClassContentDir()
    {
        if (null === $this->_classcontentdir) {
            $this->_classcontentdir = array();

            array_unshift($this->_classcontentdir, $this->getBaseDir() . '/BackBuilder/ClassContent');
            array_unshift($this->_classcontentdir, $this->getBaseDir() . '/repository/ClassContent');

            if (null !== $this->_context && 'default' != $this->_context) {
                array_unshift($this->_classcontentdir, $this->getRepository() . '/ClassContent');
            }

            array_walk($this->_classcontentdir, array('BackBuilder\Util\File', 'resolveFilepath'));
        }

        return $this->_classcontentdir;
    }

    /**
     * Push one directory at the end of classcontent dirs
     * @param string $dir
     * @return \BackBuilder\BBApplication
     */
    public function pushClassContentDir($dir)
    {
        File::resolveFilepath($dir);

        $classcontentdir = $this->getClassContentDir();
        array_push($classcontentdir, $dir);

        $this->_classcontentdir = $classcontentdir;

        return $this;
    }

    /**
     * Prepend one directory at the beginning of classcontent dirs
     * @param type $dir
     * @return \BackBuilder\BBApplication
     */
    public function unshiftClassContentDir($dir)
    {
        File::resolveFilepath($dir);

        $classcontentdir = $this->getClassContentDir();
        array_unshift($classcontentdir, $dir);

        $this->_classcontentdir = $classcontentdir;

        return $this;
    }

    /**
     * Return the resource directories, if undefined, initialized with common resources
     * @return array The resource directories
     */
    public function getResourceDir()
    {
        if (null === $this->_resourcedir) {
            $this->_resourcedir = array();

            $this->addResourceDir($this->getBaseDir() . '/BackBuilder/Resources')
                    ->addResourceDir($this->getBaseDir() . '/repository/Ressources');

            if (null !== $this->_context && 'default' != $this->_context) {
                $this->addResourceDir($this->getRepository() . '/Ressources');
            }

            array_walk($this->_resourcedir, array('BackBuilder\Util\File', 'resolveFilepath'));
        }

        return $this->_resourcedir;
    }

    /**
     * Push one directory at the end of resources dirs
     * @param string $dir
     * @return \BackBuilder\BBApplication
     */
    public function pushResourceDir($dir)
    {
        File::resolveFilepath($dir);

        $resourcedir = $this->getResourceDir();
        array_push($resourcedir, $dir);

        $this->_resourcedir = $resourcedir;

        return $this;
    }

    /**
     * Prepend one directory at the begining of resources dirs
     * @param type $dir
     * @return \BackBuilder\BBApplication
     */
    public function unshiftResourceDir($dir)
    {
        File::resolveFilepath($dir);

        $resourcedir = $this->getResourceDir();
        array_unshift($resourcedir, $dir);

        $this->_resourcedir = $resourcedir;

        return $this;
    }

    /**
     * Prepend one directory of resources
     * @param String $dir The new resource directory to add
     * @return \BackBuilder\BBApplication The current BBApplication
     * @throws BBException Occur on invalid path or invalid resource directories
     */
    public function addResourceDir($dir)
    {
        if (null === $this->_resourcedir) {
            $this->_resourcedir = array();
        }

        if (false === is_array($this->_resourcedir)) {
            throw new BBException('Misconfiguration of the BBApplication : resource dir has to be an array', BBException::INVALID_ARGUMENT);
        }

        if (false === file_exists($dir) || false === is_dir($dir)) {
            throw new BBException(sprintf('The resource folder `%s` does not exist or is not a directory', $dir), BBException::INVALID_ARGUMENT);
        }

        array_unshift($this->_resourcedir, $dir);

        return $this;
    }

    /**
     * Return the current resource dir (ie the first one in those defined)
     * @return string the file path of the current resource dir
     * @throws BBException Occur when none resource dir is defined
     */
    public function getCurrentResourceDir()
    {
        $dir = $this->getResourceDir();

        if (0 == count($dir)) {
            throw new BBException('Misconfiguration of the BBApplication : none resource dir defined', BBException::INVALID_ARGUMENT);
        }

        return array_shift($dir);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     * @throws BBException
     */
    public function getRequest()
    {
        if (false === $this->isStarted())
            throw new BBException('The BackBuilder application has to be started before to access request');

        return $this->getController()->getRequest();
    }

    /**
     * @return BackBuilder\Services\Rpc\JsonRPCServer
     */
    public function getRpcServer()
    {
        return $this->getContainer()->get('rpcserver');
    }

    /**
     * @return BackBuilder\Services\Upload\UploadServer
     */
    public function getUploadServer()
    {
        return $this->getContainer()->get('uploadserver');
    }

    /**
     * @return BackBuilder\Rewriting\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->getContainer()->get('rewriting.urlgenerator');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\SessionInterface|null The session
     */
    public function getSession()
    {
        if (null === $this->getRequest()->getSession()) {
            $session = new Session();
            $session->start();
            $this->getRequest()->setSession($session);
        }
        return $this->getRequest()->getSession();
    }

    /**
     * @return BackBuilder\Security\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->getContainer()->get('security.context');
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        $site = null;
        if (true === $this->getContainer()->has('site')) {
            $site = $this->getContainer()->get('site');
        }

        return $site;
    }

    /**
     * @return string
     */
    public function getStorageDir()
    {
        if (null === $this->_storagedir) {
            $this->_storagedir = $this->getRepository() . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Storage';
        }

        return $this->_storagedir;
    }

    /**
     * @return string
     */
    public function getTemporaryDir()
    {
        if (null === $this->_tmpdir) {
            $this->_tmpdir = $this->getRepository() . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Tmp';
        }

        return $this->_tmpdir;
    }

    /**
     * @return boolean
     */
    public function isReady()
    {
        return ($this->_isinitialized && null !== $this->_container);
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return (true === $this->_isstarted);
    }

}
