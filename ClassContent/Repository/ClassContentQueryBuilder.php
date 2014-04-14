<?php
namespace BackBuilder\ClassContent\Repository;

use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Site;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ClassContentQueryBuilder extends QueryBuilder
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;
    /**
     * @var array
     */
    private $classmap = array(
        'IdxSiteContent' => 'BackBuilder\ClassContent\Indexes\IdxSiteContent',
        'AClassContent' => 'BackBuilder\ClassContent\AClassContent'
    );

    /**
     * ClassContentQueryBuilder constructor
     * 
     * @param $em \Doctrine\ORM\EntityManager
     * @param $select \Doctrine\ORM\Query\Expr Use cc as identifier
     */
    public function __construct(EntityManager $em, Func $select = null)
    {
        $this->_em = $em;
        parent::__construct($em);
        $select = is_null($select) ? 'cc' : $select;
        $this->select($select)->distinct()->from($this->getClass('AClassContent'), 'cc');
    }

    public function addSiteFilter($site)
    {
        if ($site instanceof Site) {
            $site = $site->getUid();
        }
        $dql = 'SELECT i.content_uid FROM ' . $this->getClass('IdxSiteContent') . ' i WHERE i.site_uid = :site';
        $this->andWhere('cc._uid IN (' . $dql . ')')
             ->setParameter('site', $site);
    }

    public function addUidsFilter(array $uids)
    {
        $this->andWhere('cc._uid in(:uids)')->setParameter('uids', $uids);
    }

    public function limitToOnline()
    {
        $this->leftJoin('cc._mainnode', 'mp');
        $this->andWhere('mp._state IN (:states)')
             ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
    }

    public function addPageFilter(Page $page)
    {
        if ($page && !$page->isRoot()) {
            $query = $this->_em->createQueryBuilder('ct');
            $query->leftJoin('ct._parentcontent', 'sc')
                ->leftJoin('ct._pages', 'p')
                ->andWhere('p._root = :page_root')
                ->andWhere('p._leftnode >= :page_left_node')
                ->andWhere('p._rightnode <= :page_right_node')
                ->setParameters(
                    array(
                        'page_root' => $page->getRoot(),
                        'page_left_node' => $page->getLeftnode(),
                        'page_right_node' => $page->getRightnode()
                    )
                );
            $result = $query->getQuery()->getResult();

            $newQuery = $this->_em->createQueryBuilder('q');
            $newQuery->select('contents')
                     ->from($this->getClass('AClassContent'), 'contents')
                     ->leftJoin('contents._parentcontent', 'cs')
                     ->where('cs._uid IN (:scl)')
                     ->setParameter('scl', $result);

            $contents = $newQuery->getQuery()->getResult();

            $this->andWhere('cc in (:sc) ')->setParameter('sc', $contents);
        }
    }

    public function addKeywordsFilter($keywords)
    {
        $contentIds = $this->_em->getRepository('BackBuilder\NestedNode\KeyWord')
                                ->getContentsIdByKeyWords($keywords);
        if (is_array($contentIds) && !empty($contentIds)) {
            $this->andWhere('cc._uid in(:keywords)')->setParameter('keywords', $contentIds);
        }
    }

    public function addClassFilter($classes)
    {
        if (is_array($classes) && count($classes) !== 0) {
            // echo '<pre>';
            // var_dump($classes);
            // echo '</pre>';

            $filters = array();
            foreach ($classes as $class) {
                $filters[] = 'cc INSTANCE OF \'' . $class . '\'';
            }
            $filter = implode(" OR ", $filters);

            $this->andWhere($filter);
        }
    }

    public function orderByIndex($label, $sort = 'ASC')
    {
        $this->join('cc._indexation', 'idx')
             ->andWhere('idx._field = :sort')
             ->setParameter('sort', $label)
             ->orderBy('idx._value', $sort);
    }

    public function paginate($start, $limit)
    {
        $this->setFirstResult($start)
             ->setMaxResults($limit);
        return new Paginator($this);
    }

    private function getClass($key)
    {
        if (array_key_exists($key, $this->classmap)) {
            return $this->classmap[$key];
        }
    }
}
