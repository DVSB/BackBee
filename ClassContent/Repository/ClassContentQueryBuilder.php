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
namespace BackBuilder\ClassContent\Repository;

use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Site;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * AClassContent repository
 *
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @subpackage  Repository\Element
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
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
        'AClassContent' => 'BackBuilder\ClassContent\AClassContent',
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

    /**
     * Add site filter to the query
     *
     * @param $site mixed (BackBuilder/Site/Site|String)
     */
    public function addSiteFilter($site)
    {
        if ($site instanceof Site) {
            $site = $site->getUid();
        }
        $this->andWhere(
            'cc._uid IN (SELECT i.content_uid FROM BackBuilder\ClassContent\Indexes\IdxSiteContent i WHERE i.site_uid = :site_uid)'
        )->setParameter('site_uid', $site);
    }

    /**
     * Set contents uid as filter.
     *
     * @param $uids array
     */
    public function addUidsFilter(array $uids)
    {
        $this->andWhere('cc._uid in(:uids)')->setParameter('uids', $uids);
    }

    /**
     * Add limite to onlinne filter
     */
    public function limitToOnline()
    {
        $this->leftJoin('cc._mainnode', 'mp');
        $this->andWhere('mp._state IN (:states)')
             ->setParameter('states', array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN));
        $this->andWhere('mp._publishing < :today OR mp._publishing IS NULL')
             ->setParameter('today', new \DateTime());
    }

    /**
     * Set a page to filter the query on a nested portion
     *
     * @param $page BackBuilder\NestedNode\Page
     */
    public function addPageFilter(Page $page)
    {
        if ($page && !$page->isRoot()) {
            $this->leftJoin('cc._mainnode', 'p')
               ->andWhere('p._root = :selectedPageRoot')
               ->andWhere('p._leftnode >= :selectedPageLeftnode')
               ->andWhere('p._rightnode <= :selectedPageRightnode')
               ->setParameters(array(
                    "selectedPageRoot" => $page->getRoot(),
                    "selectedPageLeftnode" => $page->getLeftnode(),
                    "selectedPageRightnode" => $page->getRightnode(),
                ));
        }
    }

    /**
     * Filter the query by keywords
     *
     * @param $keywords array
     */
    public function addKeywordsFilter($keywords)
    {
        $contentIds = $this->_em->getRepository('BackBuilder\NestedNode\KeyWord')
                                ->getContentsIdByKeyWords($keywords);
        if (is_array($contentIds) && !empty($contentIds)) {
            $this->andWhere('cc._uid in(:keywords)')->setParameter('keywords', $contentIds);
        }
    }

    /**
     * Filter by rhe classname descriminator
     *
     * @param $classes array
     */
    public function addClassFilter($classes)
    {
        if (is_array($classes) && count($classes) !== 0) {
            $filters = array();
            foreach ($classes as $class) {
                $filters[] = 'cc INSTANCE OF \''.$class.'\'';
            }
            $filter = implode(" OR ", $filters);

            $this->andWhere($filter);
        }
    }

    /**
     * Order with the indexation table
     *
     * @param $label string
     * @param $sort ('ASC'|'DESC')
     */
    public function orderByIndex($label, $sort = 'ASC')
    {
        $this->join('cc._indexation', 'idx')
             ->andWhere('idx._field = :sort')
             ->setParameter('sort', $label)
             ->orderBy('idx._value', $sort);
    }

    /**
     * Get Results paginated
     *
     * @param $start integer
     * @param $limit integer
     *
     * @return Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($start, $limit)
    {
        $this->setFirstResult($start)
             ->setMaxResults($limit);

        return new Paginator($this);
    }

    public function addTitleLike($expression)
    {
        if (NULL != $expression) {
            $this->andWhere(
                $this->expr()->like(
                    'cc._label',
                    $this->expr()->literal('%'.$expression.'%')
                )
            );
        }
    }

    private function getClass($key)
    {
        if (array_key_exists($key, $this->classmap)) {
            return $this->classmap[$key];
        }
    }
}
