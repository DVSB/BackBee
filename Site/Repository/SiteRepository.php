<?php

namespace BackBuilder\Site\Repository;

use Doctrine\ORM\EntityRepository;

class SiteRepository extends EntityRepository
{
    public function findByServerName($server_name)
    {
        $q = $this->createQueryBuilder('s')
                  ->andWhere('s._server_name = :server_name')
                  ->setParameters(array('server_name' => $server_name));
        $theme = $q->getQuery()->getOneOrNullResult();

        return $theme;
    }
}