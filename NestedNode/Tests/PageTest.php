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

namespace BackBee\NestedNode\Tests;

use BackBee\ClassContent\ContentSet;
use BackBee\MetaData\MetaDataBag;
use BackBee\NestedNode\Page;
use BackBee\NestedNode\PageRevision;
use BackBee\NestedNode\Section;
use BackBee\Site\Layout;
use BackBee\Site\Site;
use BackBee\Tests\BackBeeTestCase;
use BackBee\Workflow\State;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PageTest extends BackBeeTestCase
{

    /**
     * @var \Datetime
     */
    private $current_time;

    /**
     * @var \BackBee\NestedNode\Page
     */
    private $page;

    /**
     * @covers BackBee\NestedNode\Page::__construct
     */
    public function test__construct()
    {
        $page = new Page();
        $this->assertNotNull($page->getUid());
        $this->assertEquals(0, $page->getLevel());
        $this->assertEquals(0, $page->getPosition());
        $this->assertEquals(Page::STATE_HIDDEN, $page->getState());
        $this->assertFalse($page->isStatic());
        $this->assertEquals(Page::DEFAULT_TARGET, $page->getTarget());
        $this->assertInstanceOf('\DateTime', $page->getCreated());
        $this->assertInstanceOf('\DateTime', $page->getModified());
        $this->assertInstanceOf('BackBee\ClassContent\ContentSet', $page->getContentSet());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $page->getRevisions());
        $this->assertInstanceOf('BackBee\NestedNode\Section', $page->getSection());
        $this->assertTrue($page->hasMainSection());
        $this->assertEquals($page->getUid(), $page->getSection()->getUid());
    }

    /**
     * @covers BackBee\NestedNode\Page::__construct
     */
    public function test__constructWithOptions()
    {
        $this->assertEquals('title', $this->page->getTitle());
        $this->assertEquals('url', $this->page->getUrl());
        $this->assertEquals('root', $this->page->getUid());
        $this->assertEquals('root', $this->page->getSection()->getUid());

        $pagef = new Page('test', 'not an array');
        $this->assertNull($pagef->getTitle());
        $this->assertNull($pagef->getUrl());
    }

    /**
     * @covers BackBee\NestedNode\Page::__clone
     */
    public function test__clone()
    {
        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $revision = new PageRevision();
        $child->setSection($this->page->getSection())
                ->setState(Page::STATE_ONLINE)
                ->getRevisions()
                ->add($revision);
        $clone = clone $child;
        $this->assertNotEquals($child->getContentSet(), $clone->getContentSet());
        $this->assertNotEquals($child->getUid(), $clone->getUid());
        $this->assertEquals(1, $clone->getPosition());
        $this->assertEquals(1, $clone->getLevel());
        $this->assertGreaterThanOrEqual($clone->getCreated()->getTimestamp(), $child->getCreated()->getTimestamp());
        $this->assertGreaterThanOrEqual($clone->getModified()->getTimestamp(), $child->getModified()->getTimestamp());
        $this->assertNull($clone->getMainSection());
        $this->assertEquals(Page::STATE_HIDDEN, $clone->getState());
        $this->assertFalse($clone->isStatic());
        $this->assertEquals($child->getTitle(), $clone->getTitle());
        $this->assertEquals(null, $clone->getUrl());
        $this->assertTrue(is_array($clone->cloningData));
        $this->assertTrue(isset($clone->cloningData['pages']));
        $this->assertTrue(isset($clone->cloningData['pages'][$child->getUid()]));
        $this->assertEquals($clone, $clone->cloningData['pages'][$child->getUid()]);
        $this->assertEquals(0, $clone->getRevisions()->count());
        $clone2 = clone $this->page;
        $this->assertNotEquals($this->page->getMainSection(), $clone2->getMainSection());
        $this->assertEquals($clone2->getSection(), $clone2->getMainSection());
        $this->assertEquals(0, $clone2->getPosition());
        $this->assertEquals(0, $clone2->getLevel());
    }

    /**
     * @covers BackBee\NestedNode\Page::getContentSet
     */
    public function testGetContentSet()
    {
        $this->assertInstanceOf('BackBee\ClassContent\ContentSet', $this->page->getContentSet());
    }

    /**
     * @covers BackBee\NestedNode\Page::getUrl
     */
    public function testGetUrl()
    {
        $this->assertEquals('url', $this->page->getUrl());

        $this->page->setRedirect('redirect');
        $this->assertEquals('redirect', $this->page->getUrl());
        $this->assertEquals('redirect', $this->page->getUrl(true));
        $this->assertEquals('url', $this->page->getUrl(false));
    }

    /**
     * @covers BackBee\NestedNode\Page::getNormalizeUri
     */
    public function testGetNormalizeUri()
    {
        $this->assertEquals('url', $this->page->getNormalizeUri());

        $site = new Site();
        $this->page->setSite($site);
        $this->assertEquals('url.html', $this->page->getNormalizeUri());
    }

    /**
     * @covers BackBee\NestedNode\Page::getTarget
     */
    public function testGetTarget()
    {
        $this->assertEquals(Page::DEFAULT_TARGET, $this->page->getTarget());

        $this->page->setTarget('target');
        $this->assertEquals('target', $this->page->getTarget());
    }

    /**
     * @covers BackBee\NestedNode\Page::isRedirect
     */
    public function testIsRedirect()
    {
        $this->assertFalse($this->page->isRedirect());

        $this->page->setRedirect('redirect');
        $this->assertTrue($this->page->isRedirect());
    }

    /**
     * @covers BackBee\NestedNode\Page::getRevisions
     */
    public function testGetRevisions()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->page->getRevisions());
    }

    /**
     * @covers BackBee\NestedNode\Page::getData
     */
    public function testGetData()
    {
        $this->assertEquals([], $this->page->getData());
        $this->assertNull($this->page->getData('unknown'));
    }

    /**
     * @covers BackBee\NestedNode\Page::getParam
     */
    public function testGetParam()
    {
        $params = array(
            'left' => $this->page->getLeftnode(),
            'right' => $this->page->getRightnode(),
            'level' => $this->page->getLevel(),
            'position' => $this->page->getPosition(),
        );

        $this->assertEquals($params, $this->page->getParam());
        $this->assertEquals($this->page->getLeftnode(), $this->page->getParam('left'));
        $this->assertEquals($this->page->getRightnode(), $this->page->getParam('right'));
        $this->assertEquals($this->page->getLevel(), $this->page->getParam('level'));
        $this->assertEquals($this->page->getPosition(), $this->page->getParam('position'));
        $this->assertNull($this->page->getParam('unknown'));
    }

    /**
     * @covers BackBee\NestedNode\Page::isScheduled
     */
    public function testIsScheduled()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertFalse($page->isScheduled());

        $page->setPublishing(new \DateTime());
        $page->setArchiving();
        $this->assertTrue($page->isScheduled());

        $page->setPublishing();
        $page->setArchiving(new \DateTime());
        $this->assertTrue($page->isScheduled());

        $page->setPublishing(new \DateTime());
        $page->setArchiving(new \DateTime());
        $this->assertTrue($page->isScheduled());
    }

    /**
     * @covers BackBee\NestedNode\Page::isVisible
     */
    public function testIsVisble()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertFalse($page->isVisible());

        $page->setState(Page::STATE_ONLINE);
        $this->assertTrue($page->isVisible());

        $page->setState(Page::STATE_ONLINE & Page::STATE_HIDDEN);
        $this->assertFalse($page->isVisible());
    }

    /**
     * @covers BackBee\NestedNode\Page::isOnline
     */
    public function testIsOnline()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertFalse($page->isOnline());
        $this->assertFalse($page->isOnline(true));

        $page->setState(Page::STATE_ONLINE);
        $this->assertTrue($page->isOnline());
        $this->assertTrue($page->isOnline(true));

        $page->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN);
        $this->assertTrue($page->isOnline());
        $this->assertTrue($page->isOnline(true));

        $page->setState(Page::STATE_ONLINE + Page::STATE_HIDDEN + Page::STATE_DELETED);
        $this->assertFalse($page->isOnline());
        $this->assertFalse($page->isOnline(true));

        $tomorrow = new \DateTime('tomorrow');

        $page->setState(Page::STATE_ONLINE)
                ->setPublishing($tomorrow)
                ->setArchiving();
        $this->assertFalse($page->isOnline());
        $this->assertTrue($page->isOnline(true));
    }

    /**
     * @covers BackBee\NestedNode\Page::isDeleted
     */
    public function testIsDeleted()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertFalse($page->isDeleted());

        $page->setState($page->getState() + Page::STATE_DELETED);
        $this->assertTrue($page->isDeleted());
    }

    /**
     * @covers BackBee\NestedNode\Page::setSite
     */
    public function testSetSite()
    {
        $site = new Site();
        $this->assertEquals($this->page, $this->page->setSite($site));
        $this->assertEquals($site, $this->page->getSite());

        $this->page->setSite();
        $this->assertNull($this->page->getSite());
    }

    /**
     * @covers BackBee\NestedNode\Page::setContentSet
     */
    public function testSetContentSet()
    {
        $contentset = new ContentSet();
        $this->assertEquals($this->page, $this->page->setContentSet($contentset));
        $this->assertEquals($contentset, $this->page->getContentSet());
    }

    /**
     * @covers BackBee\NestedNode\Page::setDate
     */
    public function testSetDate()
    {
        $this->assertEquals($this->page, $this->page->setDate($this->current_time));
        $this->assertEquals($this->current_time, $this->page->getDate());
        $this->assertEquals($this->page, $this->page->setDate(null));
        $this->assertNull($this->page->getDate());
    }

    /**
     * @covers BackBee\NestedNode\Page::setLayout
     * @covers BackBee\NestedNode\Page::getInheritedContent
     * @covers BackBee\NestedNode\Page::createNewDefaultContent
     */
    public function testSetLayout()
    {
        $this->assertEquals(2, $this->page->getContentSet()->count());
        $this->assertEquals(1, $this->page->getContentSet()->first()->count());
        $this->assertInstanceOf('BackBee\ClassContent\ContentSet', $this->page->getContentSet()->first()->first());
        $this->assertEquals($this->page, $this->page->getContentSet()->first()->first()->getMainNode());
        $this->assertEquals(0, $this->page->getContentSet()->last()->count());

        $topush = new ContentSet();
        $column = new ContentSet();
        $this->page->getContentSet()->last()->push($column);
        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($this->page)
                ->setLayout($this->page->getLayout(), $topush);

        $this->assertEquals(2, $child->getContentSet()->count());
        $this->assertEquals(1, $child->getContentSet()->first()->count());
        $this->assertEquals($topush, $child->getContentSet()->first()->first());
        $this->assertEquals($child, $child->getContentSet()->first()->first()->getMainNode());
        $this->assertEquals(1, $child->getContentSet()->last()->count());
        $this->assertEquals($column, $child->getContentSet()->last()->first());
    }

    /**
     * @covers BackBee\NestedNode\Page::setAltTitle
     */
    public function testSetAltTitle()
    {
        $this->assertEquals($this->page, $this->page->setAltTitle('alt-title'));
        $this->assertEquals('alt-title', $this->page->getAltTitle());
    }

    /**
     * @covers BackBee\NestedNode\Page::setTitle
     */
    public function testSetTitle()
    {
        $this->assertEquals($this->page, $this->page->setTitle('new-title'));
        $this->assertEquals('new-title', $this->page->getTitle());
    }

    /**
     * @covers BackBee\NestedNode\Page::setUrl
     */
    public function testSetUrl()
    {
        $this->assertEquals($this->page, $this->page->setUrl('new-url'));
        $this->assertEquals('new-url', $this->page->getUrl());
    }

    /**
     * @covers BackBee\NestedNode\Page::setTarget
     */
    public function testSetTarget()
    {
        $this->assertEquals($this->page, $this->page->setTarget('target'));
        $this->assertEquals('target', $this->page->getTarget());
    }

    /**
     * @covers BackBee\NestedNode\Page::setRedirect
     */
    public function testSetRedirect()
    {
        $this->assertEquals($this->page, $this->page->setRedirect('redirect'));
        $this->assertEquals('redirect', $this->page->getRedirect());
    }

    /**
     * @covers BackBee\NestedNode\Page::setMetaData
     */
    public function testSetMetaData()
    {
        $meta = new MetaDataBag();
        $this->assertEquals($this->page, $this->page->setMetaData($meta));
        $this->assertEquals($meta, $this->page->getMetaData());
        $this->assertEquals($this->page, $this->page->setMetaData(null));
        $this->assertNull($this->page->getMetaData());
    }

    /**
     * @covers BackBee\NestedNode\Page::setState
     */
    public function testSetState()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertEquals($page, $page->setState(Page::STATE_DELETED));
        $this->assertEquals(Page::STATE_DELETED, $page->getState());
    }

    /**
     * @covers BackBee\NestedNode\Page::setPublishing
     */
    public function testSetPublishing()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertEquals($page, $page->setPublishing($this->current_time));
        $this->assertEquals($this->current_time, $page->getPublishing());
        $this->assertEquals($page, $page->setPublishing(null));
        $this->assertNull($page->getPublishing());
    }

    /**
     * @expectedException     \LogicException
     * @expectedExceptionMessage Page can't be published in the past.
     */
    public function testSetPublishingWithADateBeforeTodayFails()
    {
        $page = new Page();
        $page->setParent($this->page);

        $page->setPublishing(new \DateTime('NOW -1 day'));
    }

    /**
     * @covers BackBee\NestedNode\Page::setArchiving
     */
    public function testSetArchiving()
    {
        $page = new Page();
        $page->setParent($this->page);

        $this->assertEquals($page, $page->setArchiving($this->current_time));
        $this->assertEquals($this->current_time, $page->getArchiving());
        $this->assertEquals($page, $page->setArchiving(null));
        $this->assertNull($page->getArchiving());
    }

    /**
     * @expectedException     \LogicException
     * @expectedExceptionMessage Root page can't be archived.
     */
    public function testSetArchivingOnRootPagesFails()
    {
        $rootPage = self::$kernel->createRootPage();
        $rootPage->setArchiving(new \DateTime());
    }

    /**
     * @expectedException     \LogicException
     * @expectedExceptionMessage Page can't be archived in the past or before publication date.
     */
    public function testSetArchivingWithADateBeforeTodayFails()
    {
        $page = new Page();
        $page->setParent($this->page);

        $page->setArchiving(new \DateTime('NOW -1 day'));
    }

    /**
     * @expectedException     \LogicException
     * @expectedExceptionMessage Page can't be archived in the past or before publication date.
     */
    public function testSetArchivingWithADateBeforePublicationDateFails()
    {
        $page = new Page();
        $page->setParent($this->page);

        $page->setPublishing(new \DateTime());
        $page->setArchiving($page->getPublishing()->modify('-1 hour'));
    }

    /**
     * @covers BackBee\NestedNode\Page::setRevisions
     */
    public function testSetRevisions()
    {
        $revisions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->assertEquals($this->page, $this->page->setRevisions($revisions));
        $this->assertEquals($revisions, $this->page->getRevisions());
    }

    /**
     * @covers BackBee\NestedNode\Page::setWorkflowState
     */
    public function testSetWorkflowState()
    {
        $state = new State();
        $this->assertEquals($this->page, $this->page->setWorkflowState($state));
        $this->assertEquals($state, $this->page->getWorkflowState());
        $this->assertEquals($this->page, $this->page->setWorkflowState(null));
        $this->assertNull($this->page->getWorkflowState());
    }

    /**
     * @covers BackBee\NestedNode\Page::setLevel
     */
    public function testSetLevel()
    {
        $this->assertEquals($this->page, $this->page->setLevel(10));
        $this->assertEquals(10, $this->page->getLevel());
    }

    /**
     * @covers BackBee\NestedNode\Page::setLevel
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testSetNonNumericLevel()
    {
        $this->page->setLevel('test');
    }

    /**
     * @covers BackBee\NestedNode\Page::setPosition
     */
    public function testSetPosition()
    {
        $this->assertEquals($this->page, $this->page->setPosition(10));
        $this->assertEquals(10, $this->page->getPosition());
    }

    /**
     * @covers BackBee\NestedNode\Page::setPosition
     * @expectedException BackBee\Exception\InvalidArgumentException
     */
    public function testSetNonNumericPosition()
    {
        $this->page->setPosition('test');
    }

    /**
     * @covers BackBee\NestedNode\Page::setPosition
     */
    public function testSetModified()
    {
        $now = new \Datetime();
        $this->assertEquals($this->page, $this->page->setModified($now));
        $this->assertEquals($now, $this->page->getModified());
    }

    /**
     * @covers BackBee\NestedNode\Page::getInheritedContensetZoneParams
     */
    public function testGetInheritedContensetZoneParams()
    {
        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($this->page);

        $this->assertNull($child->getInheritedContensetZoneParams($child->getContentSet()));
        $this->assertNull($child->getInheritedContensetZoneParams($child->getContentSet()));

        $child->setLayout($this->page->getLayout());

        $expected = $this->page->getLayout()->getZone(1);
        $this->assertEquals($expected, $child->getInheritedContensetZoneParams($child->getContentSet()->last()));
        $this->assertNull($child->getInheritedContensetZoneParams($child->getContentSet()->first()));
        $this->assertNull($this->page->getInheritedContensetZoneParams($this->page->getContentSet()->first()));
        $this->assertNull($this->page->getInheritedContensetZoneParams($this->page->getContentSet()->last()));
    }

    /**
     * @covers BackBee\NestedNode\Page::getRootContentSetPosition
     */
    public function testGetRootContentSetPosition()
    {
        $column1 = $this->page->getContentSet()->first();
        $column2 = $this->page->getContentSet()->last();

        $this->assertEquals(0, $this->page->getRootContentSetPosition($column1));
        $this->assertEquals(1, $this->page->getRootContentSetPosition($column2));
        $this->assertFalse($this->page->getRootContentSetPosition(new ContentSet()));
    }

    /**
     * @covers BackBee\NestedNode\Page::getParentZoneAtSamePositionIfExists
     */
    public function testGetParentZoneAtSamePositionIfExists()
    {
        $page = new Page('test', array('title' => 'title', 'url' => 'url'));
        $page->setLayout(self::$kernel->createLayout('test'));

        $this->assertFalse($this->page->getParentZoneAtSamePositionIfExists($page->getContentSet()->first()));
        $this->assertFalse($this->page->getParentZoneAtSamePositionIfExists($page->getContentSet()->last()));

        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($page)
                ->setLayout($page->getLayout());

        $this->assertFalse($child->getParentZoneAtSamePositionIfExists(new ContentSet()));
        $this->assertEquals($page->getContentSet()->first(), $child->getParentZoneAtSamePositionIfExists($child->getContentSet()->first()));
        $this->assertEquals($page->getContentSet()->last(), $child->getParentZoneAtSamePositionIfExists($child->getContentSet()->last()));

        $thirdcolumn = new \stdClass();
        $thirdcolumn->id = 'third';
        $thirdcolumn->defaultContainer = null;
        $thirdcolumn->target = '#target';
        $thirdcolumn->gridClassPrefix = 'row';
        $thirdcolumn->gridSize = 4;
        $thirdcolumn->mainZone = false;
        $thirdcolumn->defaultClassContent = 'inherited';
        $thirdcolumn->options = null;

        $data = self::$kernel->getDefaultLayoutZones();
        $data->templateLayouts[] = $thirdcolumn;

        $layout = new Layout();
        $child->setLayout($layout->setDataObject($data));
        $this->assertEquals($page->getContentSet()->last(), $child->getParentZoneAtSamePositionIfExists($child->getContentSet()->item(1)));
        $this->assertFalse($child->getParentZoneAtSamePositionIfExists($child->getContentSet()->last()));
    }

    /**
     * @covers BackBee\NestedNode\Page::getInheritedZones
     */
    public function testGetInheritedZones()
    {
        $this->assertEquals(array(), $this->page->getInheritedZones());

        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($this->page)
                ->setLayout($this->page->getLayout());

        $this->assertEquals(array($child->getContentSet()->last()->getUid() => $child->getContentSet()->last()), $child->getInheritedZones());
        $this->assertEquals(array($child->getContentSet()->last()->getUid() => $child->getContentSet()->last()), $child->getInheritedZones(false));
        $this->assertEquals(array($child->getContentSet()->last()->getUid() => $child->getContentSet()->last()), $child->getInheritedZones(null));
        $this->assertEquals(array($child->getContentSet()->last()->getUid() => $child->getContentSet()->last()), $child->getInheritedZones('fake'));
        $this->assertEquals(array($child->getContentSet()->last()->getUid()), $child->getInheritedZones(true));
    }

    /**
     * @covers BackBee\NestedNode\Page::getPageMainZones
     */
    public function testGetPageMainZones()
    {
        $this->assertEquals(array($this->page->getContentSet()->first()->getUid() => $this->page->getContentSet()->first()), $this->page->getPageMainZones());

        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $this->assertEquals(array(), $child->getPageMainZones());
    }

    /**
     * @covers BackBee\NestedNode\Page::isLinkedToHisParentBy
     */
    public function testIsLinkedToHisParentBy()
    {
        $this->assertFalse($this->page->isLinkedToHisParentBy($this->page->getContentSet()->first()));
        $this->assertFalse($this->page->isLinkedToHisParentBy($this->page->getContentSet()->last()));
        $this->assertFalse($this->page->isLinkedToHisParentBy(null));

        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($this->page)
                ->setLayout($this->page->getLayout());

        $this->assertFalse($child->isLinkedToHisParentBy($child->getContentSet()->first()));
        $this->assertTrue($child->isLinkedToHisParentBy($child->getContentSet()->last()));
    }

    /**
     * @covers BackBee\NestedNode\Page::replaceRootContentSet
     */
    public function testReplaceRootContentSet()
    {
        $oldContentSet = $this->page->getContentSet()->last();
        $newContentSet = new ContentSet();

        $this->assertEquals($newContentSet, $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $newContentSet));
        $this->assertEquals($oldContentSet, $this->page->getContentSet()->last());
        $this->assertEquals($newContentSet, $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $newContentSet, true));
        $this->assertEquals($oldContentSet, $this->page->getContentSet()->last());
        $this->assertEquals($newContentSet, $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $newContentSet, false));
        $this->assertEquals($newContentSet, $this->page->getContentSet()->last());

        $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $oldContentSet, false);
        $this->assertEquals($newContentSet, $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $newContentSet, null));
        $this->assertEquals($newContentSet, $this->page->getContentSet()->last());

        $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $oldContentSet, false);
        $this->assertEquals($newContentSet, $this->page->replaceRootContentSet($this->page->getContentSet()->last(), $newContentSet, 'fake'));
        $this->assertEquals($newContentSet, $this->page->getContentSet()->last());

        $child = new Page('child', array('title' => 'child', 'url' => 'url'));
        $child->setParent($this->page)
                ->setLayout($this->page->getLayout());

        $this->assertEquals($newContentSet, $child->replaceRootContentSet($child->getContentSet()->last(), $newContentSet));
        $this->assertEquals($newContentSet, $child->getContentSet()->last());
    }

    /**
     * @covers BackBee\NestedNode\Page::setUseUrlRedirect
     */
    public function testSetUseUrlRedirect()
    {
        $this->assertEquals($this->page, $this->page->setUseUrlRedirect(true));
    }

    /**
     * @covers BackBee\NestedNode\Page::getUseUrlRedirect
     */
    public function testGetUseUrlRedirect()
    {
        $this->assertEquals(true, $this->page->getUseUrlRedirect());
        $this->page->setUseUrlRedirect(false);
        $this->assertEquals(false, $this->page->getUseUrlRedirect());
    }

    /**
     * @covers BackBee\NestedNode\Page::hasMainSection()
     */
    public function testHasMainSection()
    {
        $this->assertTrue($this->page->hasMainSection());
        $page = new Page();
        $section = new Section();
        $page->setSection($section);
        $this->assertFalse($page->hasMainSection());
    }

    /**
     * @covers BackBee\NestedNode\Page::setMainSection()
     */
    public function testSetMainSection()
    {
        $section = new Section('new_section');
        $section->setLevel(10);
        $this->page->setMainSection($section);
        $this->assertEquals($section, $this->page->getMainSection());
        $this->assertEquals($section, $this->page->getSection());
        $this->assertEquals(0, $this->page->getPosition());
        $this->assertEquals(10, $this->page->getLevel());
        $this->assertEquals($this->page, $section->getPage());
    }

    /**
     * @covers BackBee\NestedNode\Page::setSection()
     */
    public function testSetSection()
    {
        $section = new Section('new_section');
        $section->setLevel(10);
        $this->page->setSection($section);
        $this->assertNull($this->page->getMainSection());
        $this->assertEquals($section, $this->page->getSection());
        $this->assertEquals(1, $this->page->getPosition());
        $this->assertEquals(11, $this->page->getLevel());
    }

    /**
     * @covers BackBee\NestedNode\Page::getSection()
     */
    public function testGetSection()
    {
        $this->assertEquals($this->page->getMainSection(), $this->page->getSection());
        $page = new Page('test');
        $this->assertEquals($page->getMainSection(), $page->getSection());
        $this->assertEquals('test', $page->getSection()->getUid());
    }

    /**
     * @covers BackBee\NestedNode\Page::isLeaf()
     */
    public function testIsLeaf()
    {
        $this->assertFalse($this->page->isLeaf());
        $child = new Page('child');
        $child->setSection($this->page->getSection());
        $this->assertTrue($child->isLeaf());
    }

    /**
     * @covers BackBee\NestedNode\Page::isAncestorOf()
     */
    public function testIsAncestorOf()
    {
        $child = new Page('child');
        $child->setSection($this->page->getSection());
        $this->assertFalse($child->isAncestorOf($child));
        $this->assertTrue($child->isAncestorOf($child, false));
        $this->assertFalse($child->isAncestorOf($this->page));
        $this->assertTrue($this->page->isAncestorOf($child));
        $this->assertFalse($this->page->isAncestorOf($this->page));
        $this->assertTrue($this->page->isAncestorOf($this->page, false));
        $child2 = new Page('child2');
        $child2->setSection($this->page->getSection());
        $this->assertFalse($child->isAncestorOf($child2));
    }

    /**
     * @covers BackBee\NestedNode\Page::isAncestorOf()
     */
    public function testIsDescendantOf()
    {
        $child = new Page('child');
        $child->setSection($this->page->getSection());
        $this->assertFalse($child->isDescendantOf($child));
        $this->assertTrue($child->isDescendantOf($child, false));
        $this->assertTrue($child->isDescendantOf($this->page));
        $this->assertFalse($this->page->isDescendantOf($this->page));
        $this->assertTrue($this->page->isDescendantOf($this->page, false));
        $child2 = new Page('child2');
        $child2->setSection($this->page->getSection());
        $this->assertFalse($child->isDescendantOf($child2));
    }

    /**
     * @covers BackBee\NestedNode\Page::getRoot()
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->page, $this->page->getRoot());
        $child = new Page('child');
        $child->setSection($this->page->getSection());
        $this->assertEquals($this->page, $child->getRoot());
    }

    /**
     * @covers BackBee\NestedNode\Page::isRoot()
     */
    public function testIsRoot()
    {
        $this->assertTrue($this->page->isRoot());
        $subsection = new Section('sub-section');
        $subsection->setRoot($this->page->getSection())
                ->setParent($this->page->getSection());
        $child = new Page('child', array('main_section' => $subsection));
        $this->assertFalse($child->isRoot());
        $subchild = new Page('child');
        $subchild->setSection($child->getSection());
        $this->assertFalse($subchild->isRoot());
    }

    /**
     * @covers BackBee\NestedNode\Page::setParent()
     */
    public function testSetParent()
    {
        $child1 = new Page('child1');
        $child1->setSection($this->page->getSection());
        $child1->setParent($this->page);
        $this->assertEquals($this->page, $child1->getParent());
        $this->assertEquals($this->page->getSection(), $child1->getSection());
        $child2 = new Page('child2');
        $child2->setParent($this->page);
        $this->assertEquals($this->page, $child2->getParent());
        $this->assertEquals($this->page->getSection(), $child2->getSection());
        $subsection = new Section('sub-section');
        $subsection->setParent($this->page->getSection());
        $child3 = new Page('child3', array('main_section' => $subsection));
        $child3->setParent($this->page);
        $this->assertEquals($this->page, $child3->getParent());
        $this->assertEquals($subsection, $child3->getSection());
    }

    /**
     * @covers BackBee\NestedNode\Page::setParent()
     * @expectedException \BackBee\Exception\InvalidArgumentException
     */
    public function testSetParentWithLeaf()
    {
        $child1 = new Page('child1');
        $child2 = new Page('child2');
        $child1->setParent($this->page);
        $child2->setParent($child1);
    }

    /**
     * @covers BackBee\NestedNode\Page::getParent()
     */
    public function testGetParent()
    {
        $this->assertNull($this->page->getParent());
        $subsection = new Section('sub-section');
        $subsection->setRoot($this->page->getSection())
                ->setParent($this->page->getSection());
        $child = new Page('child', array('main_section' => $subsection));
        $this->assertEquals($this->page, $child->getParent());
        $subchild = new Page('child');
        $subchild->setSection($child->getSection());
        $this->assertEquals($child, $subchild->getParent());
    }

    /**
     * @covers BackBee\NestedNode\Page::getLeftnode()
     * @covers BackBee\NestedNode\Page::getRightnode()
     */
    public function testGetNode()
    {
        $this->assertEquals(1, $this->page->getLeftnode());
        $this->assertEquals(2, $this->page->getRightnode());
        $child = new Page('child');
        $child->setSection($this->page->getSection());
        $this->assertEquals(1, $child->getLeftnode());
        $this->assertEquals(2, $child->getRightnode());
    }

    /**
     * Restrictions on Root page
     * => A root page can't be archived;
     * => A root page can't be published in the future;
     * => A root page can't be put offline.
     * @expectedException \LogicException
     */
    public function testRootPageCantBeArchived()
    {
        $this->page->setArchiving(new \Datetime());
    }

    /**
     * @expectedException \LogicException
     */
    public function testRootPageCantBePublished()
    {
        $this->page->setPublishing(new \Datetime());
    }

    /**
     * @expectedException \LogicException
     */
    public function testRootPageCantBePutOffline()
    {
        $this->page->setState(Page::STATE_OFFLINE);
    }

    /**
     * Test cascade Doctrine annotations for entity
     */
    public function testDoctrineCascades()
    {
        self::$kernel->resetDatabase();
        $site = new Site('site-test', ['label' => 'site-test']);
        $layout = self::$kernel->createLayout('layout-test', 'layout-test');
        self::$em->persist($site);
        self::$em->persist($layout);
        self::$em->flush();

        $root = new Page('root', ['title' => 'root']);
        $root->setSite($site)
                ->setLayout($layout);

        // Persist cascade on Page::_mainsection and Page::_contentset
        self::$em->persist($root);
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($root));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($root->getMainSection()));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForInsert($root->getContentSet()));
        self::$em->flush($root);

        // Remove cascade on Page::_mainsection and Page::_contentset
        self::$em->remove($root);
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForDelete($root));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForDelete($root->getContentSet()));
        $this->assertTrue(self::$em->getUnitOfWork()->isScheduledForDelete($root->getMainSection()));
        self::$em->flush();
    }

    /**
     * Sets up the fixture.
     */
    public function setUp()
    {
        $this->current_time = new \Datetime();
        $this->page = self::$kernel->createPage('root');
    }

}
