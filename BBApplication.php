<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee;

use BackBee\Console\Console;
use BackBee\DependencyInjection\ContainerBuilder;
use BackBee\DependencyInjection\ContainerInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceInterface;
use BackBee\DependencyInjection\Dumper\DumpableServiceProxyInterface;
use BackBee\Event\Event;
use BackBee\Exception\BBException;
use BackBee\NestedNode\Repository\NestedNodeRepository;
use BackBee\Security\Token\BBUserToken;
use BackBee\Site\Site;
use BackBee\Utils\File\File;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The main BackBee application.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class BBApplication implements ApplicationInterface, DumpableServiceInterface, DumpableServiceProxyInterface
{
    const VERSION = '1.0.0-DEV';

    /**
     * application's service container.
     *
     * @var BackBee\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * application's context.
     *
     * @var string
     */
    private $context;

    /**
     * application's environment.
     *
     * @var string
     */
    private $environment;

    /**
     * define if application is started with debug mode or not.
     *
     * @var boolean
     */
    private $debug;
    private $isInitialized;
    private $isStarted;
    private $repository;
    private $baseRepository;
    private $resourceDir;
    private $storageDir;
    private $tmpDir;
    private $classcontentDir;
    private $overwriteConfig;

    /**
     * tell us if application has been restored by container or not.
     *
     * @var boolean
     */
    private $isRestored;

    /**
     * @var array
     */
    private $dumpDatas;

    /**
     * @param string $context
     * @param true   $debug
     * @param true   $overwrite_config set true if you need overide base config with the context config
     */
    public function __construct($context = null, $environment = null, $overwrite_config = false)
    {
        $this->context = null === $context ? self::DEFAULT_CONTEXT : $context;
        $this->isInitialized = false;
        $this->isStarted = false;
        $this->overwriteConfig = $overwrite_config;
        $this->isRestored = false;
        $this->environment = null !== $environment && is_string($environment)
            ? $environment
            : self::DEFAULT_ENVIRONMENT
        ;
        $this->dumpDatas = [];

        $this->initAnnotationReader();
        $this->initContainer();

        if ($this->isDebugMode()) {
            Debug::enable();
        }

        $this->initEnvVariables();
        $this->initAutoloader();
        $this->initContentWrapper();

        try {
            $this->initEntityManager();
        } catch (\Exception $e) {
            $this->getLogging()->notice('BackBee starting without EntityManager');
        }

        $this->initBundles();

        if (!$this->getContainer()->has('em')) {
            $this->debug(sprintf(
                'BBApplication (v.%s) partial initialization with context `%s`, debugging set to %s',
                self::VERSION,
                $this->context,
                var_export($this->debug, true)
            ));

            return;
        }

        // Force container to create SecurityContext object to activate listener
        $this->getSecurityContext();

        $this->debug(sprintf(
            'BBApplication (v.%s) initialization with context `%s`, debugging set to %s',
            self::VERSION,
            $this->context,
            var_export($this->debug, true)
        ));
        $this->debug(sprintf('  - Base directory set to `%s`', $this->getBaseDir()));
        $this->debug(sprintf('  - Repository directory set to `%s`', $this->getRepository()));

        $this->isInitialized = true;

        // trigger bbapplication.init
        $this->getEventDispatcher()->dispatch('bbapplication.init', new Event($this));
    }

    public function __destruct()
    {
        $this->stop();
    }

    public function __call($method, $args)
    {
        if ($this->getContainer()->has('logging')) {
            call_user_func_array([$this->getContainer()->get('logging'), $method], $args);
        }
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer()
    {
        if (!$this->getContainer()->has('mailer') || is_null($this->getContainer()->get('mailer'))) {
            if (null !== $mailer_config = $this->getConfig()->getSection('mailer')) {
                $smtp = is_array($mailer_config['smtp']) ? reset($mailer_config['smtp']) : $mailer_config['smtp'];
                $port = is_array($mailer_config['port']) ? reset($mailer_config['port']) : $mailer_config['port'];

                $transport = \Swift_SmtpTransport::newInstance($smtp, $port);
                if (array_key_exists('username', $mailer_config) && array_key_exists('password', $mailer_config)) {
                    $username = is_array($mailer_config['username'])
                        ? reset($mailer_config['username'])
                        : $mailer_config['username']
                    ;
                    $password = is_array($mailer_config['password'])
                        ? reset($mailer_config['password'])
                        : $mailer_config['password']
                    ;

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
        $debug = (bool) $this->debug;
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
        return $this->overwriteConfig;
    }

    /**
     * @param type $name
     *
     * @return AbstractBundle
     */
    public function getBundle($name)
    {
        $bundle = null;
        if ($this->getContainer()->has('bundle.'.$name)) {
            $bundle = $this->getContainer()->get('bundle.'.$name);
        }

        return $bundle;
    }

    /**
     * returns every registered bundles.
     *
     * @return array
     */
    public function getBundles()
    {
        $bundles = [];
        foreach ($this->getContainer()->findTaggedServiceIds('bundle') as $id => $datas) {
            $bundles[] = $this->getContainer()->get($id);
        }

        return $bundles;
    }

    /**
     * @param \BackBee\Site\Site $site
     */
    public function start(Site $site = null)
    {
        if (null === $site) {
            $site = $this->getEntityManager()->getRepository('BackBee\Site\Site')->findOneBy([]);
        }

        if (null !== $site) {
            $this->getContainer()->set('site', $site);
        }

        $this->isStarted = true;
        $this->info(sprintf('BackBee application started (Site Uid: %s)', null !== $site ? $site->getUid() : 'none'));

        // trigger bbapplication.start
        $this->getEventDispatcher()->dispatch('bbapplication.start', new Event($this));

        if (!$this->isClientSAPI()) {
            $response = $this->getController()->handle();
            if ($response instanceof Response) {
                $this->getController()->sendResponse($response);
            }
        }
    }

    /**
     * Stop the current BBApplication instance.
     */
    public function stop()
    {
        if ($this->isStarted()) {
            // trigger bbapplication.stop
            $this->getEventDispatcher()->dispatch('bbapplication.stop', new Event($this));
            $this->info('BackBee application ended');
        }
    }

    /**
     * @return \BackBee\FrontController\FrontController
     */
    public function getController()
    {
        return $this->getContainer()->get('controller');
    }

    /**
     * @return \BackBee\Routing\RouteCollection
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
        return __DIR__;
    }

    /**
     * Returns path to Data directory.
     *
     * @return string absolute path to Data directory
     */
    public function getDataDir()
    {
        return $this->container->getParameter('bbapp.data.dir');
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return dirname($this->getBBDir());
    }

    /**
     * Get vendor dir.
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->getBaseDir().DIRECTORY_SEPARATOR.'vendor';
    }

    /**
     * Returns TRUE if a starting context is defined, FALSE otherwise.
     *
     * @return boolean
     */
    public function hasContext()
    {
        return null !== $this->context && self::DEFAULT_CONTEXT !== $this->context;
    }

    /**
     * Returns the starting context.
     *
     * @return string|NULL
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return BBUserToken|null
     */
    public function getBBUserToken()
    {
        $token = $this->getSecurityContext()->getToken();
        if ((null === $token || !($token instanceof BBUserToken))) {
            if (is_null($this->getContainer()->get('bb_session'))) {
                $token = null;
            } else {
                if (null !== $token = $this->getContainer()->get('bb_session')->get('_security_bb_area')) {
                    $token = unserialize($token);

                    if (!($token instanceof BBUserToken)) {
                        $token = null;
                    }
                }
            }
        }

        return $token;
    }

    /**
     * Get cache provider from config.
     *
     * @return string Cache provider config name or \BackBee\Cache\DAO\Cache if not found
     */
    public function getCacheProvider()
    {
        $conf = $this->getConfig()->getCacheConfig();

        return isset($conf['provider']) && is_subclass_of($conf['provider'], '\BackBee\Cache\AExtendedCache')
            ? $conf['provider']
            : '\BackBee\Cache\DAO\Cache'
        ;
    }

    /**
     * @return \BackBee\Cache\DAO\Cache
     */
    public function getCacheControl()
    {
        return $this->getContainer()->get('cache.control');
    }

    /**
     * @return \BackBee\Cache\AbstractCache
     */
    public function getBootstrapCache()
    {
        return $this->getContainer()->get('cache.bootstrap');
    }

    public function getCacheDir()
    {
        if (null === $this->container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->getContainer()->getParameter('bbapp.cache.dir');
    }

    /**
     * @return \BackBee\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get validator service.
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
        if (null === $this->container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->container->get('config');
    }

    /**
     * Get current environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getConfigDir()
    {
        return $this->getRepository().'/'.'Config';
    }

    public function getBBConfigDir()
    {
        return $this->getBaseRepository().'/'.'Config';
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager($name = 'default')
    {
        try {
            if (null === $this->getContainer()->get('doctrine')) {
                $this->initEntityManager();
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
        if (null === $this->container) {
            throw new \Exception('Application\'s container is not ready!');
        }

        return $this->getContainer()->getParameter('bbapp.media.dir');
    }

    /**
     * @return Renderer\AbstractRenderer
     */
    public function getRenderer()
    {
        return $this->getContainer()->get('renderer');
    }

    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->getBaseRepository();
            if ($this->hasContext()) {
                $this->repository .= DIRECTORY_SEPARATOR.$this->context;
            }
        }

        return $this->repository;
    }

    public function getBaseRepository()
    {
        if (null === $this->baseRepository) {
            $this->baseRepository = $this->getBaseDir().DIRECTORY_SEPARATOR.'repository';
        }

        return $this->baseRepository;
    }

    /**
     * Return the classcontent repositories path for this instance.
     *
     * @return array
     */
    public function getClassContentDir()
    {
        if (null === $this->classcontentDir) {
            $this->classcontentDir = [];

            array_unshift($this->classcontentDir, $this->getBBDir().'/ClassContent');
            array_unshift($this->classcontentDir, $this->getBaseRepository().'/ClassContent');

            if ($this->hasContext()) {
                array_unshift($this->classcontentDir, $this->getRepository().'/ClassContent');
            }

            array_map(['BackBee\Utils\File\File', 'resolveFilepath'], $this->classcontentDir);
        }

        return $this->classcontentDir;
    }

    /**
     * Push one directory at the end of classcontent dirs.
     *
     * @param string $dir
     *
     * @return \BackBee\BBApplication
     */
    public function pushClassContentDir($dir)
    {
        File::resolveFilepath($dir);

        $classcontentdir = $this->getClassContentDir();
        array_push($classcontentdir, $dir);

        $this->classcontentDir = $classcontentdir;

        return $this;
    }

    /**
     * Prepend one directory at the beginning of classcontent dirs.
     *
     * @param type $dir
     *
     * @return \BackBee\BBApplication
     */
    public function unshiftClassContentDir($dir)
    {
        File::resolveFilepath($dir);

        $classcontentdir = $this->getClassContentDir();
        array_unshift($classcontentdir, $dir);

        $this->classcontentDir = $classcontentdir;

        return $this;
    }

    /**
     * Return the resource directories, if undefined, initialized with common resources.
     *
     * @return array The resource directories
     */
    public function getResourceDir()
    {
        if (null === $this->resourceDir) {
            $this->resourceDir = [];

            $this->addResourceDir($this->getBBDir().'/Resources');

            if (is_dir($this->getBaseRepository().'/Resources')) {
                $this->addResourceDir($this->getBaseRepository().'/Resources');
            }

            if (is_dir($this->getBaseRepository().'/Ressources')) {
                $this->addResourceDir($this->getBaseRepository().'/Ressources');
            }

            if ($this->hasContext()) {
                if (is_dir($this->getRepository().'/Resources')) {
                    $this->addResourceDir($this->getRepository().'/Resources');
                }

                if (is_dir($this->getRepository().'/Resources')) {
                    $this->addResourceDir($this->getRepository().'/Resources');
                }
            }

            array_map(['BackBee\Utils\File\File', 'resolveFilepath'], $this->resourceDir);
        }

        return $this->resourceDir;
    }

    /**
     * Push one directory at the end of resources dirs.
     *
     * @param string $dir
     *
     * @return \BackBee\BBApplication
     */
    public function pushResourceDir($dir)
    {
        File::resolveFilepath($dir);

        $resourcedir = $this->getResourceDir();
        array_push($resourcedir, $dir);

        $this->resourceDir = $resourcedir;

        return $this;
    }

    /**
     * Prepend one directory at the begining of resources dirs.
     *
     * @param type $dir
     *
     * @return \BackBee\BBApplication
     */
    public function unshiftResourceDir($dir)
    {
        File::resolveFilepath($dir);

        $resourcedir = $this->getResourceDir();
        array_unshift($resourcedir, $dir);

        $this->resourceDir = $resourcedir;

        return $this;
    }

    /**
     * Prepend one directory of resources.
     *
     * @param String $dir The new resource directory to add
     *
     * @return \BackBee\BBApplication The current BBApplication
     *
     * @throws BBException Occur on invalid path or invalid resource directories
     */
    public function addResourceDir($dir)
    {
        if (null === $this->resourceDir) {
            $this->resourceDir = [];
        }

        if (!is_array($this->resourceDir)) {
            throw new BBException(
                'Misconfiguration of the BBApplication : resource dir has to be an array',
                BBException::INVALID_ARGUMENT
            );
        }

        if (!file_exists($dir) || !is_dir($dir)) {
            throw new BBException(
                sprintf('The resource folder `%s` does not exist or is not a directory', $dir),
                BBException::INVALID_ARGUMENT
            );
        }

        array_unshift($this->resourceDir, $dir);

        return $this;
    }

    /**
     * Return the current resource dir (ie the first one in those defined).
     *
     * @return string the file path of the current resource dir
     *
     * @throws BBException Occur when none resource dir is defined
     */
    public function getCurrentResourceDir()
    {
        $dir = $this->getResourceDir();

        if (0 === count($dir)) {
            throw new BBException(
                'Misconfiguration of the BBApplication : none resource dir defined',
                BBException::INVALID_ARGUMENT
            );
        }

        return array_shift($dir);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     *
     * @throws BBException
     */
    public function getRequest()
    {
        if (!$this->isStarted()) {
            throw new BBException('The BackBee application has to be started before to access request');
        }

        return $this->container->get('request');
    }

    /**
     * @return BackBee\Rewriting\UrlGenerator
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
            if ('test' === $this->getEnvironment()) {
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
     * @return BackBee\Security\SecurityContext
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
        return $this->getContainer()->has('site') ? $this->getContainer()->get('site') : null;
    }

    /**
     * @return string
     */
    public function getStorageDir()
    {
        if (null === $this->storageDir) {
            $this->storageDir = $this->container->getParameter('bbapp.data.dir').DIRECTORY_SEPARATOR.'Storage';
        }

        return $this->storageDir;
    }

    /**
     * @return string
     */
    public function getTemporaryDir()
    {
        if (null === $this->tmpDir) {
            $this->tmpDir = $this->container->getParameter('bbapp.data.dir').DIRECTORY_SEPARATOR.'Tmp';
        }

        return $this->tmpDir;
    }

    /**
     * @return boolean
     */
    public function isReady()
    {
        return $this->isInitialized && null !== $this->container;
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return $this->isStarted;
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
     * @param BackBee\Console\Console $console An Application instance
     */
    public function registerCommands(Console $console)
    {
        if (is_dir($dir = $this->getBBDir().'/Command')) {
            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($dir);
            $ns = 'BackBee\\Command';

            foreach ($finder as $file) {
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\'.strtr($relativePath, '/', '\\');
                }
                $r = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
                if (
                    $r->isSubclassOf('BackBee\\Console\\AbstractCommand')
                    && !$r->isAbstract()
                    && !$r->getConstructor()->getNumberOfRequiredParameters()
                ) {
                    $console->add($r->newInstance());
                }
            }
        }

        foreach ($this->getBundles() as $bundle) {
            if (!is_dir($dir = $bundle->getBaseDirectory().'/Command')) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($dir);
            $ns = $bundle->getNamespace().'\\Command';

            foreach ($finder as $file) {
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\'.strtr($relativePath, '/', '\\');
                }
                $r = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
                if (
                    $r->isSubclassOf('BackBee\\Console\\AbstractCommand')
                    && !$r->isAbstract()
                    && 0 === $r->getConstructor()->getNumberOfRequiredParameters()
                ) {
                    $instance = $r->newInstance();
                    $instance->setBundle($bundle);
                    $console->add($instance);
                }
            }
        }
    }

    /**
     * Returns the namespace of the class proxy to use or null if no proxy is required.
     *
     * @return string|null the namespace of the class proxy to use on restore or null if no proxy required
     */
    public function getClassProxy()
    {
        return;
    }

    /**
     * Dumps current service state so we can restore it later by calling DumpableServiceInterface::restore()
     * with the dump array produced by this method.
     *
     * @return array contains every datas required by this service to be restored at the same state
     */
    public function dump(array $options = [])
    {
        return array_merge($this->dumpDatas, [
            'classcontent_directories' => $this->classcontentDir,
            'resources_directories'    => $this->resourceDir,
        ]);
    }

    /**
     * Restore current service to the dump's state.
     *
     * @param array $dump the dump provided by DumpableServiceInterface::dump() from where we can
     *                    restore current service
     */
    public function restore(ContainerInterface $container, array $dump)
    {
        $this->classcontentDir = $dump['classcontent_directories'];
        $this->resourceDir = $dump['resources_directories'];

        if (isset($dump['date_timezone'])) {
            date_default_timezone_set($dump['date_timezone']);
        }

        if (isset($dump['locale'])) {
            setLocale(LC_ALL, $dump['locale']);
        }

        $this->isRestored = true;
    }

    /**
     * @return boolean true if current service is already restored, otherwise false
     */
    public function isRestored()
    {
        return $this->isRestored;
    }

    /**
     * Initializes application's dependency injection container.
     *
     * @return self
     */
    private function initContainer()
    {
        $this->container = (new ContainerBuilder($this))->getContainer();

        return $this;
    }

    /**
     * @return self
     */
    private function initEnvVariables()
    {
        if ($this->isRestored()) {
            return $this;
        }

        $dateConfig = $this->getConfig()->getDateConfig();
        if (false !== $dateConfig && isset($dateConfig['timezone'])) {
            if (false === date_default_timezone_set($dateConfig['timezone'])) {
                throw new \Exception(sprintf('Unabled to set default timezone (:%s)', $dateConfig['timezone']));
            }

            $this->dumpDatas['date_timezone'] = $dateConfig['timezone'];
        }

        if (null !== $encoding = $this->getConfig()->getEncodingConfig()) {
            if (array_key_exists('locale', $encoding)) {
                if (false === setLocale(LC_ALL, $encoding['locale'])) {
                    throw new \Exception(sprintf('Unabled to setLocal with locale %s', $encoding['locale']));
                }

                $this->dumpDatas['locale'] = $encoding['locale'];
            }
        }

        return $this;
    }

    /**
     * @return \BackBee\BBApplication
     */
    private function initAutoloader()
    {
        if ($this->getAutoloader()->isRestored()) {
            return $this;
        }

        $this->getAutoloader()
            ->register()
            ->registerNamespace('BackBee\Bundle', $this->getBaseDir().DIRECTORY_SEPARATOR.'bundle')
            ->registerNamespace(
                'BackBee\Renderer\Helper',
                implode(DIRECTORY_SEPARATOR, [$this->getRepository(), 'Templates', 'helpers'])
            )
            ->registerNamespace('BackBee\Event\Listener', $this->getRepository().DIRECTORY_SEPARATOR.'Listener')
            ->registerNamespace('BackBee\Controller', $this->getRepository().DIRECTORY_SEPARATOR.'Controller')
            ->registerNamespace('BackBee\Traits', $this->getRepository().DIRECTORY_SEPARATOR.'Traits')
            ->registerNamespace(
                'Respect\Validation\Rules',
                implode(DIRECTORY_SEPARATOR, [$this->getBBDir(), 'Validator', 'Rules'])
            )
        ;

        if ($this->hasContext()) {
            $this->getAutoloader()
                ->registerNamespace(
                    'BackBee\Renderer\Helper',
                    implode(DIRECTORY_SEPARATOR, [$this->getBaseRepository(), 'Templates', 'helpers'])
                )
                ->registerNamespace(
                    'BackBee\Event\Listener',
                    $this->getBaseRepository().DIRECTORY_SEPARATOR.'Listener'
                )
                ->registerNamespace('BackBee\Controller', $this->getBaseRepository().DIRECTORY_SEPARATOR.'Controller')
                ->registerNamespace('BackBee\Traits', $this->getBaseRepository().DIRECTORY_SEPARATOR.'Traits')
            ;
        }

        return $this;
    }

    /**
     * register all annotations and init the AnnotationReader
     */
    private function initAnnotationReader()
    {
        AnnotationRegistry::registerFile(
            $this->getVendorDir().'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
        );

        // annotations require custom autoloading
        AnnotationRegistry::registerAutoloadNamespaces([
            'Symfony\Component\Validator\Constraint' => $this->getVendorDir().'/symfony/validator/',
            'JMS\Serializer\Annotation'              => $this->getVendorDir().'/jms/serializer/src/',
        ]);

        AnnotationRegistry::registerLoader(function ($classname) {
            if (0 === strpos($classname, 'BackBee')) {
                return class_exists($classname);
            }

            return false;
        });
    }

    /**
     * @return \BackBee\BBApplication
     *
     * @throws BBException
     */
    private function initContentWrapper()
    {
        if ($this->getAutoloader()->isRestored()) {
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
     * @return \BackBee\BBApplication
     *
     * @throws BBException
     */
    private function initEntityManager()
    {
        // init NestedNode config
        if ($this->getConfig()->getSection('nestednode')) {
            NestedNodeRepository::$config = array_merge(
                NestedNodeRepository::$config,
                $this->getConfig()->getSection('nestednode')
            );

            $console_command = $this->container->getParameter('bbapp.console.command');
            if ('/' !== $console_command[0]) {
                $console_command = $this->getBaseDir().'/'.$console_command;
                $this->container->setParameter('bbapp.console.command', $console_command);
            }

            NestedNodeRepository::$config['script_command'] = $this->container->getParameter('bbapp.script.command');
            NestedNodeRepository::$config['console_command'] = $console_command;
            NestedNodeRepository::$config['environment'] = $this->getEnvironment();
        }

        if (!$this->container->getDefinition('em')->isSynthetic()) {
            return;
        }

        if (null === $doctrine_config = $this->getConfig()->getRawSection('doctrine')) {
            throw new BBException('None database configuration found');
        }

        if (!array_key_exists('dbal', $doctrine_config)) {
            throw new BBException('None dbal configuration found');
        }

        if (!array_key_exists('proxy_ns', $doctrine_config['dbal'])) {
            $doctrine_config['dbal']['proxy_ns'] = 'Proxies';
        }

        if (!array_key_exists('proxy_dir', $doctrine_config['dbal'])) {
            $doctrine_config['dbal']['proxy_dir'] = $this->getCacheDir().'/'.'Proxies';
        }

        if (array_key_exists('orm', $doctrine_config)) {
            $doctrine_config['dbal']['orm'] = $doctrine_config['orm'];
        }

        // Init ORM event
        $r = new \ReflectionClass('Doctrine\ORM\Events');
        $definition = new Definition('Doctrine\Common\EventManager');
        $definition->addMethodCall('addEventListener', [$r->getConstants(), new Reference('doctrine.listener')]);
        $this->container->setDefinition('doctrine.event_manager', $definition);

        try {
            $logger_id = 'logging';

            if ($this->isDebugMode()) {
                // doctrine data collector
                $this->getContainer()->get('data_collector.doctrine')->addLogger(
                    'default',
                    $this->getContainer()->get('doctrine.dbal.logger.profiling')
                );
                $logger_id = 'doctrine.dbal.logger.profiling';
            }

            $definition = new Definition('Doctrine\ORM\EntityManager', [
                $doctrine_config['dbal'],
                new Reference($logger_id),
                new Reference('doctrine.event_manager'),
                new Reference('service_container'),
            ]);
            $definition->setFactoryClass('BackBee\Util\Doctrine\EntityManagerCreator');
            $definition->setFactoryMethod('create');
            $this->container->setDefinition('em', $definition);

            $this->debug(sprintf('%s(): Doctrine EntityManager initialized', __METHOD__));
        } catch (\Exception $e) {
            $this->warning(sprintf('%s(): Cannot initialize Doctrine EntityManager', __METHOD__));
        }

        return $this;
    }

    /**
     * Loads every declared bundles into application.
     *
     * @return self
     */
    private function initBundles()
    {
        if (null !== $this->getConfig()->getBundlesConfig()) {
            $this->getContainer()->get('bundle.loader')->load($this->getConfig()->getBundlesConfig());
        }

        return $this;
    }
}
