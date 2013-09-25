<?php
namespace BackBuilder\Services\Local;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Aloha
 * 
 * @author Nicolas BREMONT<nicolas.bremont@group-lp.com>
 */
class Aloha extends AbstractServiceLocal {
    
    
    private function setStdObjectAloha($data)
    {
        $stdClass = new \stdClass();
    }
    
    /**
     * @exposed(secured=true)
     * 
     */
    public function getAlohaPluginsTbale()
    {
        $config = $this->bbapp->getConfig()->getAlohaConfig();
        return $config;
    }
}

?>
