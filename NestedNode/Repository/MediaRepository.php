<?php

namespace BackBuilder\NestedNode\Repository;

use Doctrine\ORM\EntityRepository;
use BackBuilder\NestedNode\Media;

/**
 * 20d9c06700dfb9832f7fa99c390dcb8d
 */
class MediaRepository extends EntityRepository {

    public function getMedias(\BackBuilder\NestedNode\MediaFolder $mediafolder, $cond, $order_sort = '_title', $order_dir = 'asc', $paging = array()) {
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
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%'.$searchField.'%')));
        
        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $q->andWhere('mc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));
        
        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $q->andWhere('mc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));
        
        if(is_array($paging)){
            if(array_key_exists("start", $paging) && array_key_exists("limit", $paging)){
               $q->setFirstResult($paging["start"])
                 ->setMaxResults($paging["limit"]);
                $result = new \Doctrine\ORM\Tools\Pagination\Paginator($q);
            }
        } else {
            $result = $q->getQuery()->getResult();
        }
        return $result;
    }

    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\NestedNode\Media $media
     * @return boolean
     */
    public function delete(\BackBuilder\NestedNode\Media $media) {
        return false;
    }

    public function countMedias(\BackBuilder\NestedNode\MediaFolder $mediafolder, $cond = array()) {
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
            $q->andWhere($q->expr()->like('mc._label', $q->expr()->literal('%'.$searchField.'%')));
        
        $afterPubdateField = (isset($cond['afterPubdateField'])) ? $cond['afterPubdateField'] : NULL;
        if (NULL != $afterPubdateField)
            $q->andWhere('mc._modified > :afterPubdateField')->setParameter('afterPubdateField', date('Y/m/d', $afterPubdateField));
        
        $beforePubdateField = (isset($cond['beforePubdateField'])) ? $cond['beforePubdateField'] : NULL;
        if (NULL != $beforePubdateField)
            $q->andWhere('mc._modified < :beforePubdateField')->setParameter('beforePubdateField', date('Y/m/d', $beforePubdateField));
        
        return $q->getQuery()->getSingleScalarResult();
    }

    public function getMediasByFolder(\BackBuilder\NestedNode\MediaFolder $mediafolder) {
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
