<?php
namespace BackBuilder\Services\Local;

class Bundle extends AbstractServiceLocal {
    /**
     * @exposed(secured=true)
     * 
     */
    public function findAll() {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);
        
        $result = array();
        foreach($this->bbapp->getBundles() as $bundle) {
            $result[] = json_decode($bundle->serialize());
        }
        
        return $result;
    }
}
