<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\NestedNode\Tests\Repository;

use BackBee\NestedNode\Page;
use BackBee\NestedNode\Repository\PageQueryBuilder;
use BackBee\Site\Layout;
use BackBee\Tests\BackBeeTestCase;


/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageQueryBuilderTest extends BackBeeTestCase
{
    private static $previousDateFormat;

    /**
     * @var \BackBee\NestedNode\Repository\PageRepository
     */
    private $repository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->repository = self::$em->getRepository('BackBee\NestedNode\Page');
    }

    public static function setUpBeforeClass()
    {
        self::$kernel->resetDatabase();

        self::$previousDateFormat = PageQueryBuilder::$config['dateSchemeForPublishing'];

        PageQueryBuilder::$config = array(
            'dateSchemeForPublishing' => 'Y-m-d H:i:00',
        );
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsOnline
     */
    public function testAndIsOnline()
    {
        $q = $this->repository->createQueryBuilder('p')->andIsOnline();

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state IN (:states0) AND (p._publishing IS NULL OR p._publishing <= :now0) AND (p._archiving IS NULL OR p._archiving > :now0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE, Page::STATE_ONLINE + Page::STATE_HIDDEN), $q->getParameter('states0')->getValue());
        $this->assertEquals(date('Y-m-d H:i:00', time()), $q->getParameter('now0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsVisible
     */
    public function testAndIsVisible()
    {
        $q = $this->repository->createQueryBuilder('p')
                ->andIsVisible();

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state = :states0 AND (p._publishing IS NULL OR p._publishing <= :now0) AND (p._archiving IS NULL OR p._archiving > :now0)', $q->getDql());
        $this->assertEquals(Page::STATE_ONLINE, $q->getParameter('states0')->getValue());
        $this->assertEquals(date('Y-m-d H:i:00', time()), $q->getParameter('now0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andLayoutIs
     */
    public function testAndLayoutIs()
    {
        $layout = new Layout();
        $q = $this->repository->createQueryBuilder('p')
                ->andLayoutIs($layout);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._layout = :layout0', $q->getDql());
        $this->assertEquals($layout, $q->getParameter('layout0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsOnlineSiblingsOf
     */
    public function testAndIsOnlineSiblingsOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsOnlineSiblingsOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._uid = :uid0 AND p._parent IS NULL AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode asc', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsPreviousOnlineSiblingOf
     */
    public function testAndIsPreviousOnlineSiblingOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsPreviousOnlineSiblingOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode <= :leftnode0 AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode DESC', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsNextOnlineSiblingOf
     */
    public function testAndIsNextOnlineSiblingOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsNextOnlineSiblingOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode >= :leftnode0 AND p._state IN (:states1) AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode ASC', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsVisibleSiblingsOf
     */
    public function testAndIsVisibleSiblingsOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsVisibleSiblingsOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._uid = :uid0 AND p._parent IS NULL AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode asc', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsPreviousVisibleSiblingOf
     */
    public function testAndIsPreviousVisibleSiblingOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsPreviousVisibleSiblingOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode <= :leftnode0 AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode DESC', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andIsNextVisibleSiblingOf
     */
    public function testAndIsNextVisibleSiblingOf()
    {
        $page = new Page();
        $q = $this->repository->createQueryBuilder('p')
                ->andIsNextVisibleSiblingOf($page);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._parent IS NULL AND p._leftnode >= :leftnode0 AND p._state = :states1 AND (p._publishing IS NULL OR p._publishing <= :now1) AND (p._archiving IS NULL OR p._archiving > :now1) ORDER BY p._leftnode ASC', $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andStateIsIn
     */
    public function testAndStateIsIn()
    {
        $q = $this->repository->createQueryBuilder('p')
                ->andStateIsIn(Page::STATE_ONLINE);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE), $q->getParameter('states0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andStateIsIn(array(Page::STATE_ONLINE, Page::STATE_OFFLINE));

        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE, Page::STATE_OFFLINE), $q->getParameter('states0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andStateIsNotIn
     */
    public function testAndStateIsNotIn()
    {
        $q = $this->repository->createQueryBuilder('p')
                ->andStateIsNotIn(Page::STATE_ONLINE);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state NOT IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE), $q->getParameter('states0')->getValue());

        $q->resetDQLPart('where')
                ->setParameters(array())
                ->andStateIsNotIn(array(Page::STATE_ONLINE, Page::STATE_OFFLINE));

        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state NOT IN(:states0)', $q->getDql());
        $this->assertEquals(array(Page::STATE_ONLINE, Page::STATE_OFFLINE), $q->getParameter('states0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andStateIsLowerThan
     */
    public function testAndStateIsLowerThan()
    {
        $q = $this->repository->createQueryBuilder('p')
                ->andStateIsLowerThan(Page::STATE_DELETED);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state < :state0', $q->getDql());
        $this->assertEquals(Page::STATE_DELETED, $q->getParameter('state0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andSiteIs
     */
    public function testAndSiteIs()
    {
        $site = new \BackBee\Site\Site();
        $q = $this->repository->createQueryBuilder('p')
                ->andSiteIs($site);

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._site = :site0', $q->getDql());
        $this->assertEquals($site, $q->getParameter('site0')->getValue());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andTitleIsLike
     */
    public function testAndTitleIsLike()
    {
        $q = $this->repository->createQueryBuilder('p')
                ->andTitleIsLike('test');

        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals("SELECT p FROM BackBee\NestedNode\Page p WHERE p._title LIKE '%test%'", $q->getDql());
    }

    /**
     * @covers \BackBee\NestedNode\Repository\PageQueryBuilder::andSearchCriteria
     */
    public function testAndSearchCriteria()
    {
        $now = new \DateTime();

        $q = $this->repository->createQueryBuilder('p')
                ->andSearchCriteria('fake');
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), 'fake');
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array('all'));
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('beforePubdateField' => $now->getTimestamp()));
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._modified < :date0', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('afterPubdateField' => $now->getTimestamp()));
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._modified > :date1', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(Page::STATE_ONLINE));
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals('SELECT p FROM BackBee\NestedNode\Page p WHERE p._state IN(:states2)', $q->getDql());

        $q->resetDQLPart('where')
                ->andSearchCriteria(array(), array('searchField' => 'test'));
        $this->assertInstanceOf('BackBee\NestedNode\Repository\PageQueryBuilder', $q);
        $this->assertEquals("SELECT p FROM BackBee\NestedNode\Page p WHERE p._title LIKE '%test%'", $q->getDql());
    }

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        self::$em->clear();

        self::$kernel->resetDatabase([
            self::$em->getClassMetaData('BackBee\NestedNode\Page'),
        ]);
    }

    public static function tearDownAfterClass()
    {
        PageQueryBuilder::$config = array(
            'dateSchemeForPublishing' => self::$previousDateFormat,
        );
    }
}
