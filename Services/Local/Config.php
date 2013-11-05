<?php

namespace BackBuilder\Services\Local;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author h.baptiste
 */
class Config extends AbstractServiceLocal
{

    /**
     * @exposed(secured=true)
     */
    public function getRTEConfig()
    {
        $config = $this->bbapp->getConfig();
        $config = $config->getSection("rteconfig");
        return $config;
    }

}

?>
