<?php
namespace BackBuilder\ClassContent\Repository\Element;

use BackBuilder\ClassContent\Repository\ClassContentRepository,
    BackBuilder\ClassContent\Exception\ClassContentException,
    BackBuilder\ClassContent\AClassContent,
    BackBuilder\ClassContent\Element\file as elementFile,
    BackBuilder\Util\File,
    BackBuilder\BBApplication;

/**
 * file repository
 * @category    BackBuilder
 * @package     BackBuilder\ClassContent\Repository\Element
 * @copyright   Lp digital system
 * @author      c.rouillon
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
     * Move an uploaded file to either media library or storage directory
     * @param \BackBuilder\ClassContent\Element\file $file
     * @return boolean
     */
    public function commitFile(elementFile $file)
    {
        //if (null === $file->getDraft()) return false;

        $filename = $file->path;
        File::resolveFilepath($filename, NULL, array('base_dir' => $this->_temporarydir));

        if (file_exists($filename) && false === is_dir($filename)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $currentname = \BackBuilder\Util\Media::getPathFromContent($file);
            File::resolveFilepath($currentname, NULL, array('base_dir' => ($this->isInMediaLibrary($file)) ? $this->_mediadir : $this->_storagedir));

            if (FALSE === is_dir(dirname($currentname)))
                mkdir(dirname($currentname), 0755, TRUE);

            copy($filename, $currentname);
            unlink($filename);

            $file->path = \BackBuilder\Util\Media::getPathFromContent($file);
        }

        return true;
    }

    /**
     * Move an uploaded file to the temporary directory and update file content
     * @param \BackBuilder\ClassContent\AClassContent $file
     * @param string $newfilename
     * @param string $originalname
     * @return boolean|string
     * @throws ClassContentException Occures on invalid content type provided
     */
    public function updateFile(AClassContent $file, $newfilename, $originalname = null)
    {
        if (false === ($file instanceof elementFile)) {
            throw new ClassContentException('Invalid content type');
        }

        if (NULL === $originalname) $originalname = $file->originalname;

        File::resolveFilepath($newfilename, null, array('base_dir' => $this->_temporarydir));
        if (false === file_exists($newfilename)) return false;

        $extension = pathinfo($newfilename, PATHINFO_EXTENSION);
        $stat = stat($newfilename);

        $base_dir = $this->_temporarydir;
        $file->path = \BackBuilder\Util\Media::getPathFromContent($file);
        
        if (null === $file->getDraft()) {
            $base_dir = ($this->isInMediaLibrary($file)) ? $this->_mediadir : $this->_storagedir;
        }

        $moveto = $file->path;
        File::resolveFilepath($moveto, null, array('base_dir' => $base_dir));

        if (FALSE === is_dir(dirname($moveto)))
            mkdir(dirname($moveto), 0755, TRUE);

        copy($newfilename, $moveto);
        unlink($newfilename);

        $file->originalname = $originalname;
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
        if (0 === count($parent_ids)) return false;

        $q = $this->_em->getConnection()
                       ->createQueryBuilder()
                       ->select('m.id')
                       ->from('media', 'm')
                       ->andWhere('m.content_uid IN ("'.implode('","', $parent_ids).'")');

        $medias = $q->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return (0 < count($medias)) ? $medias[0] : false;
    }

    /**
     * Do stuf on update by post of the content editing form
     * @param \BackBuilder\ClassContent\AClassContent $content
     * @param stdClass $value
     * @param \BackBuilder\ClassContent\AClassContent $parent
     * @return \BackBuilder\ClassContent\Element\file
     * @throws ClassContentException Occures on invalid content type provided
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
