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

namespace BackBuilder\ClassContent\Repository\Element;

use BackBuilder\ClassContent\Repository\ClassContentRepository,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\file as elementFile,
    BackBuilder\Util\File,
    BackBuilder\Util\Media,
    BackBuilder\BBApplication;

/**
 * file repository
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent
 * @subpackage  Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class fileRepository extends ClassContentRepository
{

    /**
     * The temporary directory
     * @var string
     */
    protected $_temporarydir;

    /**
     * The sotrage directory
     * @var string
     */
    protected $_storagedir;

    /**
     * The media library directory
     * @var string
     */
    protected $_mediadir;

    /**
     * Move an temporary uploaded file to either media library or storage directory
     * @param \BackBuilder\ClassContent\Element\file $file
     * @return boolean
     */
    public function commitFile(elementFile $file)
    {
        $filename = $file->path;
        File::resolveFilepath($filename, null, array('base_dir' => $this->_temporarydir));

        $currentname = Media::getPathFromContent($file);
        File::resolveFilepath($currentname, null, array('base_dir' => ($this->isInMediaLibrary($file)) ? $this->_mediadir : $this->_storagedir));

        try {
            File::move($filename, $currentname);
        } catch (\BackBuilder\Exception\BBException $e) {
            return false;
        }

        $file->path = Media::getPathFromContent($file);

        return true;
    }

    /**
     * Move an uploaded file to the temporary directory and update file content
     * @param \BackBuilder\ClassContent\AClassContent $file
     * @param string $newfilename
     * @param string $originalname
     * @return boolean|string
     * @throws \BackBuilder\ClassContent\Exception\ClassContentException Occures on invalid content type provided
     */
    public function updateFile(AClassContent $file, $newfilename, $originalname = null)
    {
        if (false === ($file instanceof elementFile)) {
            throw new ClassContentException('Invalid content type');
        }

        if (null === $originalname) {
            $originalname = $file->originalname;
        }

        $base_dir = $this->_temporarydir;
        $file->originalname = $originalname;
        $file->path = \BackBuilder\Util\Media::getPathFromContent($file);

        if (null === $file->getDraft()) {
            $base_dir = ($this->isInMediaLibrary($file)) ? $this->_mediadir : $this->_storagedir;
        }

        $moveto = $file->path;
        File::resolveFilepath($moveto, null, array('base_dir' => $base_dir));
        File::resolveFilepath($newfilename, null, array('base_dir' => $this->_temporarydir));

        try {
            File::move($newfilename, $moveto);
        } catch (\BackBuilder\Exception\BBException $e) {
            return false;
        }

        $stat = stat($moveto);
        $file->setParam('stat', $stat, 'array');

        return $moveto;
    }

    /**
     * Return true if file is in media libray false otherwise
     * @param \BackBuilder\ClassContent\Element\file $file
     * @return boolean
     */
    public function isInMediaLibrary(elementFile $file)
    {
        $parent_ids = $this->getParentContentUid($file);
        if (0 === count($parent_ids))
            return false;

        $q = $this->_em->getConnection()
                ->createQueryBuilder()
                ->select('m.id')
                ->from('media', 'm')
                ->andWhere('m.content_uid IN ("' . implode('","', $parent_ids) . '")');

        $medias = $q->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return (0 < count($medias)) ? $medias[0] : false;
    }

    /**
     * Do stuf on update by post of the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return \BackBuilder\ClassContent\Element\file
     * @throws \BackBuilder\ClassContent\Exception\ClassContentException Occures on invalid content type provided
     */
    public function getValueFromPost(AClassContent $content, $value, AClassContent $parent = null)
    {
        if (false === ($content instanceof elementFile)) {
            throw new ClassContentException('Invalid content type');
        }

        if (true === property_exists($value, 'value')) {
            $image_obj = json_decode($value->value);
            if (true === is_object($image_obj)
                    && true === property_exists($image_obj, 'filename')
                    && true === property_exists($image_obj, 'originalname')) {
                $this->updateFile($content, $image_obj->filename, $image_obj->originalname);
            }
        }

        return $content;
    }

    /**
     * Set the storage directories define by the BB5 application
     * @param \BackBuilder\BBApplication $application
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setDirectories(BBApplication $application = null)
    {
        if (null !== $application) {
            $this->setTemporaryDir($application->getTemporaryDir())
                    ->setStorageDir($application->getStorageDir())
                    ->setMediaDir($application->getMediaDir());
        }

        return $this;
    }

    /**
     * Set the temporary directory
     * @param type $temporary_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setTemporaryDir($temporary_dir = null)
    {
        $this->_temporarydir = $temporary_dir;
        return $this;
    }

    /**
     * Set the storage directory
     * @param type $storage_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setStorageDir($storage_dir = null)
    {
        $this->_storagedir = $storage_dir;
        return $this;
    }

    /**
     * Set the media library directory
     * @param type $media_dir
     * @return \BackBuilder\ClassContent\Repository\Element\fileRepository
     */
    public function setMediaDir($media_dir = null)
    {
        $this->_mediadir = $media_dir;
        return $this;
    }

}
