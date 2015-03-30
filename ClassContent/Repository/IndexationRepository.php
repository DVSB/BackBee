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

namespace BackBee\ClassContent\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\NestedNode\Page;
use BackBee\Site\Site;
use BackBee\Util\Doctrine\DriverFeatures;

/**
 * The Indexation repository provides methods to update and access to the
 * indexation content datas stored in the tables:
 *     - indexation: indexed scalar values for a content
 *     - idx_content_content: closure table between content and its sub-contents
 *     - idx_page_content: join table between a page and its contents
 *     - idx_site_content: join table between a site and its contents.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class IndexationRepository extends EntityRepository
{
    /**
     * Is REPLACE command is supported.
     *
     * @var boolean
     */
    private $_replace_supported;

    /**
     * Initializes a new EntityRepository.
     *
     * @param \Doctrine\ORM\EntityManager         $em            The EntityManager to use.
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->_replace_supported = DriverFeatures::replaceSupported($em->getConnection()->getDriver());
    }

    /**
     * Replaces content in optimized tables.
     *
     * @param  \BackBee\ClassContent\AbstractClassContent                   $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function replaceOptContentTable(AbstractClassContent $content)
    {
        if (null === $content->getMainNode()) {
            return $this;
        }

        $command = 'REPLACE';
        if (false === $this->_replace_supported) {
            // REPLACE command not supported, remove first then insert
            $this->removeOptContentTable($content);
            $command = 'INSERT';
        }

        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\OptContentByModified');
        $query = $command.' INTO '.$meta->getTableName().
                ' ('.$meta->getColumnName('_uid').', '.
                $meta->getColumnName('_label').', '.
                $meta->getColumnName('_classname').', '.
                $meta->getColumnName('_node_uid').', '.
                $meta->getColumnName('_modified').', '.
                $meta->getColumnName('_created').')'.
                ' VALUES (:uid, :label, :classname, :node_uid, :modified, :created)';

        $params = array(
            'uid' => $content->getUid(),
            'label' => $content->getLabel(),
            'classname' => \Symfony\Component\Security\Core\Util\ClassUtils::getRealClass($content),
            'node_uid' => $content->getMainNode()->getUid(),
            'modified' => date('Y-m-d H:i:s', $content->getModified()->getTimestamp()),
            'created' => date('Y-m-d H:i:s', $content->getCreated()->getTimestamp()),
        );

        $types = array(
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
        );

        return $this->_executeQuery($query, $params, $types);
    }

    /**
     * Removes content from optimized table.
     *
     * @param  \BackBee\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeOptContentTable(AbstractClassContent $content)
    {
        $this->getEntityManager()
                ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\OptContentByModified o WHERE o._uid=:uid')
                ->setParameter('uid', $content->getUid())
                ->execute();

        return $this;
    }

    /**
     * Replaces site-content indexes for an array of contents in a site.
     *
     * @param \BackBee\Site\Site $site
     * @param array              $contents An array of AbstractClassContent
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxSiteContents(Site $site, array $contents)
    {
        $content_uids = $this->_getAClassContentUids($contents);

        return $this->replaceIdxSiteContentsUid($site->getUid(), $content_uids);
    }

    /**
     * Removes site-content indexes for an array of contents in a site.
     *
     * @param \BackBee\Site\Site $site
     * @param array              $contents An array of AbstractClassContent
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxSiteContents(Site $site, array $contents)
    {
        return $this->_removeIdxSiteContents($site->getUid(), $this->_getAClassContentUids($contents));
    }

    /**
     * Replaces content-content indexes for an array of contents.
     *
     * @param array $contents An array of AbstractClassContent
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxContentContents(array $contents)
    {
        $parent_uids = array();
        foreach ($contents as $content) {
            // avoid loop if content is already treated
            if (null === $content || true === $content->isElementContent()) {
                continue;
            } elseif (true === array_key_exists($content->getUid(), $parent_uids)) {
                break;
            } elseif (false === array_key_exists($content->getUid(), $parent_uids)) {
                $parent_uids[$content->getUid()] = array($content->getUid());
            }

            $parent_uids[$content->getUid()] = array_merge($parent_uids[$content->getUid()], $this->_getAClassContentUids($content->getSubcontent()->toArray()));
        }

        return $this->_replaceIdxContentContents($parent_uids);
    }

    /**
     * Removes content-content indexes for an array of contents.
     *
     * @param array $contents An array of AbstractClassContent
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxContentContents(array $contents)
    {
        return $this->_removeIdxContentContents($this->_getAClassContentUids($contents));
    }

    /**
     * Replaces or inserts a set of Site-Content indexes.
     *
     * @param string $site_uid
     * @param array  $content_uids
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function replaceIdxSiteContentsUid($site_uid, array $content_uids)
    {
        if (0 < count($content_uids)) {
            $command = 'REPLACE';
            if (false === $this->_replace_supported) {
                // REPLACE command not supported, remove first then insert
                $this->_removeIdxSiteContents($site_uid, $content_uids);
                $command = 'INSERT';
            }

            $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxSiteContent');
            $query = $command.' INTO '.$meta->getTableName().
                    ' ('.$meta->getColumnName('site_uid').', '.$meta->getColumnName('content_uid').')'.
                    ' VALUES ("'.$site_uid.'", "'.implode('"), ("'.$site_uid.'", "', $content_uids).'")';

            $this->_em->getConnection()->executeQuery($query);
        }

        return $this;
    }

    /**
     * Returns an array of content uids owning provided contents.
     *
     * @param array $uids
     *
     * @return array
     */
    public function getParentContentUidsByUids(array $uids)
    {
        $ids = array();

        $query  = 'SELECT j.parent_uid FROM content_has_subcontent j
                   LEFT JOIN content c ON c.uid = j.content_uid
                   WHERE classname != \'BackBee\ClassContent\Element\'';

        $where = array();
        foreach ($uids as $uid) {
            $where[] = $uid;
        }

        if (count($where) > 0) {
            $query .= ' AND j.content_uid  IN ("'.implode('","', $where).'")';
            $parents = $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query)->fetchAll(\PDO::FETCH_COLUMN);
            if ($parents) {
                $ids = array_merge($ids, $parents, $this->getParentContentUidsByUids($parents));
            }
        }

        return array_unique($ids);
    }

    /**
     * Returns an array of content uids owning provided contents.
     *
     * @param array $contents
     *
     * @return array
     */
    public function getParentContentUids(array $contents)
    {
        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.'.$meta->getColumnName('content_uid'))
                ->from($meta->getTableName(), 'c');

        $p = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('j.parent_uid')
                ->from('content_has_subcontent', 'j');

        $index = 0;
        $method = 'where';
        $atleastone = false;
        foreach ($contents as $content) {
            if (false === ($content instanceof AbstractClassContent)) {
                continue;
            }

            if (true === $content->isElementContent()) {
                continue;
            }

            if ($index !== 0) {
                $method = 'orWhere';
            }

            $q->{$method}('c.'.$meta->getColumnName('subcontent_uid').' = :uid'.$index)
              ->setParameter('uid'.$index, $content->getUid());

            $p->{$method}('j.content_uid = :uid'.$index)
              ->setParameter('uid'.$index, $content->getUid());

            $index++;
            $atleastone = true;
        }

        return (true === $atleastone) ? array_unique(
            array_merge(
                $q->execute()->fetchAll(\PDO::FETCH_COLUMN),
                $p->execute()->fetchAll(\PDO::FETCH_COLUMN)
            )
        ) : array();
    }

    /**
     * Returns an array of content uids owned by provided contents.
     *
     * @param mixed $contents
     *
     * @return array
     */
    public function getDescendantsContentUids($contents)
    {
        if (false === is_array($contents)) {
            $contents = array($contents);
        }

        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.'.$meta->getColumnName('subcontent_uid'))
                ->from($meta->getTableName(), 'c');

        $index = 0;
        $atleastone = false;
        foreach ($contents as $content) {
            if (false === ($content instanceof AbstractClassContent)) {
                continue;
            }

            if (true === $content->isElementContent()) {
                continue;
            }

            $q->orWhere('c.'.$meta->getColumnName('content_uid').' = :uid'.$index)
                    ->setParameter('uid'.$index, $content->getUid());

            $index++;
            $atleastone = true;
        }

        return (true === $atleastone) ? array_unique($q->execute()->fetchAll(\PDO::FETCH_COLUMN)) : array();
    }

    /**
     * Returns every main node attach to the provided content uids.
     *
     * @param array $content_uids
     *
     * @return array
     */
    public function getNodeUids(array $content_uids)
    {
        $meta = $this->_em->getClassMetadata('BackBee\ClassContent\AbstractClassContent');

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('c.node_uid')
                ->from($meta->getTableName(), 'c');

        $q->andWhere('c.'.$meta->getColumnName('_uid').' IN (:ids)')
              ->setParameter('ids', $content_uids);

        return (false === empty($content_uids)) ? array_unique($q->execute()->fetchAll(\PDO::FETCH_COLUMN)) : array();
    }

    /**
     * Removes a set of Site-Content indexes.
     *
     * @param string $site_uid
     * @param array  $content_uids
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxSiteContents($site_uid, array $content_uids)
    {
        if (0 < count($content_uids)) {
            $this->getEntityManager()
                    ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\IdxSiteContent i
                        WHERE i.site_uid=:site_uid
                        AND i.content_uid IN (:content_uids)')
                    ->setParameters(array(
                        'site_uid' => $site_uid,
                        'content_uids' => $content_uids, ))
                    ->execute();
        }

        return $this;
    }

    /**
     * Replaces a set of Site-Content indexes.
     *
     * @param string $site_uid
     * @param array  $content_uids
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _replaceIdxContentContents(array $parent_uids)
    {
        if (0 < count($parent_uids)) {
            $command = 'REPLACE';
            if (false === $this->_replace_supported) {
                // REPLACE command not supported, remove first then insert
                $this->_removeIdxContentContents(array_keys($parent_uids));
                $command = 'INSERT';
            }

            $meta = $this->_em->getClassMetadata('BackBee\ClassContent\Indexes\IdxContentContent');
            $insert_children = array();
            foreach ($parent_uids as $parent_uid => $subcontent_uids) {
                foreach ($subcontent_uids as $subcontent_uid) {
                    $insert_children[] = sprintf('SELECT "%s", "%s"', $parent_uid, $subcontent_uid);
                    $insert_children[] = sprintf('SELECT %s, "%s"
                        FROM %s
                        WHERE %s = "%s"', $meta->getColumnName('content_uid'), $subcontent_uid, $meta->getTableName(), $meta->getColumnName('subcontent_uid'), $parent_uid
                    );
                }
            }

            if (0 < count($insert_children)) {
                $union_all = implode(' UNION ALL ', $insert_children);
                $query = sprintf('%s INTO %s (%s, %s) %s',
                    $command,
                    $meta->getTableName(),
                    $meta->getColumnName('content_uid'),
                    $meta->getColumnName('subcontent_uid'),
                    $union_all
                );
                $this->_em->getConnection()->executeQuery($query);
            }
        }

        return $this;
    }

    /**
     * Removes a set of Content-Content indexes.
     *
     * @param array $content_uids
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function _removeIdxContentContents(array $content_uids)
    {
        if (0 < count($content_uids)) {
            $this->getEntityManager()
                    ->createQuery('DELETE FROM BackBee\ClassContent\Indexes\IdxContentContent i
                        WHERE i.content_uid IN(:content_uids)
                        OR i.subcontent_uid IN(:subcontent_uids)')
                    ->setParameters(array(
                        'content_uids' => $content_uids,
                        'subcontent_uids' => $content_uids, ))
                    ->execute();
        }

        return $this;
    }

    /**
     * Executes an, optionally parameterized, SQL query.
     *
     * @param string $query  The SQL query to execute
     * @param array  $params The parameters to bind to the query, if any
     * @param array  $types  The types the previous parameters are in
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _executeQuery($query, array $params = array(), array $types = array())
    {
        $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, $params, $types);

        return $this;
    }

    /**
     * Replace site-content indexes for the provided page.
     *
     * @param \BackBee\NestedNode\Page $page
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _replaceIdxSite(Page $page)
    {
        $query = 'INSERT INTO idx_site_content (site_uid, content_uid) '.
                '(SELECT :site, content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid(),
        );

        return $this->_removeIdxSite($page)
                        ->_executeQuery($query, $params);
    }

    /**
     * Remove stored content-content indexes from a content.
     *
     * @param \BackBee\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxContentContent(AbstractClassContent $content)
    {
        $query = 'DELETE FROM idx_content_content WHERE content_uid = :child OR subcontent_uid = :child';

        $params = array(
            'child' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored page-content indexes from a content and a page.
     *
     * @param  \BackBee\ClassContent\AbstractClassContent            $content
     * @param  \BackBee\NestedNode\Page                              $page
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxContentPage(AbstractClassContent $content, Page $page)
    {
        $query = 'DELETE FROM idx_page_content WHERE page_uid = :page '.
                'AND (content_uid IN (SELECT subcontent_uid FROM idx_content_content WHERE content_uid = :content) '.
                'OR content_uid IN (SELECT content_uid FROM idx_content_content WHERE subcontent_uid = :content))';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored site-content indexes from a site and a page.
     *
     * @param \BackBee\NestedNode\Page $page
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxSite(Page $page)
    {
        $query = 'DELETE FROM idx_site_content WHERE site_uid = :site AND content_uid IN (SELECT content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Replace content-content indexes for the provided content
     * Also replace page-content indexes if content has a main node.
     *
     * @param \BackBee\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxContent(AbstractClassContent $content)
    {
        $query = 'INSERT INTO idx_content_content (content_uid, subcontent_uid) '.
                '(SELECT :child, content_uid FROM content_has_subcontent WHERE parent_uid = :child) '.
                'UNION DISTINCT (SELECT parent_uid, :child FROM content_has_subcontent WHERE content_uid = :child) '.
                'UNION DISTINCT (SELECT i.content_uid, :child FROM idx_content_content i WHERE i.subcontent_uid IN (SELECT parent_uid FROM content_has_subcontent WHERE content_uid = :child)) '.
                'UNION DISTINCT (SELECT :child, i.subcontent_uid FROM idx_content_content i WHERE i.content_uid IN (SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :child)) '.
                'UNION DISTINCT (SELECT :child, :child)';

        $params = array(
            'child' => $content->getUid(),
        );

        return $this->_removeIdxContentContent($content)
                        ->_executeQuery($query, $params)
                        ->updateIdxPage($content->getMainNode(), $content);
    }

    /**
     * Replace page-content indexes for the provided page
     * Then replace site_content indexes.
     *
     * @param  \BackBee\NestedNode\Page                              $page
     * @param  \BackBee\ClassContent\AbstractClassContent            $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxPage(Page $page = null, AbstractClassContent $content = null)
    {
        if (null === $page) {
            return $this;
        }

        if (null === $content) {
            $content = $page->getContentSet();
        }

        $query = 'INSERT INTO idx_page_content (page_uid, content_uid) '.
                '(SELECT :page, subcontent_uid FROM idx_content_content WHERE content_uid = :content) '.
                'UNION DISTINCT (SELECT :page, content_uid FROM idx_content_content WHERE subcontent_uid = :content)';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid(),
        );

        return $this->_removeIdxContentPage($content, $page)
                        ->_executeQuery($query, $params)
                        ->_replaceIdxSite($page);
    }

    /**
     * Replaces site-content indexes for a content in a site.
     *
     * @param  \BackBee\Site\Site                                    $site
     * @param  \BackBee\ClassContent\AbstractClassContent            $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxSiteContent(Site $site, AbstractClassContent $content)
    {
        $query = 'INSERT INTO idx_site_content (site_uid, content_uid) '.
                '(SELECT :site, content_uid FROM content_has_subcontent WHERE parent_uid = :content)'.
                'UNION '.
                '(SELECT :site, :content) ';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid(),
        );

        return $this->removeIdxSiteContent($site, $content)
                        ->_executeQuery($query, $params);
    }

    /**
     * Returns an array of AbstractClassContent uids.
     *
     * @param array $contents An array of object
     *
     * @return array
     */
    private function _getAClassContentUids(array $contents)
    {
        $content_uids = array();
        foreach ($contents as $content) {
            if ($content instanceof AbstractClassContent &&
                    false === $content->isElementContent()) {
                $content_uids[] = $content->getUid();
            }
        }

        return $content_uids;
    }

    /**
     * Removes all stored indexes for the content.
     *
     * @param \BackBee\ClassContent\AbstractClassContent $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxContent(AbstractClassContent $content)
    {
        $params = array(
            'content' => $content->getUid(),
        );

        return $this->_executeQuery('DELETE FROM idx_site_content WHERE content_uid = :content', $params)
                        ->_executeQuery('DELETE FROM idx_page_content WHERE content_uid = :content', $params)
                        ->_removeIdxContentContent($content);
    }

    /**
     * Remove stored page-content and site-content indexes from a page.
     *
     * @param \BackBee\NestedNode\Page $page
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxPage(Page $page)
    {
        $query = 'DELETE FROM idx_page_content WHERE page_uid = :page';

        $params = array(
            'page' => $page->getUid(),
        );

        return $this->_removeIdxSite($page)
                        ->_executeQuery($query, $params);
    }

    /**
     * Removes stored site-content indexes for a content in a site.
     *
     * @param  \BackBee\Site\Site                                    $site
     * @param  \BackBee\ClassContent\AbstractClassContent            $content
     *
     * @return \BackBee\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxSiteContent(Site $site, AbstractClassContent $content)
    {
        $query = 'DELETE FROM idx_site_content WHERE site_uid = :site AND (content_uid IN '.
                '(SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :content)'.
                'OR content_uid = :content)';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid(),
        );

        return $this->_executeQuery($query, $params);
    }
}
