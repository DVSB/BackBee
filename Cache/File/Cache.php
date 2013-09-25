<?php

namespace BackBuilder\Cache\File;

use BackBuilder\Cache\ACache,
    BackBuilder\BBApplication,
    BackBuilder\Cache\Exception\CacheException;

/**
 * Filesystem cache adapter
 * 
 * A simple cache system on file, it does not provide tag or expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache\File
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class Cache extends ACache
{

    /**
     * The current BackBuilder application
     * @var \BackBuilder\BBApplication
     */
    private $_application;

    /**
     * The cache directory
     * @var string
     */
    private $_cachedir;

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $application BackBuilder application
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;
        $this->_setCacheDir();
    }

    /**
     * Returns the available cache for the given id if found returns false else
     * @param string $id Cache id
     * @param boolean $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire Optionnal, the expiration time (now by default)
     * @return string|FALSE
     */
    public function load($id, $bypassCheck = false, \DateTime $expire = null)
    {
        if (false === $cachefile = $this->_getCacheFile($id)) {
            return false;
        }

        if (null === $expire) {
            $expire = new \DateTime();
        }

        $last_timestamp = $this->test($id);
        if (true === $bypassCheck
                || false === $last_timestamp
                || $expire->getTimestamp() <= $last_timestamp) {
            return file_get_contents($cachefile);
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id)
     * @param string $id Cache id
     * @return int|FALSE the last modified timestamp of the available cache record
     */
    public function test($id)
    {
        if (false === $cachefile = $this->_getCacheFile($id)) {
            return false;
        }

        if (false === $stat = stat($cachefile)) {
            return false;
        }

        return $stat['mtime'];
    }

    /**
     * Save some string datas into a cache record
     * @param string $id Cache id
     * @param string $data Datas to cache
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @param string $tag Optional, an associated tag to the data stored
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null)
    {
        if (null !== $lifetime || null !== $tag) {
            $this->_application->warning(sprintf('Lifetime and tag feature are not available for cache %s', get_class($this)));
        }

        $cachefile = $this->_cachedir . DIRECTORY_SEPARATOR . $id;
        return (false !== @file_put_contents($cachefile, $data));
    }

    /**
     * Removes a cache record
     * @param  string $id Cache id
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function remove($id)
    {
        if (false === $cachefile = $this->_getCacheFile($id)) {
            return false;
        }

        if (false === is_writable($cachefile)) {
            return false;
        }

        return @unlink($cachefile);
    }

    /**
     * Clears all cache records
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    public function clear()
    {
        if (true === \BackBuilder\Util\Dir::delete($this->_cachedir)) {
            $this->_setCacheDir();
            return true;
        }

        return false;
    }

    /**
     * Sets the cache directory, depending on the optional context of the
     * current BackBuilder application
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the cache directory can not be created
     */
    private function _setCacheDir()
    {
        $this->_cachedir = $this->_application->getCacheDir();

        if (null !== $this->_application->getContext()) {
            $this->_cachedir .= DIRECTORY_SEPARATOR . $this->_application->getContext();
        }

        if (false === is_dir($this->_cachedir)
                && false === @mkdir($this->_cachedir, 0700, true)) {
            throw new CacheException(sprintf('Unable to create the cache directory `%s`.', $this->_cachedir));
        }
    }

    /**
     * Returns the cache file if a cache is available for the given id
     * @param string $id Cache id
     * @return string|FALSE The file path of the available cache record
     */
    private function _getCacheFile($id)
    {
        $cachefile = $this->_cachedir . DIRECTORY_SEPARATOR . $id;

        if (false === file_exists($cachefile)
                || false === is_readable($cachefile)) {
            return false;
        }

        return $cachefile;
    }

}