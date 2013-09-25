<?php
namespace BackBuilder\Stream;

/**
 * Interface for the construction of new class wrappers
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Stream
 * @copyright   Lp system
 * @author      c.rouillon
 */
interface IStreamWrapper {
    /**
     * Renames a content
     * @see php.net/manual/en/book.stream.php
     */
    /*public function rename($path_from, $path_to);*/
    
    /**
     * Close an resource
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_close();
    
    /**
     * Tests for end-of-file on a resource
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_eof();
    
    /**
     * Opens a stream content
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_open($path, $mode, $options, &$opened_path);
    
    /**
     * Read from stream
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_read($count);
    
    /**
     * Seeks to specific location in a stream
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_seek($offset, $whence = \SEEK_SET);
    
    /**
     * Retrieve information about a stream
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_stat();
    
    /**
     * Retrieve the current position of a stream
     * @see php.net/manual/en/book.stream.php
     */
    public function stream_tell();
    
    /**
     * Write to stream
     * @see php.net/manual/en/book.stream.php
     */
    /*public function stream_write($data);*/
    
    /**
     * Delete a file
     * @see php.net/manual/en/book.stream.php
     */
    /*public function unlink($path);*/
    
    /**
     * Retrieve information about a stream
     * @see php.net/manual/en/book.stream.php
     */
    public function url_stat($path, $flags);
}