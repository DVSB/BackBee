<?php

namespace BackBuilder\Site;

use BackBuilder\Site\Metadata\Metadata,
    BackBuilder\Security\Acl\Domain\AObjectIdentifiable,
    BackBuilder\Services\Local\IJson;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * A BackBuilder website entity
 * 
 * A website should be associated to:
 * 
 * * a collection of available layouts
 * * a collection of default metadata sets
 * 
 * @category    BackBuilder
 * @package     BackBuilder\Site
 * @copyright   Lp digital system
 * @author      c.rouillon <rouillon.charles@gmail.com>
 * @Entity(repositoryClass="BackBuilder\Site\Repository\SiteRepository")
 * @Table(name="site", indexes={@Index(name="url", columns={"server_name"})})
 * @fixtures(qty=1)
 */
class Site extends AObjectIdentifiable implements IJson
{

    /**
     * The unique identifier of this website.
     * @var string
     * @Id @Column(type="string", name="uid")
     * @fixture(type="md5")
     */
    protected $_uid;

    /**
     * The label of this website.
     * @var string
     * @Column(type="string", name="label", nullable=false)
     * @fixture(type="domainWord")
     */
    protected $_label;

    /**
     * The creation datetime.
     * @var \DateTime
     * @Column(type="datetime", name="created", nullable=false)
     * @fixture(type="dateTime")
     */
    protected $_created;

    /**
     * The last modification datetime.
     * @var \DateTime
     * @Column(type="datetime", name="modified", nullable=false)
     * @fixture(type="dateTime")
     */
    protected $_modified;

    /**
     * The optional server name.
     * @var string
     * @Column(type="string", name="server_name", nullable=true)
     * @fixture(type="domainWord")
     */
    protected $_server_name;

    /**
     * The default extension used by the site.
     * @var string
     */
    protected $_default_ext = '.html';

    /**
     * The collection of layouts available for this site.
     * @OneToMany(targetEntity="BackBuilder\Site\Layout", mappedBy="_site")
     * @OrderBy({"_label" = "ASC"})
     */
    protected $_layouts;

    /**
     * The default metadatas associated tto the pages of this website.
     * @ManyToMany(targetEntity="BackBuilder\Site\Metadata\Metadata", cascade={"all"})
     * @JoinTable(name="metadata_site",
     *      joinColumns={@JoinColumn(name="site_uid", referencedColumnName="uid")},
     *      inverseJoinColumns={@JoinColumn(name="metadata_uid", referencedColumnName="uid")}
     *      )
     */
    protected $_metadata;

    /**
     * Class constructor.
     * @param string $uid The unique identifier of the site.
     * @param array $options Initial options for the content:
     *                         - label      the default label
     */
    public function __construct($uid = NULL, $options = NULL)
    {
        $this->_uid = (is_null($uid)) ? md5(uniqid('', TRUE)) : $uid;
        $this->_created = new \DateTime();
        $this->_modified = new \DateTime();

        $this->_layouts = new ArrayCollection();
        $this->_metadata = new ArrayCollection();

        if (
            true === is_array($options) && 
            true === array_key_exists('label', $options)
        ) {
            $this->setLabel($options['label']);
        }
    }

    /**
     * Returns the unique identifier
     * @codeCoverageIgnore
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Returns the label
     * @codeCoverageIgnore
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns the associated server name
     * @codeCoverageIgnore
     * @return string|NULL
     */
    public function getServerName()
    {
        return $this->_server_name;
    }

    /**
     * Return the default defined extension.
     * @codeCoverageIgnore
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->_default_ext;
    }

    /**
     * Returns the collection of layouts available for this website.
     * @codeCoverageIgnore
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getLayouts()
    {
        return $this->_layouts;
    }

    /**
     * Returns the default metadatas set for the pages of this wesite.
     * @codeCoverageIgnore
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    /**
     * Sets the label of the website
     * @param string $label
     * @return \BackBuilder\Site\Site
     */
    public function setLabel($label)
    {
        $this->_label = $label;
        return $this;
    }

    /**
     * Adds a new metadata set to the collection of the website.
     * @codeCoverageIgnore
     * @param \BackBuilder\Site\Metadata\Metadata $metadata
     * @return \BackBuilder\Site\Site
     */
    public function setMetadata(Metadata $metadata)
    {
        $this->_metadata->add($metadata);
        return $this;
    }

    /**
     * @see BackBuilder\Services\Local\IJson::__toJson()
     */
    public function __toJson()
    {
        $result = new \stdClass();
        $result->label = $this->getLabel();
        $result->uid = $this->getUid();

        return $result;
    }

}