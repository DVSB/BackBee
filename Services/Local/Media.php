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

namespace BackBuilder\Services\Local;

use BackBuilder\ClassContent\AClassContent;
use BackBuilder\Services\Exception\ServicesException,
    BackBuilder\ClassContent\Element\file as elementFile,
    BackBuilder\ClassContent\Element\text as elementText,
    BackBuilder\ClassContent\Element\image as elementImage,
    BackBuilder\ClassContent\Exception\ClassContentException;
use BackBuilder\Util\File;
use BackBuilder\Services\Local\AbstractServiceLocal;

/**
 * Description of Media
 *
 * @category    BackBuilder
 * @package     BackBuilder\Services
 * @subpackage  Local
 * @copyright   Lp digital system
 * @author      m.baptista <michel.baptista@lp-digital.fr>
 */
class Media extends AbstractServiceLocal
{

    private $_availableMedias;

    /**
     * @exposed(secured=true)
     */
    public function uploadImage(\Symfony\Component\HttpFoundation\Request $request)
    {
        $uploaded_file = new \stdClass();
        $uploaded_file->originalname = $request->files->get('image')->getClientOriginalName();
        $uploaded_file->extension = pathinfo($uploaded_file->originalname, PATHINFO_EXTENSION);
        $uploaded_file->filename = basename($request->files->get('image')->getRealPath()) . '.' . $uploaded_file->extension;

        if (FALSE === is_dir($this->bbapp->getTemporaryDir()))
            mkdir($this->bbapp->getTemporaryDir(), 0755, TRUE);

        move_uploaded_file($request->files->get('image')->getRealPath(), $this->bbapp->getTemporaryDir() . DIRECTORY_SEPARATOR . $uploaded_file->filename);

        return $uploaded_file;
    }

    /**
     * @exposed(secured=true)
     */
    public function uploadMedia(\Symfony\Component\HttpFoundation\Request $request)
    {
        //ini_set("upload_max_filesize","15M");
        //ini_set("post_max_size","15M");
        $uploaded_file = new \stdClass();
        $uploaded_file->originalname = $request->files->get('uploadedmedia')->getClientOriginalName();
        $uploaded_file->extension = pathinfo($uploaded_file->originalname, PATHINFO_EXTENSION);
        $uploaded_file->filename = basename($request->files->get('uploadedmedia')->getRealPath()) . '.' . $uploaded_file->extension;
        if (FALSE === is_dir($this->bbapp->getTemporaryDir()))
            mkdir($this->bbapp->getTemporaryDir(), 0755, TRUE);
        $isFileIsMoved = move_uploaded_file($request->files->get('uploadedmedia')->getRealPath(), $this->bbapp->getTemporaryDir() . DIRECTORY_SEPARATOR . $uploaded_file->filename);
        if(!$isFileIsMoved){
              return "stran";
        }
      
        return $uploaded_file;
    }

    /**
     * @exposed(secured=true)
     */
    public function getBBSelectorForm($mediafolder_uid, $media_classname, $media_id)
    {
        $em = $this->bbapp->getEntityManager();
        $renderer = $this->bbapp->getRenderer();

        $media_content = new $media_classname;

        if (NULL !== $media_id) {
            $media = $em->find('\BackBuilder\NestedNode\Media', $media_id);

            if ($media)
                $media_content = $media->getContent();
        }
        return $renderer->render($media_content, 'bbselector_edit');
    }

    /**
     * @exposed(secured=true)
     * /TODO play through repository, validations, check file
     */
    public function postBBSelectorForm($mediafolder_uid, $media_classname, $media_id, $content_values)
    {
        if (NULL === $mediafolder_uid) {
            throw new ServicesException('No media folder uid provided');
        }

        if (NULL === $media_classname) {
            throw new ServicesException('No media classname provided');
        }

        if (false === class_exists($media_classname)) {
            throw new ServicesException(sprintf('Unknown media classname provided `%s`', $media_classname));
        }

        $em = $this->bbapp->getEntityManager();
        $renderer = $this->bbapp->getRenderer();

        $content_values = json_decode($content_values);
        $content_values_array = array();
        foreach ($content_values as $content_value) {
            $content_values_array[$content_value->name] = $content_value->value;
        }

        if (NULL === $mediafolder = $em->find('\BackBuilder\NestedNode\MediaFolder', $mediafolder_uid))
            throw new ServicesException('None folder provided');

        $media_content = new $media_classname();
        if (NULL === $media_id || NULL === $media = $em->find('\BackBuilder\NestedNode\Media', $media_id)) {
            $media = new \BackBuilder\NestedNode\Media();
            $media->setContent($media_content);

            $em->persist($media);
            $em->persist($media_content);
        }

        $media_content = $media->getContent();
        $media_content = $em->find($media_classname, $media_content->getUid());
        if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($media_content, $this->bbapp->getBBUserToken(), true))
            $media_content->setDraft($draft);

        foreach ($content_values_array as $element => $value) {
            try {
                $subcontent = $media_content->$element;
                if (!($subcontent instanceof AClassContent))
                    continue;

                if (NULL === $subcontent = $em->find(get_class($subcontent), $subcontent->getUid())) {
                    $subcontent = $media_content->$element;
                    $em->persist($subcontent);
                }

                if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($subcontent, $this->bbapp->getBBUserToken(), true))
                    $subcontent->setDraft($draft);
                if ($subcontent instanceof elementText) {
                    $subcontent->value = $value;
                    $media_content->$element = $subcontent;
                    $this->bbapp->getEventDispatcher()->triggerEvent('commit', $subcontent);
                } else if ($subcontent instanceof elementFile) {
                    $content_image_obj = json_decode($value);
                    if (isset($content_image_obj->filename)) {

                        $subcontent->originalname = $content_image_obj->originalname;
                        $subcontent->path = \BackBuilder\Util\Media::getPathFromContent($subcontent);

                        $filename = $this->bbapp->getTemporaryDir() . DIRECTORY_SEPARATOR . $content_image_obj->filename;
                        $moveto = $subcontent->path;
                        File::resolveFilepath($moveto, NULL, array('base_dir' => $this->bbapp->getMediaDir()));
                        if (FALSE === is_dir(dirname($moveto)))
                            mkdir(dirname($moveto), 0755, TRUE);

                        copy($filename, $moveto);
                        unlink($filename);

                        $stat = stat($moveto);
                        $subcontent->setParam('stat', $stat, 'array');

                        if ($subcontent instanceof elementImage) {
                            $size = getimagesize($moveto);
                            list($width, $height, $type, $attr) = $size;
                            $subcontent->setParam('width', $width, 'scalar');
                            $subcontent->setParam('height', $height, 'scalar');
                        }
                    }

                    $media_content->$element = $subcontent;
                    $this->bbapp->getEventDispatcher()->triggerEvent('commit', $subcontent);
                }
            } catch (ClassContentException $e) {
                // Nothing to do
            }
        }

        $this->bbapp->getEventDispatcher()->triggerEvent('commit', $media_content);

        //media
        $media->setTitle($content_values_array['title']);
        $media->setMediaFolder($mediafolder);
        $media->setContent($media_content);

        //          $em->persist($media);
        $em->flush();

        /*
          //media content
          switch ($media_classname) {
          case 'BackBuilder\\ClassContent\\Media\\image':
          $title = $media_content->title;
          if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($title, $this->bbapp->getBBUserToken(), true))
          $title->setDraft($draft);
          $title->value = $content_values_array['title'];
          $media_content->title = $title;

          $description = $media_content->description;
          if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($description, $this->bbapp->getBBUserToken(), true))
          $description->setDraft($draft);
          $description->value = $content_values_array['description'];
          $media_content->description = $description;


          $copyrights = $media_content->copyrights;
          if (NULL !== $draft = $em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($copyrights, $this->bbapp->getBBUserToken(), true))
          $copyrights->setDraft($draft);
          $copyrights->value = $content_values_array['copyrights'];
          $media_content->copyrights = $copyrights;

          if ((isset($content_values_array['image'])) && (NULL !== $content_values_array['image']) && (strlen($content_values_array['image']) > 0)) {
          $content_image_obj = json_decode($content_values_array['image']);

          $filename = $this->bbapp->getTemporaryDir().DIRECTORY_SEPARATOR.$content_image_obj->filename;
          $basename = basename($filename);
          $extension = pathinfo($filename, PATHINFO_EXTENSION);
          $stat = stat($filename);
          $sha1 = sha1_file($filename);
          $size = getimagesize($filename);
          list($width, $height, $type, $attr) = $size;

          $moveto = $media_content->image->getUid().'.'.$extension;
          File::resolveMediapath($moveto, NULL, array('base_dir' => $this->bbapp->getMediaDir()));

          if (FALSE === is_dir(dirname($moveto)))
          mkdir(dirname($moveto), 0700, TRUE);

          copy($filename, $moveto);
          unlink($filename);

          $media_content->image->path = $media_content->image->getUid().'.'.$extension;
          $media_content->image->originalname = $content_image_obj->originalname;
          $media_content->image->setParam('width', $width, 'scalar');
          $media_content->image->setParam('height', $height, 'scalar');
          $media_content->image->setParam('stat', $stat, 'array');
          }

          //validation
          if (strlen($media_content->image->path) == 0)
          throw new \Exception('No file provided');

          break;

          case 'BackBuilder\\ClassContent\\Media\\video':
          $title = $media_content->title;
          if (NULL !== $draft = $title->getDraft()) {
          $title->releaseDraft();
          $em->detach($draft);
          }
          $title->value = $content_values_array['title'];
          $media_content->title = $title;

          $description = $media_content->description;
          if (NULL !== $draft = $description->getDraft()) {
          $description->releaseDraft();
          $em->detach($draft);
          }
          $description->value = $content_values_array['description'];
          $media_content->description = $description;

          $embed = $media_content->embed;
          if (NULL !== $draft = $embed->getDraft()) {
          $description->releaseDraft();
          $em->detach($draft);
          }
          $embed->value = $content_values_array['embed'];
          $media_content->embed = $embed;

          break;
          }
         */

        $return = new \stdClass();
        $return->id = $media->getId();
        $return->mediafolder_uid = $media->getMediaFolder()->getUid();
        $return->title = $media->getTitle();
        $return->content = new \stdClass();
        $return->content->uid = $media_content->getUid();
        $return->content->classname = get_class($media_content);
        $return->created = $media->getCreated()->format('c');
        $return->modified = $media->getModified()->format('c');
        return $return;
    }

    /**
     * @exposed(secured=true)
     */
    public function delete($id)
    {
        // Ensure all media types are known
        foreach ($this->getBBSelectorAvailableMedias() as $media_type) {
            class_exists($media_type->classname);
        }

        if (null === $media = $this->em->find('\BackBuilder\NestedNode\Media', $id)) {
            throw new ServicesException(sprintf('Unable to delete media for `%s` id', $id));
        }

        $content = $media->getContent();
        if ($content instanceof AClassContent) {
            $this->em->remove($content);
        }

        $this->em->remove($media);
        $this->em->flush();

        return true;
    }

    /**
     * @exposed(secured=true)
     */
    public function postBBMediaUpload($media_uid, $media_classname, $content_values)
    {
        if (NULL === $media_uid) {
            throw new ServicesException('No media uid provided');
        }

        if (NULL === $media_classname) {
            throw new ServicesException('No media classname provided');
        }

        $media_classname = 'BackBuilder\\ClassContent\\' . $media_classname;
        if (false === class_exists($media_classname)) {
            throw new ServicesException(sprintf('Unknown media classname provided `%s`', $media_classname));
        }

        $content_obj = json_decode($content_values);
        if (false === property_exists($content_obj, 'originalname')) {
            throw new ServicesException('No original filename provided');
        }

        if (false === property_exists($content_obj, 'filename')) {
            throw new ServicesException('No temporary filename available');
        }

        if (NULL === $content = $this->em->find($media_classname, $media_uid)) {
            $content = new $media_classname();
            $this->em->persist($content);
        }

        if (false === ($content instanceof elementFile)) {
            if (false === in_array(get_class($content), $this->_getAvailableMedias())) {
                throw new ServicesException('Provided content is neither an element file nor an media');
            }

            $media = $this->em->getRepository('BackBuilder\NestedNode\Media')->findBy(array('_content' => $content));
            if (0 < count($media)) {
                // Library media, create a new one
                $content = new $media_classname();
                $this->em->persist($content);
            }

            if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $this->bbapp->getBBUserToken(), true)) {
                $content->setDraft($draft);
            }

            $elementContent = null;
            foreach ($content->getData() as $key => $value) {
                if ($value instanceof elementFile) {
                    $elementContent = $value;
                    break;
                }
            }

            $this->postBBMediaUpload($elementContent->getUid(), str_replace('BackBuilder\\ClassContent\\', '', get_class($elementContent)), $content_values);
        } else {
            if (NULL !== $draft = $this->em->getRepository('BackBuilder\ClassContent\Revision')->getDraft($content, $this->bbapp->getBBUserToken(), true)) {
                $content->setDraft($draft);
            }

            $repository = $this->em->getRepository(get_class($content));
            if ('BackBuilder\ClassContent\Repository\ClassContentRepository' === get_class($repository)) {
                $repository = $this->em->getRepository('BackBuilder\ClassContent\Element\file');
            }

            if (false === $newfilename = $repository->setDirectories($this->bbapp)
                    ->updateFile($content, $content_obj->filename, $content_obj->originalname)) {
                throw new ServicesException('Unable to change the file');
            }

            $this->em->flush();
        }

        $return = new \stdClass();
        $return->uid = $content->getUid();
        $return->classname = get_class($content);

        return $return;
    }

    /**
     * @exposed(secured=true)
     * /TODO play through repository
     */
    public function getBBSelectorAvailableMedias()
    {
        $availableMedias = array();
        $classnames = $this->bbapp->getAutoloader()->glob('Media' . DIRECTORY_SEPARATOR . '*');
        if ($classnames !== false) {
            foreach ($classnames as $classname) {
                $content = new $classname();
                $availableMedia = new \stdClass();
                $availableMedia->label = $content->getProperty('name');
                $availableMedia->classname = $classname;
                $availableMedias[] = $availableMedia;
            }
        } else {
            return;
        }

        return $availableMedias;
    }

    private function _getAvailableMedias()
    {
        if (null === $this->_availableMedias) {
            $this->_availableMedias = array();
            foreach ($this->getBBSelectorAvailableMedias() as $media) {
                $this->_availableMedias[] = $media->classname;
            }
        }

        return $this->_availableMedias;
    }

}
