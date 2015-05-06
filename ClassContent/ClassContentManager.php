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

namespace BackBee\ClassContent;

use BackBee\ClassContent\Element\File as ElementFile;
use BackBee\ApplicationInterface;
use BackBee\Exception\InvalidArgumentException;
use BackBee\Routing\RouteCollection;
use BackBee\Security\Token\BBUserToken;
use BackBee\Utils\File\File;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\Util\ClassUtils;

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
     * @param ApplicationInterface $application
     */
    public function __construct(ApplicationInterface $application)
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
     * @param  AbstractClassContent $content
     * @param  array         $data    array of data that must contains parameters and/or elements key
     * @return AbstractClassContent
     * @throws \InvalidArgumentException if provided data doesn't have parameters and elements key
     */
    public function update(AbstractClassContent $content, array $data)
    {
        if (!isset($data['parameters']) && !isset($data['elements'])) {
            throw new \InvalidArgumentException('Provided data are not valids for ClassContentManager::update.');
        }

        if (isset($data['parameters'])) {
            $this->updateParameters($content, $data['parameters']);
        }

        if (isset($data['elements'])) {
            $this->updateElements($content, $data['elements']);
        }

        return $this;
    }

    /**
     * Calls ::jsonSerialize of the content and build valid url for image
     *
     * @param  AbstractClassContent $content
     * @param  integer       $format
     * @return array
     */
    public function jsonEncode(AbstractClassContent $content, $format = AbstractClassContent::JSON_DEFAULT_FORMAT)
    {
        if (AbstractClassContent::JSON_DEFINITION_FORMAT === $format) {
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
    public function jsonEncodeCollection($collection, $format = AbstractClassContent::JSON_DEFAULT_FORMAT)
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
        if (AbstractClassContent::JSON_DEFINITION_FORMAT === $format) {
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
                $classnames[] = AbstractClassContent::getClassnameByContentType($block->type);
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
                    AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE.str_replace([$directory, '.yml'], ['', ''], $path)
                );
            },
            File::getFilesRecursivelyByExtension($directory, 'yml')
        );

        $classnames[] = AbstractClassContent::CLASSCONTENT_BASE_NAMESPACE.'ContentSet';

        return $classnames;
    }

    /**
     * Returns current revision for the given $content
     *
     * @param AbstractClassContent $content content we want to get the latest revision
     * @return null|BackBee\ClassContent\Revision
     */
    public function getDraft(AbstractClassContent $content, $checkoutOnMissing = false)
    {
        return $this->em->getRepository('BackBee\ClassContent\Revision')->getDraft(
            $content,
            $this->token ?: $this->application->getBBUserToken(),
            $checkoutOnMissing
        );
    }

    /**
     * Computes provided data to define what to commit from given content.
     *
     * @param  AbstractClassContent $content
     * @param  array         $data
     * @return self
     */
    public function commit(AbstractClassContent $content, array $data)
    {
        if (!isset($data['parameters']) && !isset($data['elements'])) {
            throw new \InvalidArgumentException('Provided data are not valids for ClassContentManager::commit.');
        }

        if (null === $draft = $this->getDraft($content)) {
            throw new InvalidArgumentException(sprintf(
                '%s with identifier "%s" has not draft, nothing to commit.',
                $content->getContentType(),
                $content->getUid()
            ));
        }

        $cleanDraft = clone $draft;
        $this->prepareDraftForCommit($content, $draft, $data);
        $this->executeCommit($content, $draft);
        $this->commitPostProcess($content, $cleanDraft);

        return $this;
    }

    /**
     * Computes provided data to define what to revert from given content.
     *
     * @param  AbstractClassContent $content
     * @param  array         $data
     * @return self
     */
    public function revert(AbstractClassContent $content, array $data)
    {
        if (!isset($data['parameters']) && !isset($data['elements'])) {
            throw new \InvalidArgumentException('Provided data are not valids for ClassContentManager::revert.');
        }

        if (null === $draft = $this->getDraft($content)) {
            throw new InvalidArgumentException(sprintf(
                '%s with identifier "%s" has not draft, nothing to revert.',
                $content->getContentType(),
                $content->getUid()
            ));
        }

        $this->executeRevert($content, $draft, $data);
        $this->revertPostProcess($content, $draft);

        return $this;
    }

    /**
     * Alias to ClassContentRepository::findOneByTypeAndUid. In addition, it can also manage content's revision.
     *
     * @see BackBee\ClassContent\Repository\ClassContentRepository
     * @param  string  $type
     * @param  string  $uid
     * @param  boolean $hydrateDraft      if true and BBUserToken is setted, will try to get and set draft to content
     * @param  boolean $checkoutOnMissing this parameter is used only if hydrateDraft is true
     * @return AbstractClassContent
     */
    public function findOneByTypeAndUid($type, $uid, $hydrateDraft = false, $checkoutOnMissing = false)
    {
        $classname = AbstractClassContent::getClassnameByContentType($type);
        $content = $this->em->getRepository($classname)->findOneBy(['_uid' => $uid]);

        if (true === $hydrateDraft && null !== $this->token) {
            $content->setDraft($this->getDraft($content, $checkoutOnMissing));
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
            $baseFolder = $this->application->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            foreach ($this->getThumbnailBaseFolderPath() as $baseDir) {
                $imageFilepath = $baseDir.DIRECTORY_SEPARATOR.$data['image'];
                if (file_exists($imageFilepath) && is_readable($imageFilepath)) {
                    $imageUri = $baseFolder.DIRECTORY_SEPARATOR.$data['image'];

                    break;
                } else {
                    $imageUri = $baseFolder.DIRECTORY_SEPARATOR.'default_thumbnail.png';
                }
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
            $this->thumbnailBaseDir = [];
            $baseFolder = $this->application->getContainer()->getParameter('classcontent_thumbnail.base_folder');
            $thumbnailBaseDir = array_map(function ($directory) use ($baseFolder) {
                return str_replace(
                    DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $directory.DIRECTORY_SEPARATOR.$baseFolder
                );
            }, $this->application->getResourceDir());

            foreach (array_unique($thumbnailBaseDir) as $directory) {
                if (is_dir($directory)) {
                    $this->thumbnailBaseDir[] = $directory;
                }
            }
        }

        return $this->thumbnailBaseDir;
    }

    /**
     * Updates provided content's elements with data
     *
     * @param  AbstractClassContent $content
     * @param  array         $elementsData
     * @return self
     */
    private function updateElements(AbstractClassContent $content, array $elementsData)
    {
        if ($content instanceof ContentSet) {
            $content->clear();
            foreach ($elementsData as $data) {
                $element = $this->findOneByTypeAndUid($data['type'], $data['uid']);
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
                        $element = $this->findOneByTypeAndUid($data['type'], $data['uid']);
                        if ($content->isAccepted($element, $key)) {
                            $elements[] = $element;
                        }
                    }

                    $content->$key = $elements;
                }
            }
        }

        return $this;
    }

    /**
     * Updates provided content's parameters.
     *
     * @param  AbstractClassContent $content
     * @param  array         $paramsData
     * @return self
     */
    private function updateParameters(AbstractClassContent $content, array $paramsData)
    {
        foreach ($paramsData as $key => $param) {
            $content->setParam($key, $param);
        }

        return $this;
    }

    /**
     * Prepares draft for commit.
     *
     * @param  AbstractClassContent $content
     * @param  Revision      $draft
     * @param  array         $data
     * @return self
     */
    private function prepareDraftForCommit(AbstractClassContent $content, Revision $draft, array $data)
    {
        if ($content instanceof ContentSet) {
            if (!isset($data['elements']) || false === $data['elements']) {
                $draft->clear();
                foreach ($content->getData() as $element) {
                    $draft->push($element);
                }
            }
        } else {
            foreach ($content->getData() as $key => $element) {
                if (!isset($data['elements']) || !in_array($key, $data['elements'])) {
                    $draft->$key = $content->$key;
                }
            }
        }

        if (isset($data['parameters'])) {
            foreach ($content->getAllParams() as $key => $params) {
                if (!in_array($key, $data['parameters'])) {
                    $draft->setParam($key, $content->getParamValue($key));
                }
            }
        }

        if (isset($data['message'])) {
            $draft->setComment($data['message']);
        }

        return $this;
    }

    /**
     * Executes commit action on content and its draft.
     *
     * @param  AbstractClassContent $content
     * @param  Revision      $draft
     * @return self
     */
    private function executeCommit(AbstractClassContent $content, Revision $draft)
    {
        $content->setDraft(null);

        if ($content instanceof ContentSet) {
            $content->clear();
            while ($subcontent = $draft->next()) {
                if ($subcontent instanceof AbstractClassContent) {
                    $subcontent = $this->em->getRepository(ClassUtils::getRealClass($subcontent))->load($subcontent);
                    if (null !== $subcontent) {
                        $content->push($subcontent);
                    }
                }
            }
        } else {
            foreach ($draft->getData() as $key => $values) {
                $values = is_array($values) ? $values : [$values];
                foreach ($values as &$subcontent) {
                    if ($subcontent instanceof AbstractClassContent) {
                        $subcontent = $this->em->getRepository(ClassUtils::getRealClass($subcontent))
                            ->load($subcontent)
                        ;
                    }
                }

                $content->$key = $values;
            }
        }

        $draft->commit();
        $content->setLabel($draft->getLabel());
        foreach ($draft->getAllParams() as $key => $params) {
            $content->setParam($key, $params['value']);
        }

        $content->setRevision($draft->getRevision())
            ->setState(AbstractClassContent::STATE_NORMAL)
            ->addRevision($draft)
        ;

        return $this;
    }

    /**
     * Runs process of post commit.
     *
     * @param  AbstractClassContent $content
     * @param  Revision      $draft
     * @return self
     */
    private function commitPostProcess(AbstractClassContent $content, Revision $draft)
    {
        $data = $draft->jsonSerialize();
        if (0 !== count($data['parameters']) && 0 !== count($data['elements'])) {
            $draft->setRevision($content->getRevision());
            $draft->setState(Revision::STATE_MODIFIED);
            $this->em->persist($draft);
        }
    }

    /**
     * Executes revert action on content and its draft.
     *
     * @param  AbstractClassContent $content
     * @param  Revision      $draft
     * @param  array         $data
     * @return self
     */
    private function executeRevert(AbstractClassContent $content, Revision $draft, array $data)
    {
        if ($content instanceof ContentSet) {
            if (isset($data['elements']) && true === $data['elements']) {
                $draft->clear();
                foreach ($content->getData() as $element) {
                    $draft->push($element);
                }
            }
        } else {
            foreach ($content->getData() as $key => $element) {
                if (isset($data['elements']) && in_array($key, $data['elements'])) {
                    $draft->$key = $content->$key;
                }
            }
        }

        if (isset($data['parameters'])) {
            foreach ($content->getDefaultParams() as $key => $params) {
                if (in_array($key, $data['parameters'])) {
                    $draft->setParam($key, $content->getParamValue($key));
                }
            }
        }

        return $this;
    }

    /**
     * Runs revert post action on content and its draft.
     *
     * @param  AbstractClassContent $content
     * @param  Revision      $draft
     * @return self
     */
    private function revertPostProcess(AbstractClassContent $content, Revision $draft)
    {
        $data = $draft->jsonSerialize();
        if (0 === count($data['parameters']) && 0 === count($data['elements'])) {
            $this->em->remove($draft);

            if (AbstractClassContent::STATE_NEW === $content->getState()) {
                $this->em->remove($content);
            }
        }
    }
}
