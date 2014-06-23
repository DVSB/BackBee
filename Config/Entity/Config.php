<?php

namespace BackBuilder\Config\Entity;

use DateTime;

/**
 * @Entity()
 */
class Config
{
    /**
     * @var string
     * @Id
     * @Column(type="string", name="id")
     */
    private $_id;

    /**
     * @var string
     * @Column(type="text", name="data")
     */
    private $_data;

    /**
     * @var \DateTime
     * @Column(type="datetime", name="updated_at")
     */
    private $_updatedAt;    

    /**
     * Gets the value of _id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * Sets the value of _id.
     *
     * @param string $_id the  id 
     *
     * @return self
     */
    private function _setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * Gets the value of _data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->_data;
    }
    
    /**
     * Sets the value of _data.
     *
     * @param string $_data the  data 
     *
     * @return self
     */
    private function _setData($data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Gets the value of _updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->_updatedAt;
    }
    
    /**
     * Sets the value of _updatedAt.
     *
     * @param \DateTime $_updatedAt the  updated at 
     *
     * @return self
     */
    private function _setUpdatedAt(DateTime $updatedAt)
    {
        $this->_updatedAt = $updatedAt;

        return $this;
    }
}
