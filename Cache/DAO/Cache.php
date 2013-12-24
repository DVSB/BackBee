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

namespace BackBuilder\Cache\DAO;

use BackBuilder\Cache\AExtendedCache,
    BackBuilder\Cache\Exception\CacheException;
use Psr\Log\LoggerInterface;

/**
 * Database cache adapter
 * 
 * It supports tag and expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache
 * @subpackage  DAO
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Cache extends AExtendedCache
{

    /**
     * The cache entity class name
     * @var string
     */
    const ENTITY_CLASSNAME = 'BackBuilder\Cache\DAO\Entity';

    /**
     * The Doctrine entity manager to use
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * The entity repository
     * @var \Doctrine\ORM\EntityRepository
     */
    private $_repository;

    /**
     * An entity for a store cache
     * @var \BackBuilder\Cache\DAO\Entity 
     */
    private $_entity;

    /**
     * The prefix key for cache items
     * @var type 
     */
    private $_prefix_key = '';

    /**
     * Cache adapter options
     * @var array
     */
    protected $_instance_options = array(
        'em' => null,
        'dbal' => array()
    );

    /**
     * Class constructor
     * @param array $options Initial options for the cache adapter:
     *          - em \Doctrine\ORM\EntityManager  Optional, an already defined EntityManager (simply returns it)
     *          - dbal array Optional, an array of Doctrine connection options among:
     *               - connection  \Doctrine\DBAL\Connection  Optional, an already initialized database connection
     *               - proxy_dir   string                     The proxy directory
     *               - proxy_ns    string                     The namespace for Doctrine proxy
     *               - charset     string                     Optional, the charset to use
     *               - collation   string                     Optional, the collation to use
     *               - ...         mixed                      All the required parameter to open a new connection
     * @param string $context An optional cache context
     * @param \Psr\Log\LoggerInterface $logger An optional logger
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the entity manager for this cache adaptor cannot be created
     */
    public function __construct(array $options = array(), $context = null, LoggerInterface $logger = null)
    {
        parent::__construct($options, $context, $logger);

        try {
            $this->_repository = $this->_em->getRepository(self::ENTITY_CLASSNAME);
        } catch (\Exception $e) {
            throw new CacheException('Enable to load the cache entity repository', null, $e);
        }
    }

    /**
     * Sets the memcache adapter instance options
     * @param array $options
     * @return \BackBuilder\Cache\DAO\Cache
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if a provided option is unknown for this adapter
     *                                                     or if enable to create a database conneciton.
     */
    protected function setInstanceOptions(array $options = array())
    {
        parent::setInstanceOptions($options);

        if ($this->_instance_options['em'] instanceof \Doctrine\ORM\EntityManager) {
            $this->_em = $this->_instance_options['em'];
        } else {
            try {
                $this->_em = BackBuilder\Util\Doctrine\EntityManagerCreator::create($this->_instance_options['dbal'], $this->getLogger());
            } catch (\BackBuilder\Exception\InvalidArgumentException $e) {
                throw new CacheException('DAO cache: enable to create a database conneciton');
            }
        }

        if (null !== $this->getContext()) {
            $this->_prefix_key = md5($this->getContext());
        }

        return $this;
    }

    /**
     * Returns the available cache for the given id if found returns false else
     * @param string $id Cache id
     * @param boolean $bypassCheck Allow to find cache without test it before
     * @param \DateTime $expire Optionnal, the expiration time (now by default)
     * @return string|FALSE
     */
    public function load($id, $bypassCheck = false, \DateTime $expire = null)
    {
        if (null === $this->_getCacheEntity($id)) {
            return false;
        }

        if (null === $expire) {
            $expire = new \DateTime();
        }

        $last_timestamp = $this->test($id);
        if (true === $bypassCheck
                || false === $last_timestamp
                || $expire->getTimestamp() <= $last_timestamp) {
            return $this->_getCacheEntity($id)->getData();
        }

        return false;
    }

    /**
     * Tests if a cache is available or not (for the given id)
     * @param string $id Cache id
     * @return int|FALSE the last modified timestamp of the available cache record
     */
    public function test($id)
    {
        if (null === $this->_getCacheEntity($id)) {
            return false;
        }

        if (null !== $this->_getCacheEntity($id)->getExpire()) {
            return $this->_getCacheEntity($id)->getExpire()->getTimestamp();
        }

        return false;
    }

    /**
     * Save some string datas into a cache record
     * @param string $id Cache id
     * @param string $data Datas to cache
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @param string $tag Optional, an associated tag to the data stored
     * @return boolean TRUE if cache is stored FALSE otherwise
     */
    public function save($id, $data, $lifetime = null, $tag = null)
    {
        try {
            $params = array(
                'uid' => $id,
                'tag' => $tag,
                'data' => $data,
                'expire' => $this->_getExpireDateTime($lifetime),
                'created' => new \DateTime()
            );

            $types = array(
                'string',
                'string',
                'string',
                'datetime',
                'datetime'
            );

            if (null === $this->_getCacheEntity($id)) {
                $this->_em->getConnection()
                        ->insert('cache', $params, $types);
            } else {
                $identifier = array('uid' => array_shift($params));
                $type = array_shift($types);
                $types[] = $type;

                $this->_em->getConnection()
                        ->update('cache', $params, $identifier, $types);
            }
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to load cache for id %s : %s', $id, $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Removes a cache record
     * @param  string $id Cache id
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function remove($id)
    {
        try {
            $this->_repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->andWhere('c._uid = :uid')
                    ->setParameters(array('uid' => $id))
                    ->getQuery()
                    ->execute();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to remove cache for id %s : %s', $id, $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Removes all cache records associated to one of the tags
     * @param  string|array $tag
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function removeByTag($tag)
    {
        $tags = (array) $tag;

        if (0 == count($tags)) {
            return false;
        }

        try {
            $this->_repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->andWhere('c._tag IN (:tags)')
                    ->setParameters(array('tags' => $tags))
                    ->getQuery()
                    ->execute();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to remove cache for tags (%s) : %s', implode(',', $tags), $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Updates the expire date time for all cache records 
     * associated to one of the provided tags
     * @param  string|array $tag
     * @param int $lifetime Optional, the specific lifetime for this record 
     *                      (by default null, infinite lifetime)
     * @return boolean TRUE if cache is removed FALSE otherwise
     */
    public function updateExpireByTag($tag, $lifetime = null)
    {
        $tags = (array) $tag;

        if (0 == count($tags)) {
            return false;
        }

        $expire = $this->_getExpireDateTime($lifetime);

        try {
            $this->_repository
                    ->createQueryBuilder('c')
                    ->update()
                    ->set('c._expire', ':expire')
                    ->andWhere('c._tag IN (:tags)')
                    ->setParameters(array(
                        'expire' => $expire,
                        'tags' => $tags))
                    ->getQuery()
                    ->execute();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to update cache for tags (%s) : %s', implode(',', $tags), $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Clears all cache records
     * @return boolean TRUE if cache is cleared FALSE otherwise
     */
    public function clear()
    {
        try {
            $this->_repository
                    ->createQueryBuilder('c')
                    ->delete()
                    ->andWhere('1 = 1')
                    ->getQuery()
                    ->execute();
        } catch (\Exception $e) {
            $this->log('warning', sprintf('Enable to clear cache : %s', $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Returns the store entity for provided cache id
     * @param string $id Cache id
     * @return \BackBuilder\Cache\DAO\Entity The cache entity or NULL
     */
    private function _getCacheEntity($id)
    {
        if (null === $this->_entity || $this->_entity->getId() !== $id) {
            $this->_entity = $this->_repository->find($id);
        }

        return $this->_entity;
    }

    /**
     * Returns the expiration date time
     * @param int $lifetime
     * @return \DateTime
     */
    private function _getExpireDateTime($lifetime = null)
    {
        $expire = null;

        if (null !== $lifetime && 0 !== $lifetime) {
            $expire = new \DateTime ();

            if (0 < $lifetime) {
                $expire->add(new \DateInterval('PT' . $lifetime . 'S'));
            } else {
                $expire->sub(new \DateInterval('PT' . (-1 * $lifetime) . 'S'));
            }
        }

        return $expire;
    }

}