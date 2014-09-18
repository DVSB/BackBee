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

use BackBuilder\Console\Console;
use BackBuilder\DependencyInjection\ContainerBuilder;
use BackBuilder\DependencyInjection\ContainerInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBuilder\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBuilder\Event\Event;
use BackBuilder\Exception\BBException;
use BackBuilder\Exception\UnknownContextException;
use BackBuilder\NestedNode\Repository\NestedNodeRepository;
use BackBuilder\Site\Site;
use BackBuilder\Theme\Theme;
use BackBuilder\Util\File;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

/**
 * The main BackBuilder5 application
 *
 * @category    BackBuilder
 * @package     BackBuilder
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBApplication implements IApplication, DumpableServiceInterface, DumpableServiceProxyInterface
{

    const VERSION = '0.10.0';

    /**
     * application's service container
     *
     * @var BackBuilder\DependencyInjection\ContainerInterface
     */
    private $_container;

    /**
     * application's context
     *
     * @var string
     */
    private $_context;

    /**
     * application's environment
     *
     * @var string
     */
    private $_environment;

    /**
     * define if application is started with debug mode or not
     *
     * @var boolean
     */
    private $_debug;
    private $_isinitialized;
    private $_isstarted;
    private $_bbdir;
    private $_mediadir;
    private $_repository;
    private $_base_repository;
    private $_resourcedir;
    private $_starttime;
    private $_storagedir;
    private $_tmpdir;
    private $_classcontentdir;
    private $_theme;
    private $_overwrite_config;

    /**
     * tell us if application has been restored by container or not
     *
     * @var boolean
     */
    private $_is_restored;

    /**
     * [$dump_datas description]
     *
     * @var array
     */
    private $dump_datas;

    /**
     * @param string $context
     * @param true $debug
     * @param true $overwrite_config set true if you need overide base config with the context config
     */
    public function __construct($context = null, $environment = null, $overwrite_config = false)
    {
        $this->_starttime = time();
        $this->_context = (null === $context) ? self::DEFAULT_CONTEXT : $context;
        $this->_isinitialized = false;
        $this->_isstarted = false;
        $this->_overwrite_config = $overwrite_config;
        $this->_is_restored = false;
        $this->_environment = null !== $environment && true === is_string($environment)
            ? $environment
            : self::DEFAULT_ENVIRONMENT
        ;
        $this->dump_datas = array();

        $this->_initAnnotationReader();
        $this->_initContainer();
        $this->_initEnvVariables();
        $this->_initAutoloader();
        $this->_initContentWrapper();

        try {
            $this->_initEntityManager();
        } catch (\Exception $e) {
            $this->getLogging()->notice('BackBee starting without EntityManager');
        }

        $this->_initBundles();

        if (false === $this->getContainer()->has('em')) {
            $this->debug(sprintf('BBApplication (v.%s) partial initialization with context `%s`, debugging set to %s', self::VERSION, $this->_context, var_export($this->_debug, true)));

            return;
        }

        // Force container to create SecurityContext object to activate listener
        $this->getSecurityContext();

        $this->debug(sprintf('BBApplication (v.%s) initialization with context `%s`, debugging set to %s', self::VERSION, $this->_context, var_export($this->_debug, true)));
        $this->debug(sprintf('  - Base directory set to `%s`', $this->getBaseDir()));
        $this->debug(sprintf('  - Repository directory set to `%s`', $this->getRepository()));

        $this->_isinitialized = true;

        // trigger bbapplication.init
        $this->getEventDispatcher()->dispatch('bbapplication.init', new Event($this));
    }

    /**
     * [__destruct description]
     */
    public function __destruct()
    {
        $this->stop();
    }

    public function __call($method, $args)
    {
        if ($this->getContainer()->has('logging')) {
            call_user_func_array(array($this->getContainer()->get('logging'), $method), $args);
        }
    }

    public function runImport()
    {
        if (null !== $bundles = $this->getConfig()->getSection('importbundles')) {
            foreach ($bundles as $classname) {
                new $classname($this);
            }
        }
    }

    /**
     * Returns the associated theme
     * @param boolean $force_reload Force to reload the theme if true
     * @return \BackBuilder\Theme\Theme
     */
    public function getTheme($force_reload = false)
    {
        if (null === $this->getConfig()->getSection('themes_dir') || null === $this->getConfig()->getThemeConfig()) {
            return null;
        }

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
        if (false === $this->getContainer()->has('mailer') || is_null($this->getContainer()->get('mailer'))) {
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
        $debug = (bool) $this->_debug;
        if (null !== $this->getContainer() && $this->getContainer()->hasParameter('debug')) {
            $debug = $this->getContainer()->getParameter('debug');
        }

        return $debug;
    }

    /**
     * @return boolean
     */
    public function isOverridedConfig()
    {
        return $this->_overwrite_config;
    }

    /**
     * @param type $name
     * @return ABundle
     */
    public function getBundle($name)
    {
        $bundle = null;
        if (true === $this->getContainer()->has('bundle.' . $name)) {
            $bundle = $this->getContainer()->get('bundle.' . $name);
        }

        return $bundle;
    }

    /**
     * returns every registered bundles
     *
     * @return array
     */
    public function getBundles()
    {
        $bundles = array();
        foreach ($this->getContainer()->findTaggedServiceIds('bundle') as $id => $datas) {
            $bundles[] = $this->getContainer()->get($id);
        }

        return $bundles;
    }

    /**
     * @deprecated since version 1.0
     * @uses isDebugMode()
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
            $site = $this->getEntityManager()->getRepository('BackBuilder\Site\Site')->findOneBy(array()); // 40 ms
        }

        if (null !== $site) {
            $this->getContainer()->set('site', $site);
        }

        $this->_isstarted = true;
        $this->info(sprintf('BackBuilder application started (Site Uid: %s)', (null !== $site) ? $site->getUid() : 'none'));

        if (null !== $this->getTheme()) {
            $this->getTheme()->init();
        }

        // trigger bbapplication.start
        $this->getEventDispatcher()->dispatch('bbapplication.start', new Event($this)); // 15 ms

        if (false === $this->isClientSAPI()) {
            $response = $this->getController()->handle(); // 140 ms
            if ($response instanceof Response) {
                $this->getController()->sendResponse($response); // 140 ms
            }
        }
    }

    /**
     * Stop the current BBApplication instance
     */
    public function stop()
    {
        if (true === $this->isStarted()) {
            // trigger bbapplication.stop
            $this->getEventDispatcher()->dispatch('bbapplication.stop', new Event($this));
            $this->info('BackBuilder application ended');
        }
    }

    /**
     * @return \BackBuilder\FrontController\FrontController
     */
    public function getController()
    {
        return $this->getContainer()->get('controller');
    }

    /**
     * @return \BackBuilder\Routing\RouteCollection
     */
    public function getRouting()
    {
        return $this->getContainer()->get('routing');
    }

    /**
     * @return AutoLoader
     */
    public function getAutoloader()
    {
        return $this->getContainer()->get('autoloader');
    }

    /**
     * @return string
     */
    public function getBBDir()
    {
        if (null === $this->_bbdir) {
            $r = new \ReflectionObject($this);
            $this->_bbdir = dirname($r->getFileName());
        }

        return $this->_bbdir;
    }

    /**
     * Returns path to Data directory
     * @return string absolute path to Data directory
     */
    public function getDataDir()
    {
        return $this->_container->getParameter('bbapp.data.dir');
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return dirname($this->getBBDir());
    }

    /**
     * Get vendor dir
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->getBaseDir() . '/vendor';
    }

    /**
     * Returns TRUE if a starting context is defined, FALSE otherwise
     * @return boolean
     */
    public function hasContext()
    {
        return (null !== $this->_context && self::DEFAULT_CONTEXT !== $this->_context);
    }

    /**
     * Returns the starting context
     * @return string|NULL
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * @return BackBuilder\Security\Token\BBUserToken|null
     */
    public function getBBUserToken()
    {
        $token = $this->getSecurityContext()->getToken();
        if ((null === $token || !($token instanceof \BackBuilder\Security\Token\BBUserToken))) {
            if (is_null($this->getContainer()->get('bb_session'))) {
                $token = null;
            } else {
                if (null !== $token = $this->getContainer()->get('bb_session')->get('_security_bb_area')) {
                    $token = unserialize($token);

                    if (!($token instanceof \BackBuilder\Security\Token\BBUserToken)) {
                        $token = null;
                    }
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
        return $this->getContainer()->get('cache.control');
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
        if (null === $this->_container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->getContainer()->getParameter('bbapp.cache.dir');
    }

    /**
     * @return \BackBuilder\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Get validator service
     *
     * @return \Symfony\Component\Validator\ValidatorInterface
     */
    public function getValidator()
    {
        return $this->getContainer()->get('validator');
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        if (null === $this->_container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->_container->get('config');
    }

    /**
     * Get current environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    public function getConfigDir()
    {
        return $this->getRepository() . '/' . 'Config';
    }

    public function getBBConfigDir()
    {
        return $this->getBaseRepository() . '/' . 'Config';
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager($name = 'default')
    {
        try {
            if (null === $this->getContainer()->get('doctrine')) {
                $this->_initEntityManager();
            }

            return $this->getContainer()->get('doctrine')->getManager($name);
        } catch (\Exception $e) {
            $this->getLogging()->notice('BackBee starting without EntityManager');
        }
    }

    /**
     * @return Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->getContainer()->get('event.dispatcher');
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
        if (null === $this->_container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->getContainer()->getParameter('bbapp.media.dir');
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
            if (true === $this->hasContext()) {
                $this->_repository .= '/' . $this->_context;
            }
        }

        return $this->_repository;
    }

    public function getBaseRepository()
    {
        if (null === $this->_base_repository) {
            $this->_base_repository = $this->getBaseDir() . '/' . 'repository';
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

            if (true === $this->hasContext()) {
                array_unshift($this->_classcontentdir, $this->getRepository() . '/ClassContent');
            }

            //array_walk($this->_classcontentdir, array('BackBuilder\Util\File', 'resolveFilepath'));
            array_map(array('BackBuilder\Util\File', 'resolveFilepath'), $this->_classcontentdir);
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

            $this->addResourceDir($this->getBBDir() . '/Resources');

            if (true === is_dir($this->getBaseRepository() . '/Resources')) {
                $this->addResourceDir($this->getBaseRepository() . '/Resources');
            }

            if (true === is_dir($this->getBaseRepository() . '/Ressources')) {
                $this->addResourceDir($this->getBaseRepository() . '/Ressources');
            }

            if (true === $this->hasContext()) {
                if (true === is_dir($this->getRepository() . '/Resources')) {
                    $this->addResourceDir($this->getRepository() . '/Resources');
                }

                if (true === is_dir($this->getRepository() . '/Resources')) {
                    $this->addResourceDir($this->getRepository() . '/Resources');
                }
            }

            //array_walk($this->_resourcedir, array('BackBuilder\Util\File', 'resolveFilepath'));
            array_map(array('BackBuilder\Util\File', 'resolveFilepath'), $this->_resourcedir);
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
        if (false === $this->isStarted()) {
            throw new BBException('The BackBuilder application has to be started before to access request');
        }

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

            if('test' === $this->getEnvironment()) {
                $session = new Session(new MockArraySessionStorage());
            } else {
                $session = new Session();
            }

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
            $this->_storagedir = $this->_container->getParameter('bbapp.data.dir') . '/' . 'Storage';
        }

        return $this->_storagedir;
    }

    /**
     * @return string
     */
    public function getTemporaryDir()
    {
        if (null === $this->_tmpdir) {
            $this->_tmpdir = $this->_container->getParameter('bbapp.data.dir') . '/' . 'Tmp';
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

    public function isClientSAPI()
    {
        return isset($GLOBALS['argv']);
    }

    /**
     * Finds and registers Commands.
     *
     * Override this method if your bundle commands do not follow the conventions:
     *
     * * Commands are in the 'Command' sub-directory
     * * Commands extend Symfony\Component\Console\Command\Command
     *
     * @param BackBuilder\Console\Console $console An Application instance
     */
    public function registerCommands(Console $console)
    {
        if (is_dir($dir = $this->getBBDir() . '/Command')) {
            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($dir);
            $ns = 'BackBuilder\\Command';

            foreach ($finder as $file) {
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\' . strtr($relativePath, '/', '\\');
                }
                $r = new \ReflectionClass($ns . '\\' . $file->getBasename('.php'));
                if ($r->isSubclassOf('BackBuilder\\Console\\ACommand') && !$r->isAbstract() && !$r->getConstructor()->getNumberOfRequiredParameters()) {
                    $console->add($r->newInstance());
                }
            }
        }

        foreach ($this->getBundles() as $bundle) {
            if (!is_dir($dir = $bundle->getBaseDir() . '/Command')) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($dir);
            $ns = $bundle->getNamespace() . '\\Command';

            foreach ($finder as $file) {
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\' . strtr($relativePath, '/', '\\');
                }
                $r = new \ReflectionClass($ns . '\\' . $file->getBasename('.php'));
                if ($r->isSubclassOf('BackBuilder\\Console\\ACommand') && !$r->isAbstract() && 0 === $r->getConstructor()->getNumberOfRequiredParameters()) {
                    $instance = $r->newInstance();
                    $instance->setBundle($bundle);
                    $console->add($instance);
                }
            }
        }
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return null;
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = array())
    {
        return array_merge($this->dump_datas, array(
            'classcontent_directories' => $this->_classcontentdir,
            'resources_directories'    => $this->_resourcedir
        ));
    }

    /**
     * Restore current service to the dump's state
     *
     * @param  array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                     restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->_classcontentdir = $dump['classcontent_directories'];
        $this->_resourcedir = $dump['resources_directories'];

        if (true === isset($dump['date_timezone'])) {
            date_default_timezone_set($dump['date_timezone']);
        }

        if (true === isset($dump['locale'])) {
            setLocale(LC_ALL, $dump['locale']);
        }

        $this->_is_restored = true;
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->_is_restored;
    }

    /**
     * [_initContainer description]
     * @param  boolean $force_reload [description]
     * @return [type]                [description]
     */
    private function _initContainer()
    {
        $this->_container = (new ContainerBuilder($this))->getContainer();

        return $this;
    }

    /**
     * [_initEnvVariables description]
     *
     * @return [type] [description]
     */
    private function _initEnvVariables()
    {
        if (true === $this->isRestored()) {
            return $this;
        }

        $date_config = $this->getConfig()->getDateConfig();
        if (false !== $date_config && true === isset($date_config['timezone'])) {
            if (false === date_default_timezone_set($date_config['timezone'])) {
                throw new \Exception(sprintf('Unabled to set default timezone (:%s)', $date_config['timezone']));
            }

            $this->dump_datas['date_timezone'] = $date_config['timezone'];
        }

        if (null !== $encoding = $this->getConfig()->getEncodingConfig()) {
            if (true === array_key_exists('locale', $encoding)) {
                if (false === setLocale(LC_ALL, $encoding['locale'])) {
                    throw new \Exception(sprintf('Unabled to setLocal with locale %s', $encoding['locale']));
                }

                $this->dump_datas['locale'] = $encoding['locale'];
            }
        }

        return $this;
    }

    /**
     * @return \BackBuilder\BBApplication
     */
    private function _initAutoloader()
    {
        if (true === $this->getAutoloader()->isRestored()) {
            return $this;
        }

        $this->getAutoloader()
            ->register()
            ->registerNamespace('BackBuilder\Bundle', implode('/', array($this->getBaseDir(), 'bundle')))
            ->registerNamespace('BackBuilder\ClassContent\Repository', implode('/', array($this->getRepository(), 'ClassContent', 'Repositories')))
            ->registerNamespace('BackBuilder\Renderer\Helper', implode('/', array($this->getRepository(), 'Templates', 'helpers')))
            ->registerNamespace('BackBuilder\Event\Listener', implode('/', array($this->getRepository(), 'Listeners')))
            ->registerNamespace('BackBuilder\Controller', implode('/', array($this->getRepository(), 'Controller')))
            ->registerNamespace('BackBuilder\Services\Public', implode('/', array($this->getRepository(), 'Services', 'Public')))
            ->registerNamespace('BackBuilder\Traits', implode('/', array($this->getRepository(), 'Traits')))
            ->registerNamespace('Respect\Validation\Rules', implode(DIRECTORY_SEPARATOR, array($this->getBBDir(),'Validator','Rules')));

        if (true === $this->hasContext()) {
            $this->getAutoloader()
                ->registerNamespace('BackBuilder\ClassContent\Repository', implode('/', array($this->getBaseRepository(), 'ClassContent', 'Repositories')))
                ->registerNamespace('BackBuilder\Renderer\Helper', implode('/', array($this->getBaseRepository(), 'Templates', 'helpers')))
                ->registerNamespace('BackBuilder\Event\Listener', implode('/', array($this->getBaseRepository(), 'Listeners')))
                ->registerNamespace('BackBuilder\Controller', implode('/', array($this->getBaseRepository(), 'Controller')))
                ->registerNamespace('BackBuilder\Services\Public', implode('/', array($this->getBaseRepository(), 'Services', 'Public')))
                ->registerNamespace('BackBuilder\Traits', implode('/', array($this->getBaseRepository(), 'Traits')));
        }

        return $this;
    }

    /**
     * [_initAnnotationReader description]
     */
    private function _initAnnotationReader()
    {
        // annotations require custom autoloading
        AnnotationRegistry::registerAutoloadNamespaces(array(
            'Symfony\Component\Validator\Constraint' => $this->getVendorDir() . '/symfony/symfony/src/',
            'JMS\Serializer\Annotation' => $this->getVendorDir() . '/jms/serializer/src/',
            'BackBuilder\Installer\Annotation' => $this->getBaseDir(),
            'BackBuilder' => $this->getBaseDir(),
            //'Doctrine\ORM\Mapping' => $this->getVendorDir() . '/doctrine/orm/lib/'
        ));

        // AnnotationReader ignores all annotations handled by SimpleAnnotationReader
        AnnotationReader::addGlobalIgnoredName('MappedSuperclass');
        AnnotationReader::addGlobalIgnoredName('Entity');
        AnnotationReader::addGlobalIgnoredName('Column');
        AnnotationReader::addGlobalIgnoredName('Table');
        AnnotationReader::addGlobalIgnoredName('HasLifecycleCallbacks');
        AnnotationReader::addGlobalIgnoredName('Index');
        AnnotationReader::addGlobalIgnoredName('Id');
        AnnotationReader::addGlobalIgnoredName('GeneratedValue');
        AnnotationReader::addGlobalIgnoredName('ManyToMany');
        AnnotationReader::addGlobalIgnoredName('JoinTable');
        AnnotationReader::addGlobalIgnoredName('JoinColumn');
        AnnotationReader::addGlobalIgnoredName('ManyToOne');
        AnnotationReader::addGlobalIgnoredName('OneToOne');
        AnnotationReader::addGlobalIgnoredName('OneToMany');
        AnnotationReader::addGlobalIgnoredName('PreUpdate');
        AnnotationReader::addGlobalIgnoredName('index');
        AnnotationReader::addGlobalIgnoredName('fixtures');
        AnnotationReader::addGlobalIgnoredName('fixture');
        AnnotationReader::addGlobalIgnoredName('column');
    }

    /**
     * @return \BackBuilder\BBApplication
     * @throws BBException
     */
    private function _initContentWrapper()
    {
        if (true === $this->getAutoloader()->isRestored()) {
            return $this;
        }

        if (null === $contentwrapperConfig = $this->getConfig()->getContentwrapperConfig()) {
            throw new BBException('None class content wrapper found');
        }

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
        // init NestedNode config
        if ($this->getConfig()->getSection('nestednode')) {
            NestedNodeRepository::$config = array_merge(NestedNodeRepository::$config, $this->getConfig()->getSection('nestednode'));
        }

        if (false === $this->_container->getDefinition('em')->isSynthetic()) {
            return;
        }

        if (null === $doctrine_config = $this->getConfig()->getDoctrineConfig()) {
            throw new BBException('None database configuration found');
        }

        if (false === array_key_exists('dbal', $doctrine_config)) {
            throw new BBException('None dbal configuration found');
        }

        if (false === array_key_exists('proxy_ns', $doctrine_config['dbal'])) {
            $doctrine_config['dbal']['proxy_ns'] = 'Proxies';
        }

        if (false === array_key_exists('proxy_dir', $doctrine_config['dbal'])) {
            $doctrine_config['dbal']['proxy_dir'] = $this->getCacheDir() . '/' . 'Proxies';
        }

        if (true === array_key_exists('orm', $doctrine_config)) {
            $doctrine_config['dbal']['orm'] = $doctrine_config['orm'];
        }

        // Init ORM event
        $r = new \ReflectionClass('Doctrine\ORM\Events');
        $definition = new Definition('Doctrine\Common\EventManager');
        $definition->addMethodCall('addEventListener', array($r->getConstants(), new Reference('doctrine.listener')));
        $this->_container->setDefinition('doctrine.event_manager', $definition);
        $evm = $this->_container->get('doctrine.event_manager');

        try {
            $logger_id = 'logging';

            if (true === $this->isDebugMode()) {
                // doctrine data collector
                $this->getContainer()->get('data_collector.doctrine')->addLogger(
                    'default',
                    $this->getContainer()->get('doctrine.dbal.logger.profiling')
                );
                $logger_id = 'doctrine.dbal.logger.profiling';
            }

            $definition = new Definition('Doctrine\ORM\EntityManager', array(
                $doctrine_config['dbal'],
                new Reference($logger_id),
                new Reference('doctrine.event_manager')
            ));
            $definition->setFactoryClass('BackBuilder\Util\Doctrine\EntityManagerCreator');
            $definition->setFactoryMethod('create');
            $this->_container->setDefinition('em', $definition);

            $this->debug(sprintf('%s(): Doctrine EntityManager initialized', __METHOD__));
        } catch (\Exception $e) {
            $this->warning(sprintf('%s(): Cannot initialize Doctrine EntityManager', __METHOD__));
        }

        return $this;
    }

    /**
     * [_initBundles description]
     *
     * @return [type] [description]
     */
    private function _initBundles()
    {
        if (null !== $this->getConfig()->getBundlesConfig()) {
            $this->getContainer()->get('bundle.loader')->load($this->getConfig()->getBundlesConfig());
        }

        return $this;
    }
}
