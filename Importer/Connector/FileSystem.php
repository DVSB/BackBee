<?php
namespace BackBuilder\Importer\Connector;

use BackBuilder\BBApplication,
    BackBuilder\Importer\IImporterConnector,
    BackBuilder\Util\File;

/**
 * @category    BackBuilder
 * @package     BackBuilder\Importer\Connector
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class FileSystem implements IImporterConnector
{
    /**
     * @var BackBuilder\BBApplication
     */
    private $_application;
    
    /**
     * The base directory where to look for files
     * @var string
     */
    private $_basedir;
    
    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application
     * @param array $config
     */
    public function __construct(BBApplication $application, array $config)
    {
        $this->_application = $application;
        $this->_config = $config;
        
        if (true === array_key_exists('basedir', $config)) {
            $this->_basedir = $config['basedir'];
            File::resolveFilepath($this->_basedir, NULL, array('include_path' => $this->_application->getRepository()));
        } else {
            $this->_basedir = $this->_application->getRepository();
        }
    }

    /**
     * Return the path files according to the provided pattern
     *
     * @param string $pattern file pattern
     * @return array
     */
    public function find($pattern)
    {
        $values = glob($this->_basedir . DIRECTORY_SEPARATOR . $pattern);
        sort($values);
        
        return $values;
    }
}
