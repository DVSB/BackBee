<?php
namespace BackBuilder\Logging\Repository;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use BackBuilder\Logging\AdminLog;

/**
 */
class AdminLogRepository extends EntityRepository
{
    public function log($owner, $classname, $method, $entity)
    {
        $log = new AdminLog();
        $log->setOwner($owner)
            ->setAction($method)
            ->setController($classname);
        if ($entity !== null) {
            $log->setEntity($entity);
        }
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush($log);
    }
    
    public function countOtherUserInTheSamePage($controller, $action, $entity) 
    {
        $query = $this->createQueryBuilder('al')
                      ->select('al._owner, al._entity')
                      ->where('al._controller = :controller')
                      ->setParameter(':controller', $controller)
                      ->andWhere('al._action = :action')
                      ->setParameter(':action', $action)
                      ->andWhere('al._entity = :entity')
                      ->setParameter(':entity', ObjectIdentity::fromDomainObject($entity))
                      ->andWhere('al._created_at > :date')
                      ->setParameter(':date', new \DateTime('@' . strtotime('-30 minutes')))
                      ->orderBy('al._created_at', 'DESC')
                      ->groupBy('al._owner')
                      ->getQuery();
        
        $verif = $this->_getActualAdminEdition();
        $return = $query->getResult();
        foreach ($return as $key => $result) {
            if (
                array_key_exists($result['_owner'], $verif) &&
                $verif[$result['_owner']] != $result['_entity']
            ) {
                unset($return[$key]);
            }
        }
        return count($return);
    }
    
    public function getLastEditedContent(){
        $query = $this->createQueryBuilder("al")
                ->where("al._action=:action")->setParameter("action","subscriberEdit")
                ->andWhere("al._entity IS NOT NULL")
                ->setMaxResults(1)
                ->orderBy("al._created_at","DESC");
        $query_result = $query->getQuery()->getResult();
        $result = reset($query_result);
        return $result;
    }
    
    private function _getActualAdminEdition()
    {
        $query = $this->createQueryBuilder('al')
                      ->select('al._owner, al._entity')
                      ->andWhere('al._created_at > :date')
                      ->setParameter(':date', new \DateTime('@' . strtotime('-30 minutes')))
                      ->orderBy('al._created_at', 'DESC')
                      ->groupBy('al._owner')
                      ->getQuery();
        $verif = array();
        foreach ($query->getResult() as $result) {
            $verif[$result['_owner']] = $result['_entity'];
        }
        return $verif;
    }
}