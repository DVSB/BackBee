<?php

namespace BackBuilder\Renderer\Helper;

class datacontent extends AHelper {

    private function _unprefixClassname($classname) {
        return str_replace('BackBuilder\ClassContent\\', '', $classname);
    }

    public function __invoke($datacontent = NULL, $params = array()) {
        if (NULL === $datacontent)
            $datacontent = array();

        if ($this->_renderer->getParam('class')) {
            if (!array_key_exists('class', $datacontent))
                $datacontent['class'] = '';
            $datacontent['class'] = trim(implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']), (array) $this->_renderer->getParam('class')))));
        }
        $cloneDatacontent = $datacontent; 
        if (NULL !== $this->_renderer->getApplication()->getBBUserToken()) {
            $object = $this->_renderer->getObject();
            if (NULL !== $object) {
                if (!array_key_exists('class', $datacontent))
                    $datacontent['class'] = '';
                $datacontent['class'] = trim(implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']), array('contentAloha')))));

                $contentNodeClass = "";
                $applicationConfig = $this->_renderer->getApplication()->getConfig();
                if ($contentMarkup = $applicationConfig->getContentmarkupConfig()) {
                    if (is_array($contentMarkup) && array_key_exists("contentclass", $contentMarkup)) {
                        /* n'ajoute la classe que pour les contenus ayant une catégorie */
                        $nodeCat = $object->getProperty("category");
                        if ((is_array($nodeCat)) && count($nodeCat) != 0) {
                            if (!array_key_exists("class", $datacontent))
                                $datacontent['class'] = '';
                            $datacontent['class'] = trim($datacontent['class']) . " " . trim($contentMarkup["contentclass"]);
                        }
                    }
                }

                $datacontent['data-uid'] = $object->getUid();
                $datacontent['data-type'] = $this->_unprefixClassname(get_class($object));
                //if ($object instanceof \BackBuilder\ClassContent\article){echo "<pre>";var_dump($object->getProperty('forbiden-actions'));die;}
                if ($object->getProperty('forbiden-actions') && is_array($object->getProperty('forbiden-actions'))) {
                    $datacontent['data-forbidenactions'] = implode(',', $object->getProperty('forbiden-actions'));
                } else {
                    $datacontent['data-forbidenactions'] = "";
                }
                if ($this->_renderer->getParentUid()) {
                    $datacontent['data-parent'] = $this->_renderer->getParentUid();
                }

                if ($this->_renderer->getCurrentElement()) {
                    $datacontent['data-element'] = $this->_renderer->getCurrentElement();
                }
                if (NULL !== $draft = $object->getDraft())
                    $datacontent['data-draftuid'] = $draft->getUid();

                $maxEntry = $object->getMaxEntry();
                $datacontent['data-maxentry'] = (is_array($maxEntry) && count($maxEntry)) ? (array_key_exists("value", $maxEntry) ? $maxEntry["value"] : "") : "";
                $rendermode = @array_pop($object->getParam('rendermode'));
                $datacontent['data-rendermode'] = ((NULL !== $object->getMode()) ? $object->getMode() : $this->_renderer->getMode());
                $datacontent['data-isloaded'] = ($object->isLoaded() ? 'true' : 'false');
                if (is_a($object, '\BackBuilder\ClassContent\ContentSet')) {
                    if (!array_key_exists('class', $datacontent)) {
                        $datacontent['class'] = '';
                    }
                    $datacontent['class'] = implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']), array('bb5-droppable-item'))));
                    /**
                     * itemcontainer is used when items in a contentset are not directly appended to the contentset 
                     */
                    if (is_array($object->getParam('itemcontainer'))) {
                        $param = array_pop($object->getParam('itemcontainer'));
                        $datacontent['data-itemcontainer'] = (!empty($param)) ? (string) $param : "";
                        /* if the contentset has an itemcontainer, it MUST not be droppable (its itemcontainer SHOULD BE */
                        if (!empty($param)) {
                            if (isset($contentMarkup)) {
                                if (is_array($contentMarkup) && array_key_exists("droppableclass", $contentMarkup)) {
                                    if (array_key_exists("class", $datacontent) && !empty($datacontent["class"])) {
                                        $classInfos = explode(' ', $datacontent['class']);
                                        $classKey = array_search(trim($contentMarkup["droppableclass"]), $classInfos);
                                        if ($classKey !== false) {
                                            array_slice($classInfos, $classKey);
                                            $datacontent['class'] = implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']))));
                    } 
                                    }
                                }
                            }
                        }

                        /* handle params */
                        if (isset($params) && is_array($params)) {
                            if (array_key_exists("useItemcontainer", $params)) {
                                if (isset($contentMarkup)) {
                                    /* reset datacontent */
                                    if (is_array($contentMarkup) && array_key_exists("droppableclass", $contentMarkup)) {
                                        $nodeClasses = array($contentMarkup["droppableclass"]);
                                        //$classesStr = implode(" ", $nodeClasses);
                                        if (!array_key_exists('class', $cloneDatacontent)){ $cloneDatacontent['class'] = '';}
                                        $cloneDatacontent['class'] = trim(implode(' ', array_unique(array_merge(explode(' ', $cloneDatacontent['class']), $nodeClasses))));
                                        $cloneDatacontent['data-refparent'] = $object->getUid();
                                        $datacontent = $cloneDatacontent;
                                    }
                                }
                            }
                        }
                    }
                    if (NULL !== $object->getAccept()) {
                        $datacontent['data-accept'] = implode(',', array_map(array('BackBuilder\Renderer\Helper\datacontent', '_unprefixClassname'), (array) $object->getAccept()));
                    }
                } else if (NULL !== $this->_renderer->getClassContainer() && is_a($this->_renderer->getClassContainer(), '\BackBuilder\ClassContent\ContentSet')) {
                    if (!array_key_exists('class', $datacontent))
                        $datacontent['class'] = '';

                    $datacontent['class'] = trim(implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']), array('bb5-draggable-item')))));

                    if (FALSE !== strpos($datacontent['class'], ' span'))
                        $datacontent['class'] = trim(implode(' ', array_unique(array_merge(explode(' ', $datacontent['class']), array('bb5-resizable-item')))));
                } else {
                    if ($this->_renderer->getCurrentElement()) {
                        $datacontent['data-aloha'] = $this->_renderer->getCurrentElement();
                    }
                }
                
                if ($object instanceof \BackBuilder\ClassContent\Element\file) {
                    $em = $this->_renderer->getApplication()->getEntityManager();
                    $datacontent['data-library'] =$em->getRepository('BackBuilder\ClassContent\Element\file')->isInMediaLibrary($object);
                }
            }
        }
        
         $map_function = array_map(function($k, $v) {
                    if (TRUE === is_array($v))
                        $v = NULL;
                    return $k . '="' . $v . '"';
                }, array_keys($datacontent), array_values($datacontent));

        return implode(' ', $map_function);
    }

}
