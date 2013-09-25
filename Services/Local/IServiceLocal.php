<?php

namespace BackBuilder\Services\Local;

use BackBuilder\BBApplication;
/**
 * Interface for local RPC service
 * @category    BackBuilder
 * @package     BackBuilder\Services\Local
 * @copyright   Lp digital system
 * @author      n.bremont
 */
interface IServiceLocal
{

    /**
     * Class constructor
     * @param \BackBuilder\BBApplication $bbapp
     */
    public function __construct();
    
    public function initService(BBApplication $application);
}