<?php

namespace BackBuilder\Services\Local;

use BackBuilder\Services\Local\AbstractServiceLocal,
    BackBuilder\Services\Content\Category,
    BackBuilder\Cache\File\Cache,
    BackBuilder\Services\Content\ContentRender,
    BackBuilder\Services\Exception\ServicesException,
    BackBuilder\Util\String;
    


class ContentBlocks extends AbstractServiceLocal
{

    const CONTENT_PATH = "BackBuilder\\ClassContent\\";

    private $_availableMedias;

    /**
     * @exposed(secured=true)
     */
    public function getContentsByCategory($name = "tous")
    {
        $contents = array();
        $cache = $this->bbapp->getBootstrapCache();
        $cachedClassContents = $cache->load(Category::getCacheKey(), true);

        if ($name == "tous") {
            if (false !== $cachedClassContents) {
                $contents = json_decode($cachedClassContents);
            } else {
                $categoryList = Category::getCategories($this->bbapp);
                foreach ($categoryList as $cat) {
                    $cat->setBBapp($this->bbapp);
                    foreach ($cat->getContents() as $content)
                        $contents[] = $content->__toStdObject(false);
                }
                $cache->save(Category::getCacheKey(), json_encode($contents));
            }
        } else {
            $category = new Category($name, $this->bbapp);
            foreach ($category->getContents() as $content)
                $contents[] = $content->__toStdObject(false);
        }

        return $contents;
    }

    /**
     * @exposed : true
     * @protected : true
     */
    public function getCategories()
    {
        $categories = array();
        $categoryList = Category::getCategories($this->bbapp);
        foreach ($categoryList as $category)
            $categories[] = $category->__toStdObject();
        return $categories;
    }

    /* bb ContentType - methods */

    private function contentToLeaf($contents)
    {
        $result = array();
        foreach ($contents as $content) {
            $node = new \stdClass();
            $node->attr = new \stdClass();
            $node->attr->rel = "contentType_" . $content->name;
            $node->attr->id = "node_" . uniqid();
            $node->data = $content->label;
            $node->state = "leaf";
            $result[] = $node;
        }
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getDataKeywordsAutoComplete($cond)
    {
        $keyWords = $this->bbapp->getEntityManager()->getRepository('BackBuilder\NestedNode\KeyWord')->getLikeKeyWords($cond);
        $result = array();
        foreach ($keyWords as $key) {
            $std = new \stdClass();
            $std->label = $key->getKeyWord();
            $std->value = $key->getUid();
            $result[] = $std;
        }
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBContentBrowserTree($filters = array())
    {

        $tree = array();
        $children = array();


        $root = new \stdClass();
        $root->attr = new \stdClass();
        $root->attr->rel = "root";
        $root->attr->id = 'node_0';
        $root->data = "Root";
        $root->children = &$children;
        $root->state = "open";
        $tree[] = $root;
        $accepts = (array_key_exists("accept", $filters) && $filters["accept"] != 'all') ? explode(',', trim($filters["accept"])) : "all";
        $useFilter = ( $accepts == "all") ? false : true;

        $categories = ($useFilter) ? null : Category::getCategories($this->bbapp);

        /* all */
        if ($useFilter) {
            /* Ajouter à la racine */
            foreach ($accepts as $accept) {
                $class = '\BackBuilder\ClassContent\\' . $accept;
                $object = new $class();
                $leaf = new \stdClass();
                $leaf->attr = new \stdClass();
                $leaf->attr->rel = "contentType_" . $accept;
                $leaf->attr->id = uniqid();
                $leaf->data = $object->getProperty('name');
                $leaf->state = "leaf";
                $children[] = $leaf;
            }
        } else {
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    $catInfos = $category->__toStdObject();
                    $categoryContents = $this->getContentsByCategory($catInfos->name);
                    $leaf = new \stdClass();
                    $leaf->attr = new \stdClass();
                    $leaf->attr->rel = $catInfos->name;
                    $leaf->attr->id = 'node_' . $catInfos->uid;
                    $leaf->data = $catInfos->label;
                    $leaf->state = 'leaf';
                    $leaf->children = $this->contentToLeaf($categoryContents);
                    $children[] = $leaf;
                }
            }
        }

        return $tree;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentsListByCategory($catName = null, $order_sort = '_title', $order_dir = 'asc', $limit = 5, $start = 0)
    {
        $result = array("numResults" => 0, "rows" => array());
        if (!$catName)
            return $result;

        $em = $this->bbapp->getEntityManager();
        $contentsList = array();
        $limitInfos = array("start" => (int) $start, "limit" => (int) $limit);
        $orderInfos = array("column" => $order_sort, "dir" => $order_dir);
        $isCat = (strpos($catName, "contentType") === FALSE) ? true : false; //contentType
        if ($isCat) {
            $contents = $this->getContentsByCategory(strtolower($catName));
        } else {
            $contents = array();
            $nodeInfos = explode("_", $catName);
            $mock = new \stdClass();
            $mock->name = $nodeInfos[1];
            $contents[] = $mock;
        }

        $classnames = array();
        foreach ($contents as $content) {
            $contentTypeClass = "BackBuilder\ClassContent\\" . $content->name;
            $classnames[] = $contentTypeClass;
        }

        $result["numResults"] = $em->getRepository("BackBuilder\ClassContent\AClassContent")->countContentsByClassname($classnames);
        $items = $em->getRepository("BackBuilder\ClassContent\AClassContent")->findContentsByClassname($classnames, $orderInfos, $limitInfos);
        if ($items) {
            foreach ($items as $item) {
                try {
                    $itemClass = get_class($item);
                    $itemProperties = $item->getDataToObject();
                    $itemHasATitle = array_key_exists("title", $itemProperties);
                    $currentItemTitle = ($itemHasATitle && $item->title != null) ? $item->title->getData("value") : $item->getProperty("name") . " " . $item->getUid();
                    $contentInfos = new \stdClass();
                    $contentInfos->uid = $item->getUid();
                    $contentInfos->title = $currentItemTitle;
                    $contentInfos->ico = str_replace(ContentBlocks::CONTENT_PATH, "", $itemClass);
                    $contentInfos->type = str_replace(ContentBlocks::CONTENT_PATH, "", $itemClass);
                    $contentInfos->classname = $itemClass;
                    $contentsList[] = $contentInfos;
                } catch (\Exception $e) {
                    /*                     * decrément total en cas d'erreur* */
                    $result["numResults"] = (int) $result["numResults"] - 1;
                    continue;
                }
            }
            $result["rows"] = $contentsList;
        }
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function searchContent($params = array(), $order_sort = '_title', $order_dir = 'asc', $limit = 5, $start = 0)
    {
        $catName = (isset($params['typeField'])) ? $params['typeField'] : $params['catName'];
        $result = array("numResults" => 0, "rows" => array());
        if (!$catName)
            return $result;

        $em = $this->bbapp->getEntityManager();
        $contentsList = array();
        $limitInfos = array("start" => (int) $start, "limit" => (int) $limit);
        $orderInfos = array("column" => $order_sort, "dir" => $order_dir);
        $isCat = (strpos($catName, "contentType") === FALSE) ? true : false; //contentType_ || categorie
        if ($isCat) {
            $contents = $this->getContentsByCategory(strtolower($catName));
        } else {
            $contents = array();
            $nodeInfos = explode("_", $catName);
            $mock = new \stdClass();
            $mock->name = $nodeInfos[1];
            $contents[] = $mock;
        }

        $classnames = array();
        foreach ($contents as $content) {
            $contentTypeClass = "BackBuilder\ClassContent\\" . $content->name;
            $classnames[] = $contentTypeClass;
        }
        /* default value is true */
        $params["limitToOnline"] = false;
        $params["site_uid"] = $this->bbapp->getSite()->getUid();
        $result["numResults"] = $em->getRepository("BackBuilder\ClassContent\AClassContent")->countContentsBySearch($classnames, $conditions = $params);
        $items = $em->getRepository("BackBuilder\ClassContent\AClassContent")->findContentsBySearch($classnames, $orderInfos, $limitInfos, $conditions = $params);
        if ($items) {
            foreach ($items as $item) {
                try {
                    $itemClass = get_class($item);
                    $itemProperties = $item->getDataToObject();
                    $itemHasATitle = array_key_exists("title", $itemProperties);
//                    $currentItemTitle = ($itemHasATitle && $item->title != null) ? $item->title->getData("value") : $item->getProperty("name") . " " . $item->getUid();
                    $currentItemTitle = (null === $item->getLabel()) ? $item->getProperty("name") . " " . $item->getUid() : $item->getLabel();
                    $contentInfos = new \stdClass();
                    $contentInfos->uid = $item->getUid();
                    $contentInfos->title = String::truncateText($currentItemTitle,50); //truncate
                    $contentInfos->ico = str_replace(ContentBlocks::CONTENT_PATH, "", $itemClass);
                    $contentInfos->type = str_replace(ContentBlocks::CONTENT_PATH, "", $itemClass);
                    $contentInfos->classname = $itemClass;
                    $contentInfos->created = $item->getCreated()->format("d/m/Y");
                    $contentInfos->completeTitle = $currentItemTitle;

                    $contentsList[] = $contentInfos;
                } catch (\Exception $e) {
                    /*                     * decrément total en cas d'erreur* */
                    $result["numResults"] = (int) $result["numResults"] - 1;
                    continue;
                }
            }
            $result["rows"] = $contentsList;
        }
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getInfosContent($name)
    {
        if ($name !== null && $name !== "") {

            $contentObject = new ContentRender($name, $this->bbapp);
            $stdClass = new \stdClass();

            $stdClass->contentCat = $contentObject->getCategory();
            $stdClass->title = $contentObject->getName();
            $stdClass->accepted_items = "all";
            $stdClass->max_item = "unlimited";
            $stdClass->max_width_droppable = 16;
            $stdClass->min_width_droppable = 2;
            $stdClass->uid = uniqid();
            return $stdClass;
        }
    }

    /**
     * @exposed(secured=true)
     */
    public function getDataContentType($name, $mode = null, $uid = NULL, $receiverclass = NULL, $receiveruid = NULL)
    {
        $content = new ContentRender($name, $this->bbapp, null, $mode, $uid);
        $result = $content->__toStdObject();
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentBlocks($catName = "tous", $withCategory = true)
    {

        /* Content catégories */
        $response = array();

        $contentCatContainer = array();
        if ($withCategory) {
            $contentCatContainer = $this->getCategories();
        }
        $response["contentCategories"] = $contentCatContainer;
        $response["contentList"] = $this->getContentsByCategory($catName);
        $response["selectedCategory"] = $catName;
        return $response;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentParameters($nodeInfos = array())
    {
        $contentType = (is_array($nodeInfos) && array_key_exists("type", $nodeInfos)) ? $nodeInfos["type"] : null;
        $contentUid = (is_array($nodeInfos) && array_key_exists("uid", $nodeInfos)) ? $nodeInfos["uid"] : null;
        if (is_null($contentType) || is_null($contentUid))
            throw new \Exception("params content.type and content.uid can't be null");
        $contentParams = array();
        $contentTypeClass = "BackBuilder\ClassContent\\" . $contentType;

        $em = $this->bbapp->getEntityManager();
        if (NULL === $contentNode = $em->find($contentTypeClass, $contentUid)) {
            $contentNode = new $contentTypeClass($contentUid);
        }

        $this->isGranted('VIEW', $contentNode);
        
        // Find a draft if exists
        if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($contentNode, $this->bbapp->getBBUserToken()))
            $contentNode->setDraft($draft);

        $contentParams = $contentNode->getParam();

        unset($contentParams["indexation"]);
        return $contentParams;
    }

   /**
     * @exposed(secured=true)
     */
    public function updateContentparameters($params = null, $contentInfos = null)
    {
        if (is_null($params) || !is_array($params))
            throw new \Exception("params can't be null");
        // var_dump($params);
        $contentTypeClass = "BackBuilder\ClassContent\\" . $contentInfos["contentType"];

        $em = $this->bbapp->getEntityManager();
        if (NULL === $contentNode = $em->find($contentTypeClass, $contentUid)) {
            $contentNode = new $contentTypeClass($contentUid);
            $em->persist($contentNode);
        }
        
        $this->isGranted('EDIT', $contentNode);

        // Find a draft if exists
        if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($contentNode, $this->bbapp->getBBUserToken(), true))
            $contentNode->setDraft($draft);

        foreach ($params as $key => $param) {
            $contentNode->setParam($key, $param, is_array($param) ? 'array' : null);
        }

        $em->flush();
    }

    /**
     * @exposed(secured=true)
     */
    public function removeContent($contentType = null, $contentUid = null)
    {
        $result = new \StdClass();
        $result->ok = true;
        return $result;
    }

    /**
     * @exposed(secured=true)
     */
    public function getContentEditionForm($contentType, $contentUid, $disabled = false)
    {
        if (null === $contentType) {
            throw new ServicesException('No classname provided');
        }

        $content_classname = 'BackBuilder\\ClassContent\\' . $contentType;
        if (false === class_exists($content_classname)) {
            throw new ServicesException(sprintf('Unknown content classname provided `%s`', $content_classname));
        }

        if (null === $contentUid) {
            throw new ServicesException('No content uid provided');
        }

        if (null === $content = $this->em->find($content_classname, $contentUid)) {
            $content = new $content_classname($contentUid);
        }
        
        $this->isGranted('EDIT', $content);

        // Find a draft if exists
        if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $this->bbapp->getBBUserToken())) {
            $content->setDraft($draft);
        }

        $result = new \StdClass();
        $result->bb5_uid = $content->getUid();
        $result->bb5_name = $content->getProperty('name');
        $result->bb5_type = get_class($content);
        $result->bb5_form = new \StdClass();
        $result->message = "";

        if (is_array($this->_getAvailableMedias()) && true === in_array(get_class($content), $this->_getAvailableMedias())) {
            $media = $this->em->getRepository('BackBuilder\NestedNode\Media')->findBy(array('_content' => $content));
            $result->bb5_media = $disabled = (0 < count($media));
        }

        if (false === ($content instanceof \BackBuilder\ClassContent\ContentSet)) {
            $renderer = $this->bbapp->getRenderer();

            foreach ($content->getData() as $key => $subcontent) {
                $result->bb5_form->$key = new \StdClass();
                $result->bb5_form->$key->bb5_value = array();
                $result->bb5_form->$key->bb5_uid = array();

                if (NULL === $content->$key) {
                    $newContent = $content->getAcceptedType($key);
                    $subcontent = new $newContent();
                }

                $subcontent = (!is_array($subcontent)) ? array($subcontent) : $subcontent;
                foreach ($subcontent as $index => $value) {
                    if (false === is_object($value)) {
                        $result->bb5_form->$key->bb5_uid = NULL;
                        $result->bb5_form->$key->bb5_type = 'scalar';
                        $result->bb5_form->$key->bb5_fieldset = false;
                        $result->bb5_form->$key->bb5_isLoaded = (NULL !== $value) ? true : false;
                        $result->bb5_form->$key->bb5_value[] = htmlentities($value, ENT_QUOTES, 'UTF-8');
                    } elseif ($value instanceof \BackBuilder\ClassContent\AClassContent
                            && false === ($value instanceof \BackBuilder\ClassContent\ContentSet)) {
                        // Find a draft if exists
                        if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($value, $this->bbapp->getBBUserToken())) {
                            $value->setDraft($draft);
                        }

                        if (true === $value->isElementContent()) {
                            $result->bb5_form->$key->bb5_uid[$index] = $value->getUid();
                            $result->bb5_form->$key->bb5_type = get_class($value);
                        }

                        $result->bb5_form->$key->bb5_fieldset = (true === $value->isElementContent()) ? false : true;
                        $result->bb5_form->$key->bb5_isLoaded = $value->isLoaded() ? true : false;
                        $result->bb5_form->$key->bb5_value[$index] = (true === $value->isElementContent()) ? $renderer->render($value, 'bbcontent_edit', array('disabled' => $disabled)) : $this->getContentEditionForm(str_replace('BackBuilder\ClassContent\\', '', get_class($value)), $value->getUid(), $disabled);
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * @exposed(secured=true)
     */
    public function postContentEditionForm($contentType, $contentUid, $contentValues)
    {
        if (null === $contentType) {
            throw new ServicesException('No classname provided');
        }

        $content_classname = 'BackBuilder\\ClassContent\\' . $contentType;
        if (false === class_exists($content_classname)) {
            throw new ServicesException(sprintf('Unknown content classname provided `%s`', $content_classname));
        }

        if (null === $contentUid) {
            throw new ServicesException('No content uid provided');
        }

        if (null === $content = $this->em->find($content_classname, $contentUid)) {
            $content = new $content_classname($contentUid);
            $this->em->persist($content);
        }
        
        $this->isGranted('EDIT', $content);
        
        //json_decode($contentValues);
        $content = $this->_postContent($content, json_decode($contentValues));

        $this->em->flush();

        $return = new \stdClass();
        $return->uid = $content->getUid();
        $return->classname = str_replace('BackBuilder\\ClassContent\\', '', get_class($content));

        return $return;
    }

    private function removeKeyword($id, $content)
    {
        if (NULL === $id)
            return (0);
        $realKeyword = $this->bbapp->getEntityManager()->find('BackBuilder\NestedNode\KeyWord', $id);
        $realKeyword->removeContent($content);
        $this->bbapp->getEntityManager()->flush();
    }

    private function _postContent($content, $contentValues)
    {
        if (false === ($content instanceof \BackBuilder\ClassContent\AClassContent)) {
            return $content;
        }

        if (false === is_object($contentValues)) {
            throw new ServicesException('Invalid datas provided');
        }

        // Attach the content if not
        if (false === $this->em->contains($content)) {
            $content = $this->em->find(get_class($content), $content->getUid());
            if (null === $content)
                return null;
        }

        // If in media library, do nothing
        if (true === in_array(get_class($content), $this->_getAvailableMedias())) {
            $media = $this->em->getRepository('BackBuilder\NestedNode\Media')->findBy(array('_content' => $content));
            if (0 < count($media)) {
                return $content;
            }
        }
        
        $this->isGranted('EDIT', $content);

        // Find a draft, checkout it if not exists
        if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $this->bbapp->getBBUserToken(), true)) {
            $content->setDraft($draft);
        }

        $maxentries = $content->getMaxEntry();
        foreach ($contentValues as $key => $attributeArray) {
            $maxentry = (true === array_key_exists($key, $maxentries)) ? $maxentries[$key] : 0;
            $newvalues = array();

            foreach ($attributeArray as $index => $value) {
                if (0 < $maxentry && count($newvalues) == $maxentry) {
                    break;
                }

                if (false === is_object($value)) {
                    throw new ServicesException('Invalid datas provided');
                }

                if (true === property_exists($value, 'form') && true === is_object($value->form)) {
                    if (true === property_exists($value, 'delete') && true === $value->delete) {
                        $newContent = $content->getAcceptedType($key);
                        $subcontent = new $newContent();
                        $this->em->persist($subcontent);
                        $newvalues[] = $subcontent;
                    } else {
                        $newvalues[] = $this->_postContent($content->$key, $value->form);
                    }
                } else {
                    if (false === property_exists($value, 'type') || false === property_exists($value, 'value')) {
                        continue;
                    }

                    if ('scalar' === $value->type) {
                        $newvalues[] = html_entity_decode($value->value, ENT_COMPAT, 'UTF-8');
                    } else {
                        $subcontent = $content->$key;

                        if (true === is_array($subcontent)) {
                            $subcontent = (true === array_key_exists($index, $subcontent)) ? $subcontent[$index] : null;
                        } elseif (0 < $index) {
                            $subcontent = null;
                        }

                        // Attach the subcontent if not
                        if (null !== $subcontent && false === $this->em->contains($subcontent)) {
                            $subcontent = $this->em->find(get_class($subcontent), $subcontent->getUid());
                        }

                        // Create a new content if null
                        if (null === $subcontent) {
                            $newContent = $content->getAcceptedType($key);
                            $subcontent = new $newContent();
                            $this->em->persist($subcontent);
                        }

                        // Find a draft, checkout it if not exists
                        if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($subcontent, $this->bbapp->getBBUserToken(), true)) {
                            $subcontent->setDraft($draft);
                        }

                        if (true === property_exists($value, 'delete') && true === $value->delete) {
                            $this->em->getRepository(get_class($subcontent))
                                    ->setDirectories($this->bbapp)
                                    ->removeFromPost($subcontent, $value, $content);
                        } else {
                            $newvalues[] = $this->em->getRepository(get_class($subcontent))
                                    ->setDirectories($this->bbapp)
                                    ->getValueFromPost($subcontent, $value, $content);
                        }
                    }
                }

                $content->$key = $newvalues;
            }
        }
        return $content;
    }

    /**
     * @exposed(secured=true)
     * @codeCoverageIgnore
     */
    public function handleContentChange($contentsArr = array())
    {
        $result = array("result" => $contentsArr);
        return $result;
    }

    

     private function _getAvailableMedias()
    {
        if (null === $this->_availableMedias) {
            $this->_availableMedias = array();
            $sMedia = new Media();
            $sMedia->initService($this->bbapp);
            $availableMedias = $sMedia->getBBSelectorAvailableMedias();
            if (is_array($availableMedias)) {
                foreach ($availableMedias as $media) {
                    $this->_availableMedias[] = $media->classname;
                }
            }
        }
        
        return $this->_availableMedias;        
    }

}
