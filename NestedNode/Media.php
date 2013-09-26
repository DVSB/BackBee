<?php
namespace BackBuilder\NestedNode;

/**
 * PageRevison object in BackBuilder 4
 *
 * A page revision is...
 *
 * @category    BackBuilder
 * @package     BackBuilder\NestedNode
 * @copyright   Lp system
 * @author      m.baptista
 * @Entity(repositoryClass="BackBuilder\NestedNode\Repository\MediaRepository")
 * @Table(name="media")
 * @HasLifecycleCallbacks
 */
class Media {
    /**
     * Unique identifier of the revision
     * @var integer
     * @Id @Column(type="integer", name="id")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $_id;

    /**
     * @ManyToOne(targetEntity="BackBuilder\NestedNode\MediaFolder", inversedBy="_medias", cascade={"persist"})
     * @JoinColumn(name="media_folder_uid", referencedColumnName="uid")
     */
    protected $_media_folder;
    
    /**
     * @ManyToOne(targetEntity="BackBuilder\ClassContent\AClassContent", cascade={"persist"})
     * @JoinColumn(name="content_uid", referencedColumnName="uid")
     */
    protected $_content;
    
    /**
     * The title of this media
     * @var string
     * @Column(type="string", name="title")
     */
    protected $_title;
    
    /**
     * The publication datetime
     * @var DateTime
     * @Column(type="datetime", name="date")
     */
    protected $_date;
    
    /**
     * The creation datetime
     * @var DateTime
     * @Column(type="datetime", name="created")
     */
    protected $_created;

    /**
     * The last modification datetime
     * @var DateTime
     * @Column(type="datetime", name="modified")
     */
    protected $_modified;

    /**
     * Class constructor
     */
    public function __construct($title = NULL, $date = NULL) {
        $this->_title = (is_null($title)) ? 'Untitled media' : $title;
        $this->_date    = (is_null($date)) ? new \DateTime() : $date;
        
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $content
     * @return type
     */
    public static function getAbsolutePath($content = NULL)
    {
        return __DIR__.'/../../repository/' . Media::getUploadDir();
    }

    /**
     * @codeCoverageIgnore
     * @param type $content
     * @return string
     */
    public static function getWebPath($content = NULL)
    {
        return '/images/';
    }
    
    /**
     * @codeCoverageIgnore
     * @return string path
     */
    public static function getUploadTmpDir()
    {
        return __DIR__.'/../../repository/Data/Tmp/';
    }
    
    /**
     * @codeCoverageIgnore
     * @return string
     */
    protected static function getUploadDir()
    {
        return 'Data/Media/';
    }
    
    /**
     * @codeCoverageIgnore
     * @param \BackBuilder\NestedNode\MediaFolder $media_folder
     * @return \BackBuilder\NestedNode\Media
     */
    public function setMediaFolder(\BackBuilder\NestedNode\MediaFolder $media_folder) {
        $this->_media_folder = $media_folder;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $content
     * @return \BackBuilder\NestedNode\Media
     */
    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $title
     * @return \BackBuilder\NestedNode\Media
     */
    public function setTitle($title) {
        $this->_title = $title;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $date
     * @return \BackBuilder\NestedNode\Media
     */
    public function setDate($date) {
        $this->_date = $date;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $created
     * @return \BackBuilder\NestedNode\Media
     */
    public function setCreated($created) {
        $this->_created = $created;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @param type $modified
     * @return \BackBuilder\NestedNode\Media
     */
    public function setModified($modified) {
        $this->_modified = $modified;
        return $this;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getId() {
        return $this->_id;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getTitle() {
        return $this->_title;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getDate() {
        return $this->_date;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getCreated() {
        return $this->_created;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getModified() {
        return $this->_modified;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getMediaFolder() {
        return $this->_media_folder;
    }
    
    /**
     * @codeCoverageIgnore
     * @return type
     */
    public function getContent() {
        return $this->_content;
    }
}