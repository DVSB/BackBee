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

namespace BackBuilder\NestedNode\Tests\Repository;

use BackBuilder\Tests\TestCase;
use BackBuilder\NestedNode\Repository\PageRepository;
use BackBuilder\NestedNode\Repository\PageQueryBuilder;
use BackBuilder\NestedNode\Page;
use BackBuilder\Site\Layout;

/**
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode\Tests\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageQueryBuilderTest extends TestCase
{

    /**
     * @var \BackBuilder\TestUnit\Mock\MockBBApplication
     */
    private $application;

    /**
     * @var \BackBuilder\NestedNode\Repository\PageRepository
     */
    private $repo;

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::hasJoinCriteria
     */
    public function testHasJoinCriteria()
    {
        $this->assertFalse(PageQueryBuilder::hasJoinCriteria());
        $this->assertFalse(PageQueryBuilder::hasJoinCriteria(array('_unknown' => 'fake')));
        $this->assertTrue(PageQueryBuilder::hasJoinCriteria(array('_root' => 'fake')));
        $this->assertTrue(PageQueryBuilder::hasJoinCriteria(array('_parent' => 'fake')));
        $this->assertTrue(PageQueryBuilder::hasJoinCriteria(array('_leftnode' => 'fake')));
        $this->assertTrue(PageQueryBuilder::hasJoinCriteria(array('_rightnode' => 'fake')));
        $this->assertTrue(PageQueryBuilder::hasJoinCriteria(array('_site' => 'fake')));
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsSection
     */
    public function testAndIsSection()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andIsSection();

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._section = p', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsOnline
     */
    public function testAndIsOnline()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andIsOnline();

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state IN (1,3) AND (p._publishing IS NULL OR p._publishing <= \'' . date(PageQueryBuilder::$config['dateSchemeForPublishing'], time()) . '\') AND (p._archiving IS NULL OR p._archiving > \'' . date(PageQueryBuilder::$config['dateSchemeForPublishing'], time()) . '\')', $q->getDql());

        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:s';
        $q->resetDQLPart('where')
                ->andIsOnline();
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state IN (1,3) AND (p._publishing IS NULL OR p._publishing <= \'' . date(PageQueryBuilder::$config['dateSchemeForPublishing'], time()) . '\') AND (p._archiving IS NULL OR p._archiving > \'' . date(PageQueryBuilder::$config['dateSchemeForPublishing'], time()) . '\')', $q->getDql());
        PageQueryBuilder::$config['dateSchemeForPublishing'] = 'Y-m-d H:i:00';
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsNotDeleted
     */
    public function testAndIsNotDeleted()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andIsNotDeleted();

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state < 4', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsVisible
     */
    public function testAndIsVisible()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andIsVisible();

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state = :states0 AND (p._publishing IS NULL OR p._publishing <= :now0) AND (p._archiving IS NULL OR p._archiving > :now0)', $q->getDql());
        $this->assertEquals(Page::STATE_ONLINE, $q->getParameter('states0')->getValue());
        $this->assertEquals(date('Y-m-d H:i:00', time()), $q->getParameter('now0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsAncestorOf
     */
    public function testAndIsAncestorOf()
    {
        $page = new Page('test');
        $q = $this->repo->createQueryBuilder('p')
                ->andIsAncestorOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s WHERE p._section = p AND p_s._root = :root0 AND p_s._leftnode <= :leftnode0 AND p_s._rightnode >= :rightnode0', $q->getDql());
        $this->assertEquals($page->getSection()->getRoot(), $q->getParameter('root0')->getValue());
        $this->assertEquals($page->getSection()->getLeftnode(), $q->getParameter('leftnode0')->getValue());
        $this->assertEquals($page->getSection()->getRightnode(), $q->getParameter('rightnode0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsAncestorOf($page, true);
        $this->assertEquals($page->getSection()->getLeftnode() - 1, $q->getParameter('leftnode0')->getValue());
        $this->assertEquals($page->getSection()->getRightnode() + 1, $q->getParameter('rightnode0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andIsAncestorOf($page, true, 1);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s WHERE p._section = p AND p_s._root = :root0 AND p_s._leftnode <= :leftnode0 AND p_s._rightnode >= :rightnode0 AND p_s._level = :level0', $q->getDql());
        $this->assertEquals(1, $q->getParameter('level0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andParentIs
     */
    public function testParentIs()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andParentIs();

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s WHERE p_s._parent IS NULL', $q->getDql());

        $page = new Page('test');
        $q->resetDQLPart('where')
                ->andParentIs($page);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s WHERE p_s._parent = :parent0', $q->getDql());
        $this->assertEquals($page->getSection(), $q->getParameter('parent0')->getValue());

        $q->resetDQLPart('where')
                ->andParentIs($page, true);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s WHERE p != :page1 AND p_s._parent = :parent1', $q->getDql());
        $this->assertEquals($page, $q->getParameter('page1')->getValue());
        $this->assertEquals($page->getSection(), $q->getParameter('parent1')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andLayoutIs
     */
    public function testAndLayoutIs()
    {
        $layout = new Layout();
        $q = $this->repo->createQueryBuilder('p')
                ->andLayoutIs($layout);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._layout = :layout0', $q->getDql());
        $this->assertEquals($layout, $q->getParameter('layout0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsOnlineSiblingsOf
     */
    public function testAndIsOnlineSiblingsOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsOnlineSiblingsOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._uid = :uid0 AND p._parent IS NULL AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode asc', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsPreviousOnlineSiblingOf
     */
    public function testAndIsPreviousOnlineSiblingOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsPreviousOnlineSiblingOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode <= :leftnode0 AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode DESC', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsNextOnlineSiblingOf
     */
    public function testAndIsNextOnlineSiblingOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsNextOnlineSiblingOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode >= :leftnode0 AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode ASC', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsVisibleSiblingsOf
     */
    public function testAndIsVisibleSiblingsOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsVisibleSiblingsOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._uid = :uid0 AND p._parent IS NULL AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode asc', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsPreviousVisibleSiblingOf
     */
    public function testAndIsPreviousVisibleSiblingOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsPreviousVisibleSiblingOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode <= :leftnode0 AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode DESC', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andIsNextVisibleSiblingOf
     */
    public function testAndIsNextVisibleSiblingOf()
    {
        $page = new Page();
        $q = $this->repo->createQueryBuilder('p')
                ->andIsNextVisibleSiblingOf($page);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode >= :leftnode0 AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode ASC', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andStateIsIn
     */
    public function testAndStateIsIn()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andStateIsIn(Page::STATE_ONLINE);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE), $q->getParameter('states0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andStateIsIn(array(Page::STATE_ONLINE, Page::STATE_OFFLINE));

        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE, Page::STATE_OFFLINE), $q->getParameter('states0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andStateIsNotIn
     */
    public function testAndStateIsNotIn()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andStateIsNotIn(Page::STATE_ONLINE);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state NOT IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE), $q->getParameter('states0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andStateIsNotIn(array(Page::STATE_ONLINE, Page::STATE_OFFLINE));

        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state NOT IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE, Page::STATE_OFFLINE), $q->getParameter('states0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andStateIsLowerThan
     */
    public function testAndStateIsLowerThan()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andStateIsLowerThan(Page::STATE_DELETED);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state < :state0', $q->getDql());
        $this->assertEquals(Page::STATE_DELETED, $q->getParameter('state0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andSiteIs
     */
    public function testAndSiteIs()
    {
        $site = new \BackBuilder\Site\Site();
        $q = $this->repo->createQueryBuilder('p')
                ->andSiteIs($site);

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._site = :site0', $q->getDql());
        $this->assertEquals($site, $q->getParameter('site0')->getValue());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andTitleIsLike
     */
    public function testAndTitleIsLike()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->andTitleIsLike('test');

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals("SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._title LIKE '%test%'", $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::andSearchCriteria
     */
    public function testAndSearchCriteria()
    {
        $now = new \DateTime();

        $q = $this->repo->createQueryBuilder('p')
                ->andSearchCriteria('fake');
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), 'fake');
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array('all'));
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('beforePubdateField' => $now->getTimestamp()));
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._modified < :date0', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('afterPubdateField' => $now->getTimestamp()));
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._modified > :date1', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(Page::STATE_ONLINE));
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._state IN(:states2)', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('searchField' => 'test'));
        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals("SELECT p FROM BackBuilder\NestedNode\Page p WHERE p._title LIKE '%test%'", $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::addOrderBy
     */
    public function testAddOrderBy()
    {
        $q = $this->repo->createQueryBuilder('p')
                ->addOrderBy('_position');

        $this->assertInstanceOf('BackBuilder\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p ORDER BY p._position ASC', $q->getDql());

        $q->resetDQLPart('orderBy')
                ->addOrderBy('_position', 'DESC');
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p ORDER BY p._position DESC', $q->getDql());

        $q->resetDQLPart('orderBy')
                ->addOrderBy('_leftnode');
        $this->assertEquals('SELECT p FROM BackBuilder\NestedNode\Page p INNER JOIN p._section p_s ORDER BY p_s._leftnode ASC', $q->getDql());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::getAlias
     */
    public function testGetAlias()
    {
        $q = $this->repo->createQueryBuilder('p');
        $this->assertEquals('p', $q->getAlias());
    }

    /**
     * @covers \BackBuilder\NestedNode\Repository\PageQueryBuilder::getSectionAlias
     */
    public function testGetSectionAlias()
    {
        $q = $this->repo->createQueryBuilder('p');
        $this->assertEquals(0, count($q->getDQLPart('join')));
        $this->assertEquals('p_s', $q->getSectionAlias());
        $this->assertEquals(1, count($q->getDQLPart('join')));
    }

    /**
     * Sets up the fixture
     */
    public function setUp()
    {
        $this->application = $this->getBBApp();
        $em = $this->application->getEntityManager();

        $st = new \Doctrine\ORM\Tools\SchemaTool($em);
        $st->createSchema(array($em->getClassMetaData('BackBuilder\NestedNode\Page')));

        $this->_setRepo();
    }

    /**
     * Sets the NestedNode Repository
     * @return \BackBuilder\NestedNode\Tests\Repository\NestedNodeRepositoryTest
     */
    private function _setRepo()
    {
        $this->repo = $this->application
                ->getEntityManager()
                ->getRepository('BackBuilder\NestedNode\Page');

        PageRepository::$config = array(
            'nestedNodeCalculateAsync' => false,
        );

        return $this;
    }

}
