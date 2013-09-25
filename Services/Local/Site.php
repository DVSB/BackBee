<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal;

/**
 * Description of Site
 * 
 * @copyright   Lp system
 * @author      m.baptista
 */
class Site extends AbstractServiceLocal {

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorList() {
        $sites = array();
        $em = $this->bbapp->getEntityManager();

        $q = $em->getRepository('\BackBuilder\Site\Site')->createQueryBuilder('s')
                ->orderBy('s._label', 'ASC')
                ->getQuery();

        foreach ($q->getResult() as $site) {
            $sites[$site->getUid()] = $site->getLabel();
        }

        return $sites;
    }

}
