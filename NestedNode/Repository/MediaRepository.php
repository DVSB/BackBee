<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\NestedNode\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Media repository
 * 
 * @category    BackBuilder
 * @package     BackBuilder/NestedNode
 * @subpackage  Repository
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class MediaRepository extends EntityRepository
{

    public function getMedias(\BackBuilder\NestedNode\MediaFolder $mediafolder, $cond, $order_sort = '_title', $order_dir = 'asc', $paging = array())
    {
        $result = null;
        $q = $this->createQueryBuilder('m')
                ->leftJoin('m._media_folder', 'mf')
                ->leftJoin('m._content', 'mc')
                ->andWhere('mf._root = :root_' . $mediafolder->getUid())
                ->andWhere('mf._leftnode >= :leftnode_' . $mediafolder->getUid())
                ->andWhere('mf._rightnode <= :rightnode_' . $mediafolder->getUid())
                ->orderBy('m.' . $order_sort, $order_dir)
                ->setParameters(array(
            'root_' . $mediafolder->getUid() => $mediafolder->getRoot(),
            'leftnode_' . $mediafolder->getUid() => $mediafolder->getLeftnode(),
            'rightnode_' . $mediafolder->getUid() => $mediafolder->getRightnode()
                ));

        $typeField = (isset($cond['typeField']) && "all" != $cond['typeField']) ? $cond['typeField'] : NULL;
        if (NULL != $typeField)
            $q->andWhere('mc INSTANCE OF ' . $typeField);

        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : NULL;
        if (NULL != $searchField)
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%' . $searchField . '%')));

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $q->andWhere('mc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $q->andWhere('mc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));

        if (is_array($paging)) {
            if (array_key_exists("start", $paging) && array_key_exists("limit", $paging)) {
                $q->setFirstResult($paging["start"])
                        ->setMaxResults($paging["limit"]);
                $result = new \Doctrine\ORM\Tools\Pagination\Paginator($q);
            }
        } else {
            $result = $q->getQuery()->getResult();
        }
        return $result;
    }

    public function delete(\BackBuilder\NestedNode\Media $media)
    {
        return false;
    }

    public function countMedias(\BackBuilder\NestedNode\MediaFolder $mediafolder, $cond = array())
    {
        $q = $this->createQueryBuilder("m")
                ->select("COUNT(m)")
                ->leftJoin('m._media_folder', 'mf')
                ->leftJoin('m._content', 'mc')
                ->andWhere('mf._root = :root_' . $mediafolder->getUid())
                ->andWhere('mf._leftnode >= :leftnode_' . $mediafolder->getUid())
                ->andWhere('mf._rightnode <= :rightnode_' . $mediafolder->getUid())
                ->setParameters(array(
            'root_' . $mediafolder->getUid() => $mediafolder->getRoot(),
            'leftnode_' . $mediafolder->getUid() => $mediafolder->getLeftnode(),
            'rightnode_' . $mediafolder->getUid() => $mediafolder->getRightnode()
                ));

        $typeField = (isset($cond['typeField']) && "all" != $cond['typeField']) ? $cond['typeField'] : NULL;
        if (NULL != $typeField)
            $q->andWhere('mc INSTANCE OF ' . $typeField);

        $searchField = (isset($cond['searchField'])) ? $cond['searchField'] : NULL;
        if (NULL != $searchField)
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%' . $searchField . '%')));

        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $q->andWhere('mc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));

        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $q->andWhere('mc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));

        return $q->getQuery()->getSingleScalarResult();
    }

    public function getMediasByFolder(\BackBuilder\NestedNode\MediaFolder $mediafolder)
    {
        $result = null;
        $q = $this->createQueryBuilder('m')
                ->leftJoin('m._media_folder', 'mf')
                ->leftJoin('m._content', 'mc')
                ->andWhere('mf._root = :root_' . $mediafolder->getUid())
                ->andWhere('mf._leftnode >= :leftnode_' . $mediafolder->getUid())
                ->andWhere('mf._rightnode <= :rightnode_' . $mediafolder->getUid())
                ->setParameters(array(
            'root_' . $mediafolder->getUid() => $mediafolder->getRoot(),
            'leftnode_' . $mediafolder->getUid() => $mediafolder->getLeftnode(),
            'rightnode_' . $mediafolder->getUid() => $mediafolder->getRightnode()
                ));

        $result = $q->getQuery()->getResult();
        return $result;
    }

}
