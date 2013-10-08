<?php
namespace BackBuilder\Stream\ClassWrapper;

use BackBuilder\Stream\IStreamWrapper,
    BackBuilder\Cache\File\Cache,
    BackBuilder\Stream\ClassWrapper\Exception\ClassWrapperException;

/**
 * Abstract class for content wrapper in BackBuilder 4
 * Implements IClassWrapper
 *
 * BackBuilder defines bb.class protocol to include its class definition
 * Several wrappers could be defined for several storing formats:
 *  - yaml files
 *  - xml files
 *  - yaml stream stored in DB
 *  - ...
 *
 * @category    BackBuilder
 * @package     BackBuilder\Stream\ClassWrapper
 * @copyright   Lp system
 * @author      c.rouillon
 */
abstract class AClassWrapper implements IStreamWrapper
{

    /**
     * The registered BackBuilder autoloader
     * @var \BackBuilder\Autoloader\Autoloader
     */
    protected $_autoloader;
    
    /**
     * The data of the stream
     * @var string
     */
    protected $_data;
    
    /**
     * The seek position in the stream
     * @var int
     */
    protected $_pos = 0;
    
    /**
     * The protocol handled by the wrapper
     * @var string
     */
    protected $_protocol = "bb.class";
    
    /**
     * Information about the stream ressource
     * @var array
     */
    protected $_stat;
    
    /**
     * the class content name to load
     * @var string
     */
    protected $classname;
    
    /**
     * The class to be extended by the class content loaded
     * @var string
     */
    protected $extends = '\BackBuilder\ClassContent\AClassContent';
    
    /**
     * The doctrine repository associated to the class content loaded
     * @var string
     */
    protected $repository = 'BackBuilder\ClassContent\Repository\ClassContentRepository';
    
    /**
     * The elements of the class content
     * @var array
     */
    protected $elements;
    
    /**
     * The namespace of the class content loaded
     * @var string
     */
    protected $namespace = "BackBuilder\ClassContent";
    
    /**
     * the user parameters of the class content
     * @var array
     */
    protected $parameters;
    
    /**
     * the properties of the class content
     * @var array
     */
    protected $properties;
    
    /**
     * Default php template to build the class file
     * @var string
     */
    protected $template =
'<?php
namespace <namespace>;

/**
 * @Entity(repositoryClass="<repository>")
 * @Table(name="content")
 * @HasLifecycleCallbacks
 */
class <classname> extends <extends> {
    public function __construct($uid = NULL, $options = NULL) {
        parent::__construct($uid, $options);
        $this->_initData();
    }

    protected function _initData() {
        <defineDatas>
        <defineParam>
        <defineProps>
        parent::_initData();
    }
}
';

    protected $_cache;
    
    /**
     * Class constructor
     * Retreive the registered BackBuilder autoloader
     */
    public function __construct()
    {
        foreach(spl_autoload_functions() as $autoloader) {
            if (true === is_array($autoloader) && $autoloader[0] instanceof \BackBuilder\AutoLoader\AutoLoader) {
                $this->_autoloader = $autoloader[0];
                break;
            }
        }
        
        if (NULL !== $this->_autoloader && NULL !== $this->_autoloader->getApplication()) {
            $this->_cache = $this->_autoloader->getApplication()->getBootstrapCache();
        }
    }
    
    /**
     * Build the php code corresponding to the loading class
     *
     * @return string The generated php code
     */
    protected function _buildClass() {
        $defineDatas = $this->_extractDatas($this->elements);
        $defineParam = $this->_extractDatas($this->parameters);
        $defineProps = $this->properties;
        
        array_walk($defineDatas, function (&$value, $key) { $value = "->_defineData('".$key."', '".$value['type']."', ".var_export($value['options'], TRUE).")"; } );
        array_walk($defineParam, function (&$value, $key) { $value = "->_defineParam('".$key."', '".$value['type']."', ".var_export($value['options'], TRUE).")"; } );
        array_walk($defineProps, function (&$value, $key) { $value = "->_defineProperty('".$key."', ".var_export($value, TRUE).")"; } );
        
        $phpCode = str_replace(array('<namespace>',
                                     '<classname>',
                                     '<repository>',
                                     '<extends>',
                                     '<defineDatas>',
                                     '<defineParam>',
                                     '<defineProps>'),
                               array($this->namespace,
                                     $this->classname,
                                     $this->repository,
                                     $this->extends,
                                     (0 < count($defineDatas)) ? '$this'.implode('', $defineDatas).';' : '',
                                     (0 < count($defineParam)) ? '$this'.implode('', $defineParam).';' : '',
                                     (0 < count($defineProps)) ? '$this'.implode('', $defineProps).';' : ''),
                               $this->template);
       
        return $phpCode;
    }
    
    /**
     * Checks for a normalize var name
     *
     * @param string $var The var name to check
     * @throws ClassWrapperException Occurs for a syntax error
     */
    protected function _normalizeVar($var, $includeSeparator = false) {
        if ($includeSeparator)
            $var = explode(NAMESPACE_SEPARATOR, $var);
        
        $vars = (array) $var;
        
        $pattern = "/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";
        
        foreach($vars as $var) {
            if ($var != '' && !preg_match($pattern, $var))
                throw new ClassWrapperException(sprintf('Syntax error: \'%s\'', $var));
        }
        
        return implode(($includeSeparator) ? NAMESPACE_SEPARATOR : '', $vars);
    }

    /**
     * @see IClassWrapper::stream_close()
     */
    public function stream_close() {}
    
    /**
     * @see IClassWrapper::stream_eof()
     */
    public function stream_eof() {
        return $this->_pos >= strlen($this->_data);
    }
    
    /**
     * @see IClassWrapper::stream_read()
     */
    public function stream_read($count) {
        $ret = substr($this->_data, $this->_pos, $count);
        $this->_pos += strlen($ret);
        return $ret;
    }
    
    /**
     * @see IClassWrapper::stream_seek()
     */
    public function stream_seek($offset, $whence = \SEEK_SET) {
        switch ($whence) {
            case \SEEK_SET:
                if ($offset < strlen($this->_data) && $offset >= 0) {
                    $this->_pos = $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case \SEEK_CUR:
                if ($offset >= 0) {
                    $this->_pos += $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case \SEEK_END:
                if (strlen($this->_data) + $offset >= 0) {
                    $this->_pos = strlen($this->_data) + $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            default:
                return false;
        }
    }
    
    /**
     * @see IClassWrapper::stream_stat()
     */
    public function stream_stat() {
        return $this->_stat;
    }
    
    /**
     * @see IClassWrapper::stream_tell()
     */
    public function stream_tell() {
        return $this->_pos;
    }
    
    /**
     * @see IClassWrapper::url_stat()
     */
    public function url_stat($path, $flags) {
        return $this->_stat;
    }
    
    /**
     * Extract and format datas from parser
     * @param array $datas
     * @return the extracted datas
     */
    abstract protected function _extractDatas($datas);

    /**
     * @see IClassWrapper::glob()
     */
    abstract public function glob($pattern);
}