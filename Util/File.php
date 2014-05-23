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

namespace BackBuilder\Util;

use BackBuilder\Exception\BBException,
    BackBuilder\Exception\InvalidArgumentsException;

/**
 * Set of utility methods to deal with files
 *
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class File
{

    /**
     * Acceptable prefices of SI
     * @var array
     */
    protected static $_prefixes = array('', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

    /**
     * Returns canonicalized absolute pathname
     * @param string $path
     * @return boolean|string
     */
    public static function realpath($path)
    {
        if (false === $parse_url = parse_url($path)) {
            return false;
        }

        if (false === array_key_exists('host', $parse_url)) {
            return realpath($path);
        }

        if (false === array_key_exists('path', $parse_url)) {
            return false;
        }

        $parts = array();
        foreach (explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $parse_url['path'])) as $part) {
            if ('.' === $part) {
                continue;
            } elseif ('..' === $part) {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        $path = (isset($parse_url['scheme']) ? $parse_url['scheme'] . '://' : '') .
                (isset($parse_url['user']) ? $parse_url['user'] : '') .
                (isset($parse_url['pass']) ? ':' . $parse_url['pass'] : '') .
                (isset($parse_url['user']) || isset($parse_url['pass']) ? '@' : '') .
                (isset($parse_url['host']) ? $parse_url['host'] : '') .
                (isset($parse_url['port']) ? ':' . $parse_url['port'] : '') .
                implode('/', $parts);

        if (false === file_exists($path)) {
            return false;
        }

        return $path;
    }

    /**
     * Normalize a file path according to the system characteristics
     * @param string $filepath the path to normalize
     * @param string $separator The directory separator to use
     * @param boolean $removeTrailing Removing trailing separators to the end of path
     * @return string The normalize file path
     */
    public static function normalizePath($filepath, $separator = DIRECTORY_SEPARATOR, $removeTrailing = TRUE)
    {
        $patterns = array('/\//', '/\\\\/', '/' . str_replace('/', '\/', $separator) . '+/');
        $replacements = array_fill(0, 3, $separator);

        if (TRUE === $removeTrailing) {
            $patterns[] = '/' . str_replace('/', '\/', $separator) . '$/';
            $replacements[] = '';
        }

        return preg_replace($patterns, $replacements, $filepath);
    }

    /**
     * Tranformation to human-readable format
     * @param  int $size Size in bytes
     * @param  int $precision Presicion of result (default 2)
     * @return string Transformed size
     */
    public static function readableFilesize($size, $precision = 2)
    {
        $result = $size;
        $index = 0;
        while ($result > 1024 && $index < count(self::$_prefixes)) {
            $result = $result / 1024;
            $index++;
        }

        return sprintf('%1.' . $precision . 'f %sB', $result, self::$_prefixes[$index]);
    }

    /**
     * Try to find the real path to the provided file name
     * Can be invoke by array_walk()
     * @param string $filename The reference to the file to looking for
     * @param string $key The optionnal array key to be invoke by array_walk
     * @param array $options optionnal options to
     * 				  - include_path The path to include directories
     * 				  - base_dir The base directory
     */
    public static function resolveFilepath(&$filename, $key = NULL, $options = array())
    {
        $filename = self::normalizePath($filename);
        $realname = self::realpath($filename);

        if ($filename != $realname) {
            $basedir = (array_key_exists('base_dir', $options)) ? self::normalizePath($options['base_dir']) : '';

            if (array_key_exists('include_path', $options)) {
                foreach ((array) $options['include_path'] as $path) {
                    $path = self::normalizePath($path);
                    if (!is_dir($path))
                        $path = ($basedir) ? $basedir . DIRECTORY_SEPARATOR : '' . $path;

                    if (file_exists($path . DIRECTORY_SEPARATOR . $filename)) {
                        $filename = $path . DIRECTORY_SEPARATOR . $filename;
                        break;
                    }
                }
            } else if ('' != $basedir) {
                $filename = $basedir . DIRECTORY_SEPARATOR . $filename;
            }
        }

        if (FALSE !== $realname = self::realpath($filename))
            $filename = $realname;
    }

    public static function resolveMediapath(&$filename, $key = NULL, $options = array())
    {
        $matches = array();
        if (preg_match('/^(.*)([a-z0-9]{32})\.(.*)$/i', $filename, $matches)) {
            $filename = $matches[1] . implode(DIRECTORY_SEPARATOR, str_split($matches[2], 4)) . '.' . $matches[3];
        }

        self::resolveFilepath($filename, $key, $options);
    }

    /**
     * Returns the extension file base on its name
     * @param string $filename
     * @param Boolean $withDot
     * @return string
     */
    public static function getExtension($filename, $withDot = true)
    {
        $filename = basename($filename);
        if (false === strrpos($filename, '.')) {
            return '';
        }

        return substr($filename, strrpos($filename, '.') - strlen($filename) + ($withDot ? 0 : 1));
    }
    
    /**
     * Removes the extension file from its name
     * @param string $filename
     * @return string
     */
    public static function removeExtension($filename)
    {
        if (false === strrpos($filename, '.')) {
            return $filename;
        }

        return substr($filename, 0, strrpos($filename, '.'));
    }
    
    /**
     * Makes directory
     * @param string $path The directory path
     * @return boolean Returns TRUE on success
     * @throws \BackBuilder\Exception\InvalidArgumentsException Occurs if directory already 
     *                                                         exists or cannot be made
     */
    public static function mkdir($path)
    {
        if (true === is_dir($path)) {
            throw new InvalidArgumentsException(sprintf('Directory `%s` already exists.', $path));
        }

        if (false === @mkdir($path, 0755, true)) {
            throw new InvalidArgumentsException(sprintf('Enable to make directory `%s`.', $path));
        }

        return true;
    }

    /**
     * Copies file
     * @param string $from The source file path
     * @param string $to The target file path
     * @return boolean Returns TRUE on success
     * @throws \BackBuilder\Exception\InvalidArgumentsException Occurs if either $from or $to is invalid
     * @throws \BackBuilder\Exception\BBException Occurs if the copy can not be done
     */
    public static function copy($from, $to)
    {
        if (false === $frompath = self::realpath($from)) {
            throw new InvalidArgumentsException(sprintf('The file `%s` cannot be accessed', $from));
        }

        if (false === is_readable($frompath) || true === is_dir($frompath)) {
            throw new InvalidArgumentsException(sprintf('The file `%s` doesn\'t exist or cannot be read', $from));
        }

        $topath = self::normalizePath($to);
        if (false === is_writable(dirname($topath))) {
            self::mkdir(dirname($topath));
        }

        if (false === @copy($frompath, $topath)) {
            throw new BBException(sprintf('Enable to copy file `%s` to `%s`.', $from, $to));
        }

        return true;
    }

    /**
     * Moves file
     * @param string $from The source file path
     * @param string $to The target file path
     * @return boolean Returns TRUE on success
     * @throws \BackBuilder\Exception\InvalidArgumentsException Occurs if either $from or $to is invalid
     * @throws \BackBuilder\Exception\BBException Occurs if $from can not be deleted
     */
    public static function move($from, $to)
    {
        if (false === $frompath = self::realpath($from)) {
            throw new InvalidArgumentsException(sprintf('The file `%s` cannot be accessed', $from));
        }

        if (false === is_writable($frompath) || true === is_dir($frompath)) {
            throw new InvalidArgumentsException(sprintf('The file `%s` doesn\'t exist or cannot be write', $from));
        }

        self::copy($from, $to);

        if (false === @unlink($frompath)) {
            throw new BBException(sprintf('Enable to delete file `%s`.', $from));
        }

        return true;
    }

    /**
     * Looks recursively in $basedir for files with $extension
     * @param string $basedir
     * @param string $extension
     * @return array
     * @throws \BackBuilder\Exception\InvalidArgumentException Occures if $basedir is unreachable
     */
    public static function getFilesRecursivelyByExtension($basedir, $extension)
    {
        if (false === is_readable($basedir)) {
            throw new \BackBuilder\Exception\InvalidArgumentException(sprintf('Cannot read the directory %s', $basedir));
        }

        $files = array();
        $parse_url = parse_url($basedir);
        if (false !== $parse_url && isset($parse_url['scheme'])) {
            $directory = new \RecursiveDirectoryIterator($basedir);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.' . $extension . '$/i', \RecursiveRegexIterator::GET_MATCH);

            foreach ($regex as $file) {
                $files[] = $file[0];
            }
        } else {
            $pattern = '';
            foreach (str_split($extension) as $letter) {
                $pattern .= '[' . strtolower($letter) . strtoupper($letter) . ']';
            }

            $pattern = $basedir . DIRECTORY_SEPARATOR . '{*,*' . DIRECTORY_SEPARATOR . '*}.' . $pattern;
            $files = glob($pattern, GLOB_BRACE);
        }

        return $files;
    }
    
    /**
     * Extracts a zip archive into a specified directory
     * 
     * @param $file - zip archive file
     * @param type $destinationDir - where the files will be extracted to
     * @param bool $createDir - should destination dir be created if it doesn't exist
     * @throws \Exception
     */
    public static function extractZipArchive($file, $destinationDir, $createDir = false)
    {
        if(!file_exists($destinationDir)) {
            if(false == $createDir) {
                throw new \Exception('Destination directory does not exist: ' . $destinationDir);
            }
            
            $res = mkdir($destinationDir, 0777, true);
            if(false === $res) {
                throw new \Exception('Destination directory cannot be created: ' . $destinationDir);
            }
            
            if(!is_readable($destinationDir)) {
                throw new \Exception('Destination directory is not readable: ' . $destinationDir);
            }
        } elseif(!is_dir($destinationDir)) {
            throw new \Exception('Destination directory cannot be created as a file with that name already exists: ' . $destinationDir);
        }
        
        $archive = new \ZipArchive();
        
        if(false === $archive->open($file)) {
            throw new \Exception('Could not open archive: ' . $archive);
        }
        
        if(false === $archive->extractTo($destinationDir) ) {
            throw new \Exception('Could not extract archive: ' . $archive);
        }
        
        $archive->close();
    }

}
