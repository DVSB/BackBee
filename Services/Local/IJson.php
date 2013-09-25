<?php

namespace BackBuilder\Services\Local;

/**
 * Interface for object "jsonable"
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Services\Local
 * @copyright   Lp digital system
 * @author      Nicolas BREMONT <nicolas.bremont@group-lp.com>
 */
interface IJson
{

    /**
     * Returns a standard class representation of the object
     * @return \StdClass cannot return null
     */
    public function __toJson();
}