<?php

namespace BackBuilder\NestedNode\Repository;

use Doctrine\ORM\EntityRepository;
use BackBuilder\NestedNode\Page;
use BackBuilder\NestedNode\PageRevision;

/**
 */
class PageRevisionRepository extends EntityRepository {

    public function getCurrent(Page $page) {
        try {
            $q = $this->createQueryBuilder('r')
                    ->andWhere('r._page = :page')
                    ->andWhere('r._version = :version')
                    ->orderBy('r._id', 'DESC')
                    ->setParameters(array(
                        'page' => $page,
                        'version' => PageRevision::VERSION_CURRENT
                    ))
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

}