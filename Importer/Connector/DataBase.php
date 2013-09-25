<?php
namespace BackBuilder\Importer\Connector;

use BackBuilder\BBApplication,
    BackBuilder\Importer\IImporterConnector;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\Query;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer\Connector
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
class DataBase implements IImporterConnector
{
    private $_config;
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $_connector;
    /**
     * @var BackBuilder\BBApplication
     */
    private $_application;

    public function __construct(BBApplication $application, array $config)
    {
        $this->_application = $application;
        $this->_config = $config;
        $this->_connector = $this->_initEntityManager();
    }

    public function getConnector()
    {
        return $this->_connector;
    }

    /**
     * Single interface to find
     *
     * @param string $string
     * @return array
     */
    public function find($string)
    {
        $statement = $this->_connector->getConnection()->executeQuery($string);

        return $statement->fetchAll();
    }

    /**
     * Init the doctrine entity manager
     *
     * @return Doctrine\ORM\EntityManager
     */
    private function _initEntityManager()
    {
        // New database configuration
        $config = new Configuration;
        $driverImpl = $config->newDefaultAnnotationDriver();
        $config->setMetadataDriverImpl($driverImpl);

        $proxiesPath = $this->_application->getCacheDir() . DIRECTORY_SEPARATOR . 'Proxies';
        $config->setProxyDir($proxiesPath);
        $config->setProxyNamespace('Proxies');

        // Create EntityManager
        $em = EntityManager::create($this->_config, $config);
        
        if (isset($this->_config['charset'])) {
            try {
                $em->getConnection()->executeQuery('SET SESSION character_set_client = "'.addslashes($this->_config['charset']).'";');
                $em->getConnection()->executeQuery('SET SESSION character_set_connection = "'.addslashes($this->_config['charset']).'";');
                $em->getConnection()->executeQuery('SET SESSION character_set_results = "'.addslashes($this->_config['charset']).'";');
            } catch(\Exception $e) {
                throw new BBException(sprintf('Invalid database character set `%s`', $this->_config['charset']), BBException::INVALID_ARGUMENT, $e);
            }
        }
        
        if (isset($this->_config['collation'])) {
            try {
                $em->getConnection()->executeQuery('SET SESSION collation_connection = "'.addslashes($this->_config['collation']).'";');
            } catch(\Exception $e) {
                throw new BBException(sprintf('Invalid database collation `%s`', $this->_config['collation']), BBException::INVALID_ARGUMENT, $e);
            }
        }
        
        return $em;
    }
}