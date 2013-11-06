<?php

namespace BackBuilder\Util;

class File
{

    /**
     * Acceptable prefices of SI
     * @var array
     */
    protected static $_prefixes = array('', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

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
        $realname = realpath($filename);

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

        if (FALSE !== $realname = realpath($filename))
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

    public static function getExtension($filename, $withDot = true)
    {
        $filename = basename($filename);
        if (false === strpos($filename, '.'))
            return '';

        return substr($filename, strrpos($filename, '.') - strlen($filename) + ($withDot ? 0 : 1));
    }

}