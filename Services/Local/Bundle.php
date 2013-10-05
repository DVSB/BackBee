<?php

namespace BackBuilder\Services\Local;

class Bundle extends AbstractServiceLocal
{

    /**
     * @exposed(secured=true)
     * 
     */
    public function findAll()
    {
        if (NULL === $this->bbapp)
            throw new ServicesException("None BackBuilder Application provided", ServicesException::UNDEFINED_APP);

        $result = array();
        foreach ($this->bbapp->getBundles() as $bundle) {
            try {
                $this->isGranted('EDIT', $bundle);
                $result[] = json_decode($bundle->serialize());
            } catch (\BackBuilder\Security\Exception\ForbiddenAccessException $e) {
                continue;
            }
        }

        return $result;
    }

}
