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

namespace BackBee\Importer\Reader;

use SplFileObject;

/**
 * CSV file reader.
 *
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class CsvReader implements \Countable, \SeekableIterator
{
    /**
     * CSV file object.
     *
     * @var SplFileObject
     */
    protected $file;

    /**
     * Count of non-empty rows.
     *
     * @var int
     */
    protected $count;

    /**
     * CSV column headers.
     *
     * @var array
     */
    protected $headers;

    /**
     * Count of column headers.
     *
     * @var int
     */
    protected $headersCount;

    /**
     * The position of the row containing column headers.
     *
     * @var int|null
     */
    protected $headersRowPosition;

    /**
     * Mappings for special values.
     *
     * Eg: array('NULL' => null, 'false' => false, , 'TRUE' => true)
     *
     * @var array
     */
    protected $valueMappings = array();

    /**
     * True $valueMappings contains any data.
     *
     * Cache value for performance
     *
     * @var boolean
     */
    protected $hasValueMappings = false;

    /**
     * @param string $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct($file = null, $delimiter = ';', $enclosure = '"', $escape = '\\')
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File does not exist: %s', $file));
        }

        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('Supplied path is not a file: %s', $file));
        }

        if (!is_readable($file)) {
            throw new \InvalidArgumentException(sprintf('File cannot be reade: %s', $file));
        }

        $this->file = new SplFileObject($file);
        $this->file->setCsvControl($delimiter, $enclosure, $escape);
        $this->file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        $this->headersCount = count($this->headers);

        return $this;
    }

    /**
     * Get headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param int $headersRowPosition
     */
    public function setHeadersRowPosition($headersRowPosition)
    {
        $this->headersRowPosition = $headersRowPosition;

        // set headers
        $this->file->seek($headersRowPosition);
        $headers = $this->file->current();

        $this->setHeaders($headers);
    }

    /**
     * Count rows.
     *
     * @return int
     */
    public function count()
    {
        if (null === $this->count) {
            $currentPosition = $this->key();

            $this->count = iterator_count($this);

            $this->seek($currentPosition);
        }

        return $this->count;
    }

    /**
     * Return the current row converted to an associate array.
     *
     * @return String[]
     */
    public function current()
    {
        $row = $this->file->current();

        if (count($this->headers) > 0) {
            // normalise the row
            if ($this->headersCount > count($row)) {
                // too little items in row
                $row = array_pad($row, $this->headersCount, null);
            } else {
                // too many items in row
                $row = array_slice($row, 0, $this->headersCount);
            }

            // value mappings
            if ($this->hasValueMappings) {
                $mappings = $this->valueMappings;
                $row = array_map(function ($value) use ($mappings) {
                    if (\array_key_exists($value, $mappings)) {
                        return $mappings[$value];
                    }

                    return $value;
                }, $row);
            }

            $row = array_combine($this->headers, $row);
        }

        return $row;
    }

    /**
     * Rewind the file pointer.
     *
     * If $headersRowPosition is set, rewinds to $headersRowPosition + 1
     */
    public function rewind()
    {
        $this->file->rewind();

        if (null !== $this->headersRowPosition) {
            $this->file->seek($this->headersRowPosition + 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->file->next();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->file->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->file->key();
    }

    /**
     * {@inheritdoc}
     */
    public function seek($pointer)
    {
        $this->file->seek($pointer);
    }

    /**
     * @param array $valueMappings Eg: array('NULL' => null, 'false' => false, , 'TRUE' => true)
     */
    public function setValueMappings(array $valueMappings)
    {
        // break down to usable values for performance
        $this->valueMappings = $valueMappings;
        $this->hasValueMappings = count($this->valueMappings) > 0;
    }
}
