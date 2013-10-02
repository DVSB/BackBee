<?php

namespace BackBuilder\Cache\DAO;

use BackBuilder\BBApplication,
    BackBuilder\Cache\AExtendedCache,
    BackBuilder\Cache\Exception\CacheException;

/**
 * Database cache adapter
 * 
 * It supports tag and expire features
 *
 * @category    BackBuilder
 * @package     BackBuilder\Cache\DAO
 * @copyright   Lp digital system
 * @author      c.rouillon
 */
class Cache extends AExtendedCache
{

    /**
     * The current BackBuilder application
     * @var \BackBuilder\BBApplication
     */
    private $_application;

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
     * Class constructor
     * @param \BackBuilder\BBApplication $application BackBuilder application
     * @throws \BackBuilder\Cache\Exception\CacheException Occurs if the entity repository can not be loaded
     */
    public function __construct(BBApplication $application)
    {
        $this->_application = $application;

        try {
            $this->_repository = $application->getEntityManager()
                    ->getRepository('BackBuilder\Cache\DAO\Entity');
        } catch (\Exception $e) {
            throw new CacheException('Enable to load the cache entity repository', null, $e);
        }
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
        if ($this->_application->debugMode()) {
            return false;
        }

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
                $this->_application
                        ->getEntityManager()
                        ->getConnection()
                        ->insert('cache', $params, $types);
            } else {
                $identifier = array('uid' => array_shift($params));
                $type = array_shift($types);
                $types[] = $type;

                $this->_application
                        ->getEntityManager()
                        ->getConnection()
                        ->update('cache', $params, $identifier, $types);
            }
        } catch (\Exception $e) {
            $this->_application->warning(sprintf('Enable to load cache for id %s : %s', $id, $e->getMessage()));
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
            $this->_application->warning(sprintf('Enable to remove cache for id %s : %s', $id, $e->getMessage()));
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
            $this->_application->warning(sprintf('Enable to remove cache for tags (%s) : %s', implode(',', $tags), $e->getMessage()));
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
            $this->_application->warning(sprintf('Enable to update cache for tags (%s) : %s', implode(',', $tags), $e->getMessage()));
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
            $this->_application->warning(sprintf('Enable to clear cache : %s', $e->getMessage()));
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