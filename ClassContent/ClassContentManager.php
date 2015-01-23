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

namespace BackBee\ClassContent;

use BackBee\ClassContent\AClassContent;
use BackBee\IApplication;
use BackBee\Routing\RouteCollection;
use BackBee\Security\Token\BBUserToken;
use BackBee\Utils\File\File;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class ClassContentManager
{
    private $application;
    private $categoryManager;
    private $contents;
    private $em;
    private $thumbnailBaseDir;
    private $token;

    /**
     * Instantiate a ClassContentManager.
     *
     * @param IApplication $application
     */
    public function __construct(IApplication $application)
    {
        $this->application = $application;
        $this->em = $application->getEntityManager();
        $this->categoryManager = $application->getContainer()->get('classcontent.category_manager');

        $this->contents = new \SplObjectStorage();
    }

    /**
     * Setter of BBUserToken.
     *
     * @param BBUserToken $token
     */
    public function setBBUserToken(BBUserToken $token = null)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Updates the content which provided data.
     *
     * @param  AClassContent $content
     * @param  array         $data    array of data that must contains parameters and/or elements key
     * @return AClassContent
     * @throws \InvalidArgumentException if provided data doesn't have parameters and elements key
     */
    public function update(AClassContent $content, array $data)
    {
        if (!isset($data['parameters']) && !isset($data['elements'])) {
            throw new \InvalidArgumentException('Provided data is not valid for ClassContentManager::update.');
        }

        return $this->updateElements($content, $data['elements']);
    }

    /**
     * Calls ::jsonSerialize of the content and build valid url for image
     *
     * @param  AClassContent $content
     * @param  integer       $format
     * @return array
     */
    public function jsonEncode(AClassContent $content, $format = AClassContent::JSON_DEFAULT_FORMAT)
    {
        if (AClassContent::JSON_DEFINITION_FORMAT === $format) {
            $classname = get_class($content);
            $content = new $classname;
        }

        return $this->updateClassContentImageUrl($content->jsonSerialize($format));
    }

    /**
     * Calls ::jsonSerialize on all contents and build valid url for image.
     *
     * It can manage collection type of array or object that implements \IteratorAggregate and/or \Traversable.
     *
     * @param Paginator $paginator the paginator to convert
     * @return array
     * @throws \InvalidArgumentException if provided collection is not supported type
     */
    public function jsonEncodeCollection($collection, $format = AClassContent::JSON_DEFAULT_FORMAT)
    {
        if (
            !is_array($collection)
            && !($collection instanceof \IteratorAggregate)
            && !($collection instanceof \Traversable)
        ) {
            throw new \InvalidArgumentException(
                'Collection must be type of array or an object that implements \IteratorAggregate and/or \Traversable.'
            );
        }

        $contents = [];
        if (AClassContent::JSON_DEFINITION_FORMAT === $format) {
            if (is_object($collection) && $collection instanceof Paginator) {
                $contents[] = $this->jsonEncode($collection->getIterator()->current(), $format);
            } elseif (is_array($collection)) {
                $contents[] = array_pop($collection);
            }
        } else {
            foreach ($collection as $content) {
                $contents[] = $this->jsonEncode($content, $format);
            }
        }

        return $contents;
    }

    /**
     * Returns all classcontents classnames
     *
     * @return array An array that contains all classcontents classnames
     */
    public function getAllClassContentClassnames()
    {
        $classnames = [];
        foreach ($this->categoryManager->getCategories() as $category) {
            foreach ($category->getBlocks() as $block) {
                $classnames[] = AClassContent::getClassnameByContentType($block->type);
            }
        }

        return array_merge($this->getAllElementClassContentClassnames(), $classnames);
    }

    /**
     * Returns classnames of all classcontents element
     *
     * @return array Contains every BackBee's element classcontent classnames
     */
    public function getAllElementClassContentClassnames()
    {
        $directory = $this->application->getBBDir().DIRECTORY_SEPARATOR.'ClassContent';

        $classnames = array_map(
            function ($path) use ($directory) {
                return str_replace(
                    [DIRECTORY_SEPARATOR, '\\\\'],
                    [NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR],
                    AClassContent::CLASSCONTENT_BASE_NAMESPACE.str_replace([$directory, '.yml'], ['', ''], $path)
                );
            },
            File::getFilesRecursivelyByExtension($directory, 'yml')
        );

        $classnames[] = AClassContent::CLASSCONTENT_BASE_NAMESPACE.'ContentSet';

        return $classnames;
    }

        /**
     * Returns current revision for the given $content
     *
     * @param AClassContent $content content we want to get the latest revision
     * @return null|BackBee\ClassContent\Revision
     */
    public function getRevision(AClassContent $content, $checkoutOnMissing = false)
    {
        return $this->em->getRepository('BackBee\ClassContent\Revision')->getDraft(
            $content,
            $this->token ?: $this->application->getBBUserToken(),
            $checkoutOnMissing
        );
    }

    /**
     * Alias to ClassContentRepository::findOneByTypeAndUid. In addition, it can also manage content's revision.
     *
     * @see BackBee\ClassContent\Repository\ClassContentRepository
     * @param  string  $type
     * @param  string  $uid
     * @param  boolean $hydrateDraft      if true and BBUserToken is setted, will try to get and set draft to content
     * @param  boolean $checkoutOnMissing this parameter is used only if hydrateDraft is true
     * @return AClassContent
     */
    public function findOneByTypeAndUid($type, $uid, $hydrateDraft = false, $checkoutOnMissing = false)
    {
        $classname = AClassContent::getClassnameByContentType($type);
        $content = $this->em->getRepository($classname)->findOneBy(['_uid' => $uid]);

        if (true === $hydrateDraft && null !== $this->token) {
            $content->setDraft($this->getRevision($content, $checkoutOnMissing));
        }

        return $content;
    }

    /**
     * Update a single classcontent image url.
     *
     * @param array $classcontent the classcontent we want to update its image url
     * @return array
     */
    private function updateClassContentImageUrl(array $data)
    {
        if (!isset($data['image'])) {
            return $data;
        }

        $imageUri = '';
        $urlType = RouteCollection::RESOURCE_URL;
        if ('/' === $data['image'][0]) {
            $imageUri = $data['image'];
            $urlType = RouteCollection::IMAGE_URL;
        } else {
            $image_filepath = $this->getThumbnailBaseFolderPath().DIRECTORY_SEPARATOR.$data['image'];
            $baseFolder = $this->application->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            if (file_exists($image_filepath) && is_readable($image_filepath)) {
                $imageUri = $baseFolder.DIRECTORY_SEPARATOR.$data['image'];
            } else {
                $imageUri = $baseFolder.DIRECTORY_SEPARATOR.'default_thumbnail.png';
            }
        }

        $data['image'] = $this->application->getRouting()->getUri($imageUri, null, null, $urlType);

        return $data;
    }

    /**
     * Getter of class content thumbnail folder path
     *
     * @return string
     */
    private function getThumbnailBaseFolderPath()
    {
        if (null === $this->thumbnailBaseDir) {
            $baseFolder = $this->application->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            $this->thumbnailBaseDir = array_map(function ($directory) use ($baseFolder) {
                return str_replace(
                    DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $directory.DIRECTORY_SEPARATOR.$baseFolder
                );
            }, $this->application->getResourceDir());

            foreach ($this->thumbnailBaseDir as $directory) {
                if (is_dir($directory)) {
                    $this->thumbnailBaseDir = $directory;
                    break;
                }
            }
        }

        return $this->thumbnailBaseDir;
    }

    /**
     * Updates provided content's elements with data
     *
     * @param  AClassContent $content
     * @param  array         $elementsData
     * @return AClassContent
     */
    private function updateElements(AClassContent $content, array $elementsData)
    {
        if ($content instanceof ContentSet) {
            $content->clear();
            foreach ($elementsData as $data) {
                $element = $this->findOneByTypeAndUid($data['type'], $data['uid'], true, true);
                if ($content->isAccepted($element)) {
                    $content->push($element);
                }
            }
        } else {
            foreach ($elementsData as $key => $values) {
                if (is_scalar($values) && $content->isAccepted($values, $key)) {
                    $content->$key = $values;
                } elseif (is_array($values)) {
                    if (isset($values['type']) && isset($values['uid'])) {
                        $values = [$values];
                    }

                    $elements = [];
                    foreach ($values as $data) {
                        $element = $this->findOneByTypeAndUid($data['type'], $data['uid'], true, true);
                        if ($content->isAccepted($element, $key)) {
                            $elements[] = $element;
                        }
                    }

                    $content->$key = $elements;
                }
            }
        }

        return $content;
    }
}
