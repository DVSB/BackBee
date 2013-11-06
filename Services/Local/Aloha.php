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
    
    
    /**
     * @codeCoverageIgnore
     * @param type $data
     */
    private function setStdObjectAloha($data)
    {
        $stdClass = new \stdClass();
    }
    
    /**
     * @exposed(secured=true)
     * @codeCoverageIgnore
     */
    public function getAlohaPluginsTbale()
    {
        $config = $this->bbapp->getConfig()->getAlohaConfig();
        return $config;
    }
}

?>
