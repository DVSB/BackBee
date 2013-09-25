<?php

namespace BackBuilder\ClassContent\Repository;

use BackBuilder\Site\Site,
    BackBuilder\NestedNode\Page,
    BackBuilder\ClassContent\AClassContent;
use Doctrine\ORM\EntityRepository;

/**
 * The Indexation repository provides methods to update and access to the 
 * indexation content datas stored in the tables:
 *     - indexation: indexed scalar values for a content
 *     - idx_content_content: closure table between content and its sub-contents
 *     - idx_page_content: join table between a page and its contents
 *     - idx_site_content: join table between a site and its contents
 * 
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Repository
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class IndexationRepository extends EntityRepository
{

    /**
     * Executes an, optionally parameterized, SQL query
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query, if any
     * @param array $types The types the previous parameters are in
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    private function _executeQuery($query, array $params = array(), array $types = array())
    {
        $this->getEntityManager()
                ->getConnection()
                ->executeQuery($query, $params, $types);

        return $this;
    }

    /**
     * Replace site-content indexes for the provided page
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    private function _replaceIdxSite(Page $page)
    {
        $query = 'INSERT INTO idx_site_content (site_uid, content_uid) ' .
                '(SELECT :site, content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid()
        );

        return $this->_removeIdxSite($page)
                        ->_executeQuery($query, $params);
    }

    /**
     * Remove stored content-content indexes from a content
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxContentContent(AClassContent $content)
    {
        $query = 'DELETE FROM idx_content_content WHERE content_uid = :child OR subcontent_uid = :child';

        $params = array(
            'child' => $content->getUid()
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored page-content indexes from a content and a page
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxContentPage(AClassContent $content, Page $page)
    {
        $query = 'DELETE FROM idx_page_content WHERE page_uid = :page ' .
                'AND (content_uid IN (SELECT subcontent_uid FROM idx_content_content WHERE content_uid = :content) ' .
                'OR content_uid IN (SELECT content_uid FROM idx_content_content WHERE subcontent_uid = :content))';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid()
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Remove stored site-content indexes from a site and a page
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    private function _removeIdxSite(Page $page)
    {
        $query = 'DELETE FROM idx_site_content WHERE site_uid = :site AND content_uid IN (SELECT content_uid FROM idx_page_content WHERE page_uid = :page)';

        $params = array(
            'page' => $page->getUid(),
            'site' => $page->getSite()->getUid()
        );

        return $this->_executeQuery($query, $params);
    }

    /**
     * Replace content-content indexes for the provided content
     * Also replace page-content indexes if content has a main node
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxContent(AClassContent $content)
    {
        $query = 'INSERT INTO idx_content_content (content_uid, subcontent_uid) ' .
                '(SELECT :child, content_uid FROM content_has_subcontent WHERE parent_uid = :child) ' .
                'UNION DISTINCT (SELECT parent_uid, :child FROM content_has_subcontent WHERE content_uid = :child) ' .
                'UNION DISTINCT (SELECT i.content_uid, :child FROM idx_content_content i WHERE i.subcontent_uid IN (SELECT parent_uid FROM content_has_subcontent WHERE content_uid = :child)) ' .
                'UNION DISTINCT (SELECT :child, i.subcontent_uid FROM idx_content_content i WHERE i.content_uid IN (SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :child)) ' .
                'UNION DISTINCT (SELECT :child, :child)';

        $params = array(
            'child' => $content->getUid()
        );

        return $this->_removeIdxContentContent($content)
                        ->_executeQuery($query, $params)
                        ->updateIdxPage($content->getMainNode(), $content);
    }

    /**
     * Replace page-content indexes for the provided page
     * Then replace site_content indexes
     * @param \BackBuilder\NestedNode\Page $page
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxPage(Page $page = null, AClassContent $content = null)
    {
        if (null === $page) {
            return $this;
        }

        if (null === $content) {
            $content = $page->getContentSet();
        }

        $query = 'INSERT INTO idx_page_content (page_uid, content_uid) ' .
                '(SELECT :page, subcontent_uid FROM idx_content_content WHERE content_uid = :content) ' .
                'UNION DISTINCT (SELECT :page, content_uid FROM idx_content_content WHERE subcontent_uid = :content)';

        $params = array(
            'page' => $page->getUid(),
            'content' => $content->getUid()
        );

        return $this->_removeIdxContentPage($content, $page)
                        ->_executeQuery($query, $params)
                        ->_replaceIdxSite($page);
    }

    /**
     * Replaces site-content indexes for a content in a site
     * @param \BackBuilder\Site\Site $site
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    public function updateIdxSiteContent(Site $site, AClassContent $content)
    {
        $query = 'INSERT INTO idx_site_content (site_uid, content_uid) ' .
                '(SELECT :site, content_uid FROM content_has_subcontent WHERE parent_uid = :content)' .
                'UNION ' .
                '(SELECT :site, :content) ';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid()
        );

        return $this->removeIdxSiteContent($site, $content)
                        ->_executeQuery($query, $params);
    }

    /**
     * Removes all stored indexes for the content
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxContent(AClassContent $content)
    {
        $params = array(
            'content' => $content->getUid()
        );

        return $this->_executeQuery('DELETE FROM idx_site_content WHERE content_uid = :content', $params)
                        ->_executeQuery('DELETE FROM idx_page_content WHERE content_uid = :content', $params)
                        ->_removeIdxContentContent($content);
    }

    /**
     * Remove stored page-content and site-content indexes from a page
     * @param \BackBuilder\NestedNode\Page $page
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
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
     * Removes stored site-content indexes for a content in a site
     * @param \BackBuilder\Site\Site $site
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @return \BackBuilder\ClassContent\Repository\IndexationRepository
     */
    public function removeIdxSiteContent(Site $site, AClassContent $content)
    {
        $query = 'DELETE FROM idx_site_content WHERE site_uid = :site AND (content_uid IN ' .
                '(SELECT content_uid FROM content_has_subcontent WHERE parent_uid = :content)' .
                'OR content_uid = :content)';

        $params = array(
            'site' => $site->getUid(),
            'content' => $content->getUid()
        );

        return $this->_executeQuery($query, $params);
    }

}