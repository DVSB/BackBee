<?php
namespace BackBuilder;

use BackBuilder\AutoLoader\AutoLoader,
    BackBuilder\Config\Config,
    BackBuilder\FrontController\FrontController,
    BackBuilder\Event\Dispatcher,
    BackBuilder\Event\Listener\DoctrineListener,
    BackBuilder\Exception\BBException,
    BackBuilder\Logging\Logger,
    BackBuilder\Security\SecurityContext,
    BackBuilder\Services\Rpc\JsonRPCServer,
    BackBuilder\Services\Upload\UploadServer,
    BackBuilder\Site\Site,
    BackBuilder\Rewriting\UrlGenerator,
    BackBuilder\Theme\Theme,
    BackBuilder\Util\File;
    
use Doctrine\Common\EventManager,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;
    
use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpFoundation\Session\Session,
    Symfony\Component\Translation\MessageSelector,
    Symfony\Component\Translation\IdentityTranslator;


class BBApplication {
    const VERSION = '1.0.1';
    
    private $_container;
    private $_context;
    private $_debug;
    private $_isinitialized;
    private $_isstarted;
    private $_autoloader;
    private $_basedir;
    private $_cachedir;
    private $_mediadir;
    private $_repository;
    private $_resourcedir;
    private $_starttime;
    private $_storagedir;
    private $_tmpdir;
    private $_bundles;
    private $_classcontentdir;
    private $_theme;
    
    public function __call($method, $args) {
        if ($this->getContainer()->has('logging')) {
            call_user_func_array(array($this->getContainer()->get('logging'), $method), $args);
        }
    }
    
    public function __construct($context = NULL, $debug = FALSE) {
        $this->_starttime = time();
        $this->_context = (NULL === $context) ? 'default' : $context;
        $this->_debug = (Boolean) $debug;
        $this->_isinitialized = FALSE;
        $this->_isstarted = FALSE;
  
        $this->_initAutoloader()
             ->_initLogging()
             ->_initContentWrapper()
             ->_initSecurityContext()
             ->_initTranslator()
             ->_initBundles();

        if (NULL !== $encoding = $this->getConfig()->getEncodingConfig()) {
            if (array_key_exists('locale', $encoding))
                setLocale(LC_ALL, $encoding['locale']);
        }
        $this->debug(sprintf('BBApplication (v.%s) initialization with context `%s`, debugging set to %s', self::VERSION, $this->_context, var_export($this->_debug, TRUE)));
        $this->debug(sprintf('  - Base directory set to `%s`', $this->getBaseDir()));
        $this->debug(sprintf('  - Repository directory set to `%s`', $this->getRepository()));
        
        $this->_isinitialized = TRUE;
    }

    public function runImport()
    {
        if (NULL !== $bundles = $this->getConfig()->getSection('importbundles')) {
            foreach($bundles as $classname) {
                new $classname($this);
            }
        }
    }

    public function __destruct() {
        if ($this->_isstarted)
            $this->info('BackBuilder application ended');
    }
    
    /**
     * @return \BackBuilder\BBApplication
     */
    private function _initAutoloader() {
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

    private function _initTranslator() {
        $message_selector = new MessageSelector();
        $translator = new IdentityTranslator($message_selector);
        $translator->setLocale('fr_FR');
//        $translator->addLoader('yml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
//        $translator->addResource('yml', implode(DIRECTORY_SEPARATOR, array($this->getBaseDir(), 'BackBuilder', 'Resources', 'translation', 'fr_FR', 'general.yml')), 'fr_FR');
        $this->getContainer()->set('translator', $translator);
        
        return $this;
    }

    /**
     *
     * @return BackBuilder\Theme\Theme
     */
    public function getTheme() {
        if (!is_object($this->_theme)) {
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
        return (bool)$this->_debug;
    }

    /**
     * @param string $configdir
     * @return \BackBuilder\BBApplication
     */
    private function _initConfig($configdir = null) {
        if (is_null($configdir))
            $configdir = $this->getRepository() . DIRECTORY_SEPARATOR . 'Config';


        $this->getContainer()->set('config', new Config($configdir, $this->getBootstrapCache()));

        return $this;
    }

    /**
     * @return \BackBuilder\BBApplication
     * @throws BBException
     */
    private function _initContentWrapper() {
        if (NULL === $contentwrapperConfig = $this->getConfig()->getContentwrapperConfig())
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
    private function _initEntityManager() {
        if (NULL === $doctrineConfig = $this->getConfig()->getDoctrineConfig())
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
        $r = new \ReflectionClass( 'Doctrine\ORM\Events' );
        $evm->addEventListener($r->getConstants(), new DoctrineListener($this));
        
        // Create EntityManager
        $connectionOptions = isset($doctrineConfig['dbal']) ? $doctrineConfig['dbal'] : array();
        $this->getContainer()->set('em', EntityManager::create($connectionOptions, $config, $evm));
        
        if (isset($doctrineConfig['dbal']) && isset($doctrineConfig['dbal']['charset'])) {
            try {
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_client = "'.addslashes($doctrineConfig['dbal']['charset']).'";');
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_connection = "'.addslashes($doctrineConfig['dbal']['charset']).'";');
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION character_set_results = "'.addslashes($doctrineConfig['dbal']['charset']).'";');
            } catch(\Exception $e) {
                throw new BBException(sprintf('Invalid database character set `%s`', $doctrineConfig['dbal']['charset']), BBException::INVALID_ARGUMENT, $e);
            }
        }
        
        if (isset($doctrineConfig['dbal']) && isset($doctrineConfig['dbal']['collation'])) {
            try {
                $this->getContainer()->get('em')->getConnection()->executeQuery('SET SESSION collation_connection = "'.addslashes($doctrineConfig['dbal']['collation']).'";');
            } catch(\Exception $e) {
                throw new BBException(sprintf('Invalid database collation `%s`', $doctrineConfig['dbal']['collation']), BBException::INVALID_ARGUMENT, $e);
            }
        }
        
        $this->debug(sprintf('%s(): Doctrine EntityManager initialized', __METHOD__));
        
        return $this;
    }

    /**
     * @return \BackBuilder\BBApplication
     */
    private function _initLogging() {
        $this->getContainer()->set('logging', new Logger($this));
        return $this;
    }
    
    /**
     * @return \BackBuilder\BBApplication
     * @throws BBException
     */
    private function _initRenderer() {
        if (NULL === $rendererConfig = $this->getConfig()->getRendererConfig())
            throw new BBException('None renderer configuration found');

        if (!isset($rendererConfig['adapter']))
            throw new BBException('None renderer adapter found');

        $this->getContainer()->set('renderer', new $rendererConfig['adapter']($this));
        
        $this->debug(sprintf('%s(): Renderer initialized with adapter `%s`', __METHOD__, $rendererConfig['adapter']));
        
        return $this;
    }
	
    /**
     * @return \BackBuilder\BBApplication
     */
    private function _initSecurityContext() {
        if (FALSE === $this->getContainer()->has('security.context'))
            $this->getContainer()->set('security.context', new SecurityContext($this));
        
        return $this;
    }

    private function _initBundles() {
        if (null === $this->_bundles)
            $this->_bundles = array();
        
        if (NULL !== $bundles = $this->getConfig()->getBundlesConfig()) {
            foreach($bundles as $name => $classname) {
                $bundle = new $classname($this);
                if ($bundle->init()) {
                    $this->getContainer()->set('bundle.'.$bundle->getId(), $bundle);
                    $this->_bundles['bundle.'.$bundle->getId()] = $bundle;
                }
            }
        }
    }
    
    /**
     * @param type $name
     * @return Bundle\ABundle
     */
    public function getBundle($name) {
        if ($this->getContainer()->has('bundle.'.$name)) {
            return $this->getContainer()->get('bundle.'.$name);
        }
        
        return NULL;
    }
    
    public function getBundles() {
        return $this->_bundles;
    }

    /**
     * @return boolean
     */
    public function debugMode() {
        return $this->_debug;
    }
    
    /**
     * @param \BackBuilder\Site\Site $site
     */
    public function start(Site $site = NULL) {
        if (NULL === $site) {
            $site = $this->getEntityManager()->getRepository('BackBuilder\Site\Site')->findOneBy(array());
        }
        
        if (NULL !== $site) {
            $this->getContainer()->set('site', $site);
        }
        
        $this->_isstarted = TRUE;
        $this->info(sprintf('BackBuilder application started (Site Uid: %s)', (NULL !== $site) ? $site->getUid() : 'none'));
        
        if (null !== $this->_bundles) {
            foreach($this->_bundles as $bundle)
                $bundle->start();
        }

        $this->getTheme()->init();
        
        $this->getController()->handle();
    }

    /**
     * @return FrontController
     */
    public function getController() {
        if (false === $this->getContainer()->has('controller')) {
            $this->getContainer()->set('controller', new FrontController($this));
        }
        
        return $this->getContainer()->get('controller');
    }
    
    /**
     * @return AutoLoader
     */
    public function getAutoloader() {
        if (NULL === $this->_autoloader) {
            $this->_autoloader = new AutoLoader($this);
        }

        return $this->_autoloader;
    }

    /**
     * @return string
     */
    public function getBaseDir() {
        if (NULL === $this->_basedir) {
            $r = new \ReflectionObject($this);
            $this->_basedir = dirname($r->getFileName()) . DIRECTORY_SEPARATOR . '..';
        }
        
        return $this->_basedir;
    }
    
    
    public function getContext()
    {
        return $this->_context;
    }
    
    /**
     * @return BackBuilder\Security\Token\BBUserToken|null
     */
    public function getBBUserToken() {
        $token = NULL;
        
        if ($this->getContainer()->has('bb_session')) {
            if (NULL !== $token = $this->getContainer()->get('bb_session')->get('_security_bb_area')) {
                $token = unserialize($token);
                
                if (!is_a($token, 'BackBuilder\Security\Token\BBUserToken'))
                    $token = NULL;
            }
        }
        
        return $token;
    }
    
    /**
     * @return \BackBuilder\Cache\DAO\Cache
     */
    public function getCacheControl() {
        if (!$this->getContainer()->has('cache-control')) {
            $this->getContainer()->set('cache-control', new \BackBuilder\Cache\DAO\Cache($this));
        }
        
        return $this->getContainer()->get('cache-control');
    }

    /**
     * 
     * @return \BackBuilder\Cache\ACache
     */
    public function getBootstrapCache()
    {
        if (!$this->getContainer()->has('cache.bootstrap')) {
            $this->getContainer()->set('cache.bootstrap', new Cache\File\Cache($this));
        }

        return $this->getContainer()->get('cache.bootstrap');        
    }
    
    public function getCacheDir()
    {
        if (NULL === $this->_cachedir) {
            $this->_cachedir = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'cache';
        }
        return $this->_cachedir;
    }
    
    /**
     * @return ContainerBuilder
     */
    public function getContainer() {
        if (NULL === $this->_container) {
            $this->_container = new ContainerBuilder();
        }

        return $this->_container;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        if (!$this->getContainer()->has('config')) {
            $this->_initConfig();
        }
        return $this->getContainer()->get('config');
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->getContainer()->has('em'))
            $this->_initEntityManager();

        return $this->getContainer()->get('em');
    }

    /**
     * @return Dispatcher
     */
    public function getEventDispatcher() {
        if (!$this->getContainer()->has('ed')) {
            $this->getContainer()->set('ed', new Dispatcher($this));
        }
        
        return $this->getContainer()->get('ed');
    }
    
    /**
     * @return Logger
     */
    public function getLogging() {
        if (FALSE === $this->getContainer()->has('logging'))
            $this->_initLogging();
        
        return $this->getContainer()->get('logging');
    }
    
    public function getMediaDir() {
        if (NULL === $this->_mediadir) {
            $this->_mediadir = implode(DIRECTORY_SEPARATOR, array($this->getRepository(), 'Data', 'Media'));
        }

        return $this->_mediadir;
    }
    
    /**
     * @return Renderer\ARenderer
     */
    public function getRenderer() {
        if (!$this->getContainer()->has('renderer'))
            $this->_initRenderer();

        return $this->getContainer()->get('renderer');
    }

    public function getRepository() {
        if (NULL === $this->_repository) {
            $this->_repository = $this->getBaseDir() . DIRECTORY_SEPARATOR . 'repository';
            if (NULL !== $this->_context && 'default' != $this->_context)
                $this->_repository .= DIRECTORY_SEPARATOR . $this->_context;
        }
        return $this->_repository;
    }

    /**
     * Return the classcontent repositories path for this instance
     * @return array
     */
    public function getClassContentDir()
    {
        if (NULL === $this->_classcontentdir) {
            $this->_classcontentdir = array();
            
            array_unshift($this->_classcontentdir, $this->getBaseDir().'/BackBuilder/ClassContent');
            array_unshift($this->_classcontentdir, $this->getBaseDir().'/repository/ClassContent');

            if (NULL !== $this->_context && 'default' != $this->_context) {
                array_unshift($this->_classcontentdir, $this->getRepository().'/ClassContent');
            }
            
            array_walk( $this->_classcontentdir, array('BackBuilder\Util\File', 'resolveFilepath'));
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
        if (NULL === $this->_resourcedir) {
            $this->_resourcedir = array();
            
            $this->addResourceDir( $this->getBaseDir().'/BackBuilder/Resources' )
                 ->addResourceDir( $this->getBaseDir().'/repository/Ressources' );
            
            if (NULL !== $this->_context && 'default' != $this->_context) {
                 $this->addResourceDir( $this->getRepository().'/Ressources' );
            }
            
            array_walk( $this->_resourcedir, array('BackBuilder\Util\File', 'resolveFilepath'));
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
    public function addResourceDir( $dir )
    {
        if (NULL === $this->_resourcedir) {
            $this->_resourcedir = array();
        }
        
        if (false === is_array($this->_resourcedir)) {
            throw new BBException('Misconfiguration of the BBApplication : resource dir has to be an array', BBException::INVALID_ARGUMENT);
        }

        if (false === file_exists( $dir ) || false === is_dir( $dir )) {
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
        
        return array_shift( $dir );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     * @throws BBException
     */
    public function getRequest() {
        if (FALSE === $this->isStarted())
            throw new BBException('The BackBuilder application has to be started before to access request');
        
        return $this->getController()->getRequest();
    }

    /**
     * @return JsonRPCServer
     */
    public function getRpcServer() {
        if (!$this->getContainer()->has('rpcserver')) {
            $this->getContainer()->set('rpcserver', new JsonRPCServer($this));
        }

        return $this->getContainer()->get('rpcserver');
    }

    /**
     * @return UploadServer
     */
    public function getUploadServer() {
        if (!$this->getContainer()->has('uploadserver')) {
            $this->getContainer()->set('uploadserver', new UploadServer($this));
        }

        return $this->getContainer()->get('uploadserver');
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator() {
        if (!$this->getContainer()->has('rewriting.urlgenerator')) {
            $this->getContainer()->set('rewriting.urlgenerator', new UrlGenerator($this));
        }
        
        return $this->getContainer()->get('rewriting.urlgenerator');
    }
    
    /**
     * @return \Symfony\Component\HttpFoundation\Session\SessionInterface|null The session
     */
    public function getSession() {
        if (NULL === $this->getRequest()->getSession()) {
            $session = new Session();
            $session->start();
            $this->getRequest()->setSession($session);
        }
        return $this->getRequest()->getSession();
    }
    
    /**
     * @return SecurityContext
     */
    public function getSecurityContext() {
        if (!$this->getContainer()->has('security.context')) {
            $this->_initSecurityContext();
        }
        return $this->getContainer()->get('security.context');
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        if ($this->getContainer()->has('site')){
            return $this->getContainer()->get('site');
        }
        return NULL;
    }
    
    /**
     * @return string
     */
    public function getStorageDir() {
        if (NULL === $this->_storagedir) {
            $this->_storagedir = $this->getRepository() . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Storage';
        }

        return $this->_storagedir;
    }
    
    /**
     * @return string
     */
    public function getTemporaryDir() {
        if (NULL === $this->_tmpdir) {
            $this->_tmpdir = $this->getRepository() . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Tmp';
        }

        return $this->_tmpdir;
    }
    
    /**
     * @return boolean
     */
    public function isReady() {
        return ($this->_isinitialized && NULL !== $this->_container);
    }
    
    /**
     * @return boolean
     */
    public function isStarted() {
        return (TRUE === $this->_isstarted);
    }
}