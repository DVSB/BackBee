<?php

namespace BackBuilder\Util;

class ArrayPaginator implements \Countable, \IteratorAggregate
{

    private $_collection;
    private $_current_page;
    private $_page_size;

    public static function paginate(array $collection, $page = 1, $page_size = 1)
    {
        $self = new ArrayPaginator($collection, $page, $page_size);
        return $self;
    }

    public function __construct(array $collection, $page, $page_size)
    {

        $this->_page_size = (int) $page_size;
        $this->_current_page = (int) $page;
        $this->_collection = array_chunk($collection, (int) $page_size, true);
    }

    /**
     * @codeCoverageIgnore
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_collection[$this->_current_page]);
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function count()
    {
        return count($this->_collection);
    }

    public function getNextPageNumber()
    {
        if ($this->_current_page + 1 > ($this->count() - 1)) {
            return $this->count() - 1;
        } else {
            return $this->_current_page + 1;
        }
    }

    public function getPreviousPageNumber()
    {
        if ($this->_current_page - 1 < 0) {
            return 0;
        } else {
            return $this->_current_page - 1;
        }
    }

    public function isNextPage()
    {
        if ($this->_current_page + 1 > ($this->count() - 1)) {
            return false;
        } else {
            return true;
        }
    }

    public function isPreviousPage()
    {
        if ($this->_current_page - 1 < 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCurrentPageNumber()
    {
        return $this->_current_page;
    }

}