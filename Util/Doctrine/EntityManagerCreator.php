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

namespace BackBuilder\Util\Doctrine;

use BackBuilder\Exception\InvalidArgumentException;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\ORM\Configuration,
    Doctrine\DBAL\Connection,
    Doctrine\ORM\EntityManager,
    Doctrine\DBAL\Logging\SQLLogger,
    Doctrine\Common\EventManager,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\SimpleAnnotationReader,
    Doctrine\Common\Annotations\CachedReader,
    Doctrine\Common\Cache\ArrayCache;

use Psr\Log\LoggerInterface;

use BackBuilder\Annotations\ChainAnnotationReader;

/**
 * Utility class to create a new Doctrine entity manager
 *
 * @category    BackBuilder
 * @package     BackBuilder\Util
 * @subpackage  Doctrine
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class EntityManagerCreator
{

    /**
     * Creates a new Doctrine entity manager
     * @param array $options Options provided to get an entity manager, keys should be :
     *                         - entity_manager \Doctrine\ORM\EntityManager  Optional, an already defined EntityManager (simply returns it)
     *                         - connection     \Doctrine\DBAL\Connection    Optional, an already initialized database connection
     *                         - proxy_dir      string                       The proxy directory
     *                         - proxy_ns       string                       The namespace for Doctrine proxy
     *                         - charset        string                       Optional, the charset to use
     *                         - collation      string                       Optional, the collation to use
     *                         - ...            mixed                        All the required parameter to open a new connection
     * @param \Psr\Log\LoggerInterface $logger Optional logger
     * @param \Doctrine\Common\EventManager $evm Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if $entity_manager can not be returned
     */
    public static function create(array $options = array(), LoggerInterface $logger = null, EventManager $evm = null)
    {
        if (true === array_key_exists('entity_manager', $options)) {
            // Test the nature of the entity_manager parameter
            $em = self::_getEntityManagerWithEntityManager($options['entity_manager']);
        } else {
            // Init ORM Configuration
            $config = self::_getORMConfiguration($options, $logger);

            if (true === array_key_exists('connection', $options)) {
                // An already connection is provided
                $em = self::_createEntityManagerWithConnection($options['connection'], $config, $evm);
            } else {
                $em = self::_createEntityManagerWithParameters($options, $config, $evm);
            }
        }

        self::_setConnectionCharset($em->getConnection(), $options);
        self::_setConnectionCollation($em->getConnection(), $options);

        return $em;
    }

    /**
     * Returns a new ORM Configuration
     * @param array $options Optional, the options to create the new Configuration
     * @return \Doctrine\ORM\Configuration
     * @codeCoverageIgnore
     */
    private static function _getORMConfiguration(array $options = array(), LoggerInterface $logger = null)
    {
        $config = new Configuration();
        
        $driverImpl = $config->newDefaultAnnotationDriver();
        
        $config->setMetadataDriverImpl($driverImpl);

        if (true === array_key_exists('proxy_dir', $options)) {
            $config->setProxyDir($options['proxy_dir']);
        }

        if (true === array_key_exists('proxy_ns', $options)) {
            $config->setProxyNamespace($options['proxy_ns']);
        }

        if (true === array_key_exists('auto_generate_proxies', $options)) {
            $config->setAutoGenerateProxyClasses($options['auto_generate_proxies']);
        }

        if (true === array_key_exists('metadata_cache', $options)) {
            if($options['metadata_cache']['cachetype'] == 'memcache'){

                $memcached = new \Memcached();

                foreach($options['metadata_cache']['servers'] AS $server){
                    $memcached->addServer($server['host'], $server['port']);
                }
                $memcacheDriver = new MemcachedCache();
                $memcacheDriver->setMemcached($memcached);

                $config->setMetadataCacheImpl($memcacheDriver);

            }

        }

        if (true === array_key_exists('query_cache', $options)) {
            if($options['query_cache']['cachetype'] == 'memcache'){

                $memcached = new \Memcached();

                foreach($options['query_cache']['servers'] AS $server){
                    $memcached->addServer($server['host'], $server['port']);
                }
                $memcacheDriver = new MemcachedCache();
                $memcacheDriver->setMemcached($memcached);

                $config->setQueryCacheImpl($memcacheDriver);

            }

        }

        if ($logger instanceof SQLLogger) {
            $config->setSQLLogger($logger);
        }
        
        return self::_addCustonFunctions($config, $options);
    }

    /**
     * Adds userdefined functions
     * @param \Doctrine\ORM\Configuration $config
     * @param array $options
     * @return \Doctrine\ORM\Configuration
     */
    private static function _addCustonFunctions(Configuration $config, array $options = array())
    {
        if (null !== $string_functions = \BackBuilder\Util\Arrays::get($options, 'orm:entity_managers:default:dql:string_functions')) {
            foreach ($string_functions as $name => $class) {
                if (true === class_exists($class)) {
                    $config->addCustomStringFunction($name, $class);
                }
            }
        }

        if (null !== $numeric_functions = \BackBuilder\Util\Arrays::get($options, 'orm:entity_managers:default:dql:numeric_functions')) {
            foreach ($numeric_functions as $name => $class) {
                if (true === class_exists($class)) {
                    $config->addCustomNumericFunction($name, $class);
                }
            }
        }

        if (null !== $datetime_functions = \BackBuilder\Util\Arrays::get($options, 'orm:entity_managers:default:dql:datetime_functions')) {
            foreach ($datetime_functions as $name => $class) {
                if (true === class_exists($class)) {
                    $config->addCustomDatetimeFunction($name, $class);
                }
            }
        }

        return $config;
    }

    /**
     * Returns the EntityManager provided
     * @param \Doctrine\ORM\EntityManager $entity_manager
     * @param \Doctrine\Common\EventManager $evm Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if $entity_manager is not an EntityManager
     */
    private static function _getEntityManagerWithEntityManager($entity_manager)
    {
        if (true === is_object($entity_manager) && $entity_manager instanceof EntityManager) {
            return $entity_manager;
        }

        throw new InvalidArgumentException('Invalid EntityManager provided', InvalidArgumentException::INVALID_ARGUMENT);
    }

    /**
     * Returns a new EntityManager with the provided connection
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Doctrine\ORM\Configuration $config
     * @param \Doctrine\Common\EventManager $evm Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if $entity_manager can not be created
     */
    private static function _createEntityManagerWithConnection($connection, Configuration $config, EventManager $evm = null)
    {
        if (true === is_object($connection) && $connection instanceof Connection) {
            try {
                return EntityManager::create($connection, $config, $evm);
            } catch (\Exception $e) {
                throw new InvalidArgumentException('Enable to create new EntityManager with provided Connection', InvalidArgumentException::INVALID_ARGUMENT, $e);
            }
        }

        throw new InvalidArgumentException('Invalid Connection provided', InvalidArgumentException::INVALID_ARGUMENT);
    }

    /**
     * Returns a new EntityManager with the provided parameters
     * @param array $options
     * @param \Doctrine\ORM\Configuration $config
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if $entity_manager can not be created
     */
    private static function _createEntityManagerWithParameters(array $options, Configuration $config, EventManager $evm = null)
    {
        try {
            return EntityManager::create($options, $config, $evm);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Enable to create new EntityManager with provided parameters', InvalidArgumentException::INVALID_ARGUMENT, $e);
        }
    }

    /**
     * Sets the character set for the provided connection
     * @param \Doctrine\DBAL\Connection $connection
     * @param array $options
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if charset is invalid
     */
    private static function _setConnectionCharset(Connection $connection, array $options = array())
    {
        if (true === array_key_exists('charset', $options)) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery('SET SESSION character_set_client = "' . addslashes($options['charset']) . '";');
                    $connection->executeQuery('SET SESSION character_set_connection = "' . addslashes($options['charset']) . '";');
                    $connection->executeQuery('SET SESSION character_set_results = "' . addslashes($options['charset']) . '";');
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid database character set `%s`', $options['charset']), InvalidArgumentException::INVALID_ARGUMENT, $e);
            }
        }
    }

    /**
     * Sets the collation for the provided connection
     * @param \Doctrine\DBAL\Connection $connection
     * @param array $options
     * @throws \BackBuilder\Exception\InvalidArgumentException Occurs if collation is invalid
     */
    private static function _setConnectionCollation(Connection $connection, array $options = array())
    {
        if (true === array_key_exists('collation', $options)) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery('SET SESSION collation_connection = "' . addslashes($options['collation']) . '";');
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid database collation `%s`', $options['collation']), InvalidArgumentException::INVALID_ARGUMENT, $e);
            }
        }
    }

}
