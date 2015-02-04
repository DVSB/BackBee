<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
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

namespace BackBee\Services\Local;

use BackBee\Services\Content\Category;
use BackBee\Services\Content\ContentRender;
use BackBee\Services\Exception\ServicesException;

/**
 * @category    BackBee
 * @package     BackBee\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class ClassContent extends AbstractServiceLocal
{
    private $_frontData = null;
    private $_processedContent = array();

    /**
     * Return the serialized form of a page
     * @exposed(secured=true)
     *
     */
    public function find($classname, $uid)
    {
        $em = $this->getApplication()->getEntityManager();
        $content = $em->getRepository('\BackBee\ClassContent\\'.$classname)->find($uid);

        if (null === $content) {
            throw new ServicesException(sprintf('Unable to find content for `%s` uid', $uid));
        }

        $this->isGranted('VIEW', $content);

        $content = json_decode($content->serialize());

        return $content;
    }

    private function prepareContentData($initial_content, $datas, $accept, $isParentAContentSet = false, $persist = true)
    {
        if (true === is_array($accept) && 0 === count($accept)) {
            $accept = $initial_content->getAccept();
        }

        $result = array();
        $em = $this->getApplication()->getEntityManager();

        if (is_array($datas) && count($datas)) {
            foreach ($datas as $key => $contentInfo) {
                if ($accept && is_array($accept) && count($accept) && !array_key_exists($key, $accept)) {
                    continue;
                }

                $createDraft = true;
                if ($isParentAContentSet) {
                    $contentInfo = (object) $contentInfo;
                    $contentType = 'BackBee\ClassContent\\'.$contentInfo->nodeType;
                    $contentUid = $contentInfo->uid;
                } else {
                    $contentType = (is_array($accept[$key])) ? $accept[$key][0] : $accept[$key];
                    if (0 !== strpos($contentType, 'BackBee\ClassContent\\')) {
                        $contentType = 'BackBee\ClassContent\\'.$contentType;
                    }
                    $contentUid = $contentInfo;
                }
                if (array_key_exists($contentUid, $this->_frontData)) {
                    $childContent = $this->_frontData[$contentUid];
                    $content = $this->processContent($childContent, $persist);
                    $result[$key] = $content;
                } elseif (null !== $exists = $em->find($contentType, $contentUid)) {
                    $result[$key] = $exists;
                } else {
                    $content = ($initial_content instanceof \BackBee\ClassContent\ContentSet) ? $initial_content->item($key) : $initial_content->$key;
                    if (null !== $content) {
                        $result[$key] = $content;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function update($data = array())
    {
        if (!is_array($data)) {
            throw new ServicesException("ClassContent.update data can't be empty");
        }
        $this->_frontData = $data;
        $em = $this->getApplication()->getEntityManager();
        foreach ($data as $srzContent) {
            $this->processContent($srzContent);
        }
        $em->flush();
    }

    /**
     * @exposed(secured=true)
     */
    public function updateContentRender($renderType, $srzContent = null, $page_uid = null)
    {
        if (is_null($srzContent)) {
            throw new ServicesException("ClassContent.update data can't be null");
        }
        $em = $this->getApplication()->getEntityManager();
        $srzContent = (object) $srzContent;
        if (false === array_key_exists('uid', $srzContent)) {
            throw new ServicesException('An uid must be provided');
        }
        $content = $this->getApplication()->getEntityManager()->find('BackBee\ClassContent\\'.$srzContent->type, $srzContent->uid);
        if (null === $content) {
            $classname = 'BackBee\ClassContent\\'.$srzContent->type;
            $content = new $classname($srzContent->uid);
        }

        $this->isGranted('VIEW', $content);

        $srzContent->data = null;
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($content, $this->getApplication()->getBBUserToken(), true)) {
            $content->setDraft($draft);
        }
        $content = $content->unserialize($srzContent);
        if (null !== $page_uid && (null !== $page = $em->find('BackBee\NestedNode\Page', $page_uid))) {
            $this->getApplication()->getRenderer()->setCurrentPage($page);
        }

        //$cRender = new ContentRender($content["type"], $this->getApplication(), null, $renderType, $content["uid"]);
        $result = new \stdClass();
        $result->render = $this->getApplication()->getRenderer()->render($content, $renderType);

        return $result;
    }

    /* ne faire qu'un seul traitement */

    private function processContent($srzContent = null, $persist = true)
    {
        $em = $this->getApplication()->getEntityManager();
        if (is_null($srzContent)) {
            throw new ServicesException("ClassContent.processData data can't be null");
        }

        $srzContent = (object) $srzContent;

        if (false === array_key_exists('uid', $srzContent)) {
            throw new ServicesException('An uid has to be provided');
        }
        if (array_key_exists($srzContent->uid, $this->_processedContent)) {
            return $this->_processedContent[$srzContent->uid];
        }

        if (0 !== strpos($srzContent->type, 'BackBee\ClassContent\\')) {
            $srzContent->type = 'BackBee\ClassContent\\'.$srzContent->type;
        }
        //if (!$srzContent->isAContentSet) {
        $content = $this->getApplication()->getEntityManager()->find($srzContent->type, $srzContent->uid);

        if (null === $content) {
            $classname = $srzContent->type;
            $content = new $classname($srzContent->uid);
            $em->persist($content);
        }

        $this->isGranted('EDIT', $content);

        //Find a draft for content if exists
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($content, $this->getApplication()->getBBUserToken(), true)) {
            $content->setDraft($draft);
        }

        if (!is_null($srzContent->data)) {
            $srzContent->data = $this->prepareContentData($content, $srzContent->data, $srzContent->accept, $srzContent->isAContentSet, $persist);
        }

        if (null !== $srzContent->value && is_string($srzContent->value)) {
            $srzContent->value = html_entity_decode($srzContent->value, ENT_COMPAT, 'UTF-8');
            $srzContent->value = preg_replace('%(?:
                  \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )%xs', '', $srzContent->value);
        }

        $result = $content->unserialize($srzContent);
        $this->_processedContent[$srzContent->uid] = $result; //notify that content is already processed

        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentsData($renderType, $contents = array(), $page_uid = null)
    {
        $result = array();
        if (is_array($contents)) {
            $receiver = null;
            $em = $this->getApplication()->getEntityManager();

            if (null !== $page_uid && (null !== $page = $em->find('BackBee\NestedNode\Page', $page_uid))) {
                $this->getApplication()->getRenderer()->setCurrentPage($page);
            }

            foreach ($contents as $content) {
                $content = (object) $content;
                $cRender = new ContentRender($content->type, $this->getApplication(), null, $renderType, $content->uid);
                if ($cRender) {
                    $classname = '\BackBee\ClassContent\\'.$content->type;
                    if (null === $nwContent = $em->find($classname, $content->uid)) {
                        $nwContent = new $classname();
                    }
                    /* handle param modification */
                    if (isset($content->serializedContent)) {
                        $nwContent = $nwContent->unserialize((object) $content->serializedContent); // @fixme use updateContentRender
                    }

                    /* moved content with old param */
                    $oContent = $cRender->__toStdObject();
                    $oContent->render = $this->getApplication()->getRenderer()->render($nwContent, $renderType);
                    $oContent->serialized = json_decode($nwContent->serialize());
                    $result[] = $oContent;
                }
            }
        }

        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentParameters($nodeInfos = array())
    {
        $contentType = (is_array($nodeInfos) && array_key_exists("type", $nodeInfos)) ? $nodeInfos["type"] : null;
        $contentUid = (is_array($nodeInfos) && array_key_exists("uid", $nodeInfos)) ? $nodeInfos["uid"] : null;
        if (is_null($contentType) || is_null($contentUid)) {
            throw new \Exception("params content.type and content.uid can't be null");
        }
        $contentParams = array();
        $contentTypeClass = "BackBee\ClassContent\\".$contentType;

        $em = $this->getApplication()->getEntityManager();
        if (null === $contentNode = $em->find($contentTypeClass, $contentUid)) {
            $contentNode = new $contentTypeClass($contentUid);
        }

        $this->isGranted('VIEW', $contentNode);

        $default = $contentNode->getDefaultParams();

        // Find a draft if exists
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($contentNode, $this->getApplication()->getBBUserToken())) {
            $contentNode->setDraft($draft);
        }
        $contentParams = $contentNode->getParam();

        // TO-DO : peut-être à déplacer
        if (is_array($contentParams) && true === array_key_exists('selector', $contentParams)
                && true === array_key_exists('array', $contentParams['selector'])
                && true === array_key_exists('parentnode', $contentParams['selector']['array'])
                && true === is_array($contentParams['selector']['array']['parentnode'])
                && 0 < count($contentParams['selector']['array']['parentnode'])) {
            $parentnodeTitle = array();
            $parentnode = array_filter($contentParams['selector']['array']['parentnode']);
            foreach ($parentnode as $page_uid) {
                if (null !== $page = $em->find('BackBee\NestedNode\Page', $page_uid)) {
                    $parentnodeTitle[] = $page->getTitle();
                } else {
                    $parentnodeTitle[] = '';
                }
            }
            $contentParams['selector']['array']['parentnode'] = $parentnode;
            $contentParams['selector']['array']['parentnodeTitle'] = $parentnodeTitle;
        }

        unset($contentParams["indexation"]);

        return $contentParams;
    }

    public function getContentsByCategory($name = "tous")
    {
        $contents = array();
        if ($name == "tous") {
            $categoryList = Category::getCategories($this->getApplication()->getClassContentDir());
            foreach ($categoryList as $cat) {
                $cat->setBBapp($this->getApplication());
                foreach ($cat->getContents() as $content) {
                    $contents[] = $content->__toStdObject();
                }
            }
        } else {
            $category = new Category($name, $this->getApplication());
            foreach ($category->getContents() as $content) {
                $contents[] = $content->__toStdObject();
            }
        }

        return $contents;
    }

    /**
     * @exposed(secured=true)
     */
    public function unlinkColToParent($pageId = null, $contentSetId = null)
    {
        $pageId = (!is_null($pageId)) ? $pageId : false;
        $contentSetId = (!is_null($contentSetId)) ? $contentSetId : false;
        $result = false;
        if (!$pageId || !$contentSetId) {
            throw new \BackBee\Exception\BBException(" a ContentSetId and a PageId must be provided");
        }

        $em = $this->getApplication()->getEntityManager();
        $currentPage = $em->find("BackBee\NestedNode\\Page", $pageId);
        $contentSetToReplace = $em->find("BackBee\ClassContent\ContentSet", $contentSetId);

        if (is_null($contentSetToReplace) || is_null($currentPage)) {
            throw new \BackBee\Exception\BBException(" a ContentSet and a Page must be provided");
        }

        /* current page main contentSet will be modified a draft should be created */
        $pageRootContentSet = $currentPage->getContentSet();
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($pageRootContentSet, $this->getApplication()->getBBUserToken(), true)) {
            $pageRootContentSet->setDraft($draft);
        }

        /* create a draft for the new content */
        $newEmptyContentSet = $contentSetToReplace->createClone();
        $em->persist($newEmptyContentSet);

        //$newEmptyContentSet = new \BackBee\ClassContent\ContentSet();

        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($newEmptyContentSet, $this->getApplication()->getBBUserToken(), true)) {
            $newEmptyContentSet->setDraft($draft);
        }
        $newEmptyContentSet->clear();
        $em->flush();

        /* unlink and update */
        $replace = $em->getRepository('BackBee\NestedNode\Page')->replaceRootContentSet($currentPage, $contentSetToReplace, $newEmptyContentSet);
        if ($replace) {
            $em->getRepository("BackBee\ClassContent\ContentSet")->updateRootContentSetByPage($currentPage, $contentSetToReplace, $newEmptyContentSet, $this->getApplication()->getBBUserToken());

            $em->persist($pageRootContentSet);
            $em->flush();

            //$em->persist($newEmptyContentSet);
            /* render the new contentSet */
            $render = $this->getApplication()->getRenderer()->render($newEmptyContentSet, null);
            $result = array("render" => $render);
        } else {
            throw new \BackBee\Exception\BBException("Error while unlinking zone!");
        }

        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function linkColToParent($pageId = null, $contentSetId = null)
    {
        /* Refaire le lien entre la colonne parent */
        $em = $this->getApplication()->getEntityManager();
        $pageId = (!is_null($pageId)) ? $pageId : false;
        $result = false;

        $contentSetId = (!is_null($contentSetId)) ? $contentSetId : false;

        $contentSetToReplace = $em->find("BackBee\ClassContent\\ContentSet", $contentSetId);
        $currentPage = $em->find("BackBee\NestedNode\\Page", $pageId);

        if (is_null($contentSetToReplace) || is_null($currentPage)) {
            throw new \BackBee\Exception\BBException(" a ContentSet and a Page must be provided");
        }
        /* draft for page's maicontainer */
        $pageRootContentSet = $currentPage->getContentSet();
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($pageRootContentSet, $this->getApplication()->getBBUserToken(), true)) {
            $pageRootContentSet->setDraft($draft);
        }

        $parentZoneAtSamePosition = $currentPage->getParentZoneAtSamePositionIfExists($contentSetToReplace);
        if (!$parentZoneAtSamePosition || is_null($parentZoneAtSamePosition)) {
            return false;
        }

        /* draft for parentSimilaireZone */
        if (null !== $draft = $em->getRepository('BackBee\ClassContent\Revision')->getDraft($parentZoneAtSamePosition, $this->getApplication()->getBBUserToken(), true)) {
            $parentZoneAtSamePosition->setDraft($draft);
        }

        /* replace page's zone here */
        $replace = $currentPage->replaceRootContentSet($contentSetToReplace, $parentZoneAtSamePosition, false);
        if ($replace) {
            $em->getRepository("BackBee\ClassContent\ContentSet")->updateRootContentSetByPage($currentPage, $contentSetToReplace, $parentZoneAtSamePosition, $this->getApplication()->getBBUserToken());
            $em->persist($pageRootContentSet);
            $em->flush();
            $render = $this->getApplication()->getRenderer()->render($parentZoneAtSamePosition, null);
            $result = array("render" => $render, "newContentUid" => $parentZoneAtSamePosition->getUid());
        } else {
            throw new \BackBee\Exception\BBException("Error while linking zone!");
        }

        return $result;

        /* flush
         * $em->flush();
         */
    }

    /**
     * @exposed(secured=true)
     */
    public function showContentsPage($contentUid, $contentType)
    {
        $em = $this->getApplication()->getEntityManager();
        $content = $em->find("BackBee\\ClassContent\\".$contentType, $contentUid);
        if ($content == null) {
            throw new \Exception("content can't be null");
        }
        $pages = $em->getRepository("BackBee\ClassContent\AClassContent")->findPagesByContent($content);
        $results = new \stdClass();
        $results->pages = array();
        if (is_array($pages) && !empty($pages)) {
            foreach ($pages as $page) {
                $pageObject = new \stdClass();
                $pageObject->title = $page->getTitle();
                $pageObject->uid = $page->getUid();
                $results->pages[] = $pageObject;
            }
        }

        return $results;
    }

    /**
     * @exposed(secured=true)
     */
    public function deleteContent($contentUid, $contentType)
    {
        try {
            $em = $this->getApplication()->getEntityManager();
            $content = $em->find("BackBee\\ClassContent\\".$contentType, $contentUid);
            if ($content == null) {
                throw new \Exception("content can't be null");
            }
            $em->getRepository("BackBee\ClassContent\AClassContent")->deleteContent($content);
            $em->flush();
        } catch (\Exception $e) {
            throw new \Exception("Content can't be deleted");
        }
    }

    /**
     * @exposed(secured=true)
     */
    public function getPageLinkedZones($pageId = null)
    {
        $result = array("mainZones" => null, "linkedZones" => array());
        $pageId = (!is_null($pageId)) ? $pageId : false;
        if (!$pageId) {
            return $result;
        }
        $em = $this->getApplication()->getEntityManager();
        $currentPage = $em->find("BackBee\NestedNode\\Page", $pageId);
        $mainZones = null;
        if (!is_null($currentPage)) {
            $mainZones = $currentPage->getPageMainZones();
            $linkedZones = array_keys($currentPage->getInheritedZones());
            $result["linkedZones"] = $linkedZones;
        }
        if ($mainZones) {
            $result["mainZones"] = array_keys($mainZones);
        }

        return $result;
    }

    /**
     * @exposed(secured=true)
     * For every fields in a content, say if the content is editable
     */
    public function getContentsRteParams($contentType = null, $rte = null)
    {
        $contentTypeClass = "BackBee\ClassContent\\".$contentType;
        $content = new $contentTypeClass();
        $editable = array();
        if (!is_a($contentTypeClass, 'BackBee\ClassContent\ContentSet')) {
            $config = $this->getApplication()->getConfig();
            $rteMainConfig = $config->getSection("rteconfig");
            if (!array_key_exists("config", $rteMainConfig) || !is_array($rteMainConfig["config"])) {
                throw new \Exception("rte config can't be found");
            }
            $rteConfig = $rteMainConfig["config"];
            if (array_key_exists("adapter", $rteConfig)) {
                $adapter = $rteConfig["adapter"];
                $adapterConfig = $rteMainConfig[$adapter];
                if (!is_array($adapterConfig) || empty($adapterConfig) || !array_key_exists("customconf", $adapterConfig)) {
                    throw new \Exception("adapter config is not valid");
                }
                $customConf = (is_array($adapterConfig["customconf"])) ? $adapterConfig["customconf"] : array();

                /* handle default config here */

                $elements = $content->getData();
                $editable = new \stdClass();
                foreach ($elements as $key => $item) {
                    if (is_a($content->$key, "BackBee\ClassContent\AClassContent")) {
                        $rteStyle = $content->{$key}->getParam('aloha', 'scalar');
                        if (is_object($content->$key) && ($content->{$key}->getParam('editable', 'boolean') == true && null !== $rteStyle)) {
                            /* if the style doesn't exist */
                            if (!array_key_exists($rteStyle, $customConf)) {
                                throw new \Exception("rte conf '".$rteStyle."' can't be found. Add it to your config.");
                            }
                            $editable->{$key} = $rteStyle;
                        }
                    }
                }
                $result = new \stdClass();
                $result->editables = $editable;

                return $result;
            }
        }

        return $editable;
    }
}
