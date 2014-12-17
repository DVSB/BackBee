<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 *
 * This file is part of BackBee5.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Util\Doctrine;

use BackBee\Exception\InvalidArgumentException;
use BackBee\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

/**
 * Utility class to create a new Doctrine entity manager
 *
 * @category    BackBee
 * @package     BackBee\Util
 * @subpackage  Doctrine
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class EntityManagerCreator
{
    /**
     * Creates a new Doctrine entity manager
     * @param  array                                           $options Options provided to get an entity manager, keys should be :
     *                                                                  - entity_manager \Doctrine\ORM\EntityManager  Optional, an already defined EntityManager (simply returns it)
     *                                                                  - connection     \Doctrine\DBAL\Connection    Optional, an already initialized database connection
     *                                                                  - proxy_dir      string                       The proxy directory
     *                                                                  - proxy_ns       string                       The namespace for Doctrine proxy
     *                                                                  - charset        string                       Optional, the charset to use
     *                                                                  - collation      string                       Optional, the collation to use
     *                                                                  - ...            mixed                        All the required parameter to open a new connection
     * @param  \Psr\Log\LoggerInterface                        $logger  Optional logger
     * @param  \Doctrine\Common\EventManager                   $evm     Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if $entity_manager can not be returned
     */
    public static function create(array $options = array(), LoggerInterface $logger = null, EventManager $evm = null, ContainerInterface $container = null)
    {
        if (true === array_key_exists('entity_manager', $options)) {
            // Test the nature of the entity_manager parameter
            $em = self::_getEntityManagerWithEntityManager($options['entity_manager']);
        } else {
            // Init ORM Configuration
            $config = self::_getORMConfiguration($options, $logger, $container);

            if (true === array_key_exists('connection', $options)) {
                // An existing connection is provided
                $em = self::_createEntityManagerWithConnection($options['connection'], $config, $evm);
            } else {
                $em = self::_createEntityManagerWithParameters($options, $config, $evm);
            }
        }

        self::_setConnectionCharset($em->getConnection(), $options);
        self::_setConnectionCollation($em->getConnection(), $options);

        if ('sqlite' === $em->getConnection()->getDatabasePlatform()->getName()) {
            self::_expandSqlite($em->getConnection());
        }

        return $em;
    }

    /**
     * Custom SQLite logic
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private static function _expandSqlite(\Doctrine\DBAL\Connection $connection)
    {
        // add support for REGEXP operator
        $connection->getWrappedConnection()->sqliteCreateFunction('regexp', function ($pattern, $data, $delimiter = '~', $modifiers = 'isuS') {
                if (isset($pattern, $data) === true) {
                    return (preg_match(sprintf('%1$s%2$s%1$s%3$s', $delimiter, $pattern, $modifiers), $data) > 0);
                }

                return;
            }
        );
    }

    /**
     * Returns a new ORM Configuration
     * @param  array                       $options Optional, the options to create the new Configuration
     * @return \Doctrine\ORM\Configuration
     * @codeCoverageIgnore
     */
    private static function _getORMConfiguration(array $options = array(), LoggerInterface $logger = null, ContainerInterface $container = null)
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

        if (true === array_key_exists('metadata_cache', $options) && isset($options['metadata_type'])) {
            if ($options['metadata_type'] == 'memcached') {
                $memcached = new \Memcached();
                foreach ($options['metadata_cache']['servers'] as $server) {
                    $memcached->addServer($server['host'], $server['port']);
                }
                $memcacheDriver = new MemcachedCache();
                $memcacheDriver->setMemcached($memcached);

                $config->setMetadataCacheImpl($memcacheDriver);
            } elseif ($options['metadata_type'] == 'memcache') {
                $memcache = new \Memcache();
                foreach ($options['metadata_cache']['servers'] as $server) {
                    $memcache->addServer($server['host'], $server['port']);
                }

                $memcacheDriver = new MemcacheCache();
                $memcacheDriver->setMemcache($memcache);

                $config->setMetadataCacheImpl($memcacheDriver);
            } elseif ($options['metadata_type'] == 'apc') {
                $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
            }
        }

        if (true === array_key_exists('query_cache', $options) && isset($options['query_type'])) {
            if ($options['query_type'] == 'memcached') {
                $memcached = new \Memcached();

                foreach ($options['query_cache']['servers'] as $server) {
                    $memcached->addServer($server['host'], $server['port']);
                }

                $memcachedDriver = new MemcachedCache();
                $memcachedDriver->setMemcached($memcached);

                $config->setQueryCacheImpl($memcachedDriver);
            } elseif ($options['query_type'] == 'memcache') {
                $memcache = new \Memcache();

                foreach ($options['query_cache']['servers'] as $server) {
                    $memcache->addServer($server['host'], $server['port']);
                }

                $memcacheDriver = new MemcacheCache();
                $memcacheDriver->setMemcache($memcache);

                $config->setQueryCacheImpl($memcacheDriver);
            }
        }

        if (true === array_key_exists('orm', $options)) {
            if (true === array_key_exists('proxy_namespace', $options['orm'])) {
                $config->setProxyNamespace($options['orm']['proxy_namespace']);
            }

            if (true === array_key_exists('proxy_dir', $options['orm'])) {
                $config->setProxyDir($options['orm']['proxy_dir']);
            }

            if (true === array_key_exists('auto_generate_proxy_classes', $options['orm'])) {
                $config->setAutoGenerateProxyClasses($options['orm']['auto_generate_proxy_classes']);
            }

            if (true === array_key_exists('metadata_cache_driver', $options['orm']) && true === is_array($options['orm']['metadata_cache_driver'])) {
                if (true === array_key_exists('type', $options['orm']['metadata_cache_driver'])) {
                    if ('service' === $options['orm']['metadata_cache_driver']['type'] && true === isset($options['orm']['metadata_cache_driver']['id'])) {
                        $service_id = str_replace('@', '', $options['orm']['metadata_cache_driver']['id']);
                        if ($container->has($service_id)) {
                            $config->setMetadataCacheImpl($container->get($service_id));
                        }
                    }
                }
            }

            if (true === array_key_exists('query_cache_driver', $options['orm']) && true === is_array($options['orm']['query_cache_driver'])) {
                if (true === array_key_exists('type', $options['orm']['query_cache_driver'])) {
                    if ('service' === $options['orm']['query_cache_driver']['type'] && true === isset($options['orm']['query_cache_driver']['id'])) {
                        $service_id = str_replace('@', '', $options['orm']['query_cache_driver']['id']);
                        if ($container->has($service_id)) {
                            $config->setQueryCacheImpl($container->get($service_id));
                        }
                    }
                }
            }
        }

        if ($logger instanceof SQLLogger) {
            $config->setSQLLogger($logger);
        }

        return self::_addCustomFunctions($config, $options);
    }

    /**
     * Adds userdefined functions
     * @param  \Doctrine\ORM\Configuration $config
     * @param  array                       $options
     * @return \Doctrine\ORM\Configuration
     */
    private static function _addCustomFunctions(Configuration $config, array $options = array())
    {
        if (null !== $string_functions = \BackBee\Util\Arrays::get($options, 'orm:entity_managers:default:dql:string_functions')) {
            foreach ($string_functions as $name => $class) {
                if (true === class_exists($class)) {
                    $config->addCustomStringFunction($name, $class);
                }
            }
        }

        if (null !== $numeric_functions = \BackBee\Util\Arrays::get($options, 'orm:entity_managers:default:dql:numeric_functions')) {
            foreach ($numeric_functions as $name => $class) {
                if (true === class_exists($class)) {
                    $config->addCustomNumericFunction($name, $class);
                }
            }
        }

        if (null !== $datetime_functions = \BackBee\Util\Arrays::get($options, 'orm:entity_managers:default:dql:datetime_functions')) {
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
     * @param  \Doctrine\ORM\EntityManager                     $entity_manager
     * @param  \Doctrine\Common\EventManager                   $evm            Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if $entity_manager is not an EntityManager
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
     * @param  \Doctrine\DBAL\Connection                       $connection
     * @param  \Doctrine\ORM\Configuration                     $config
     * @param  \Doctrine\Common\EventManager                   $evm        Optional event manager
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if $entity_manager can not be created
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
     * @param  array                                           $options
     * @param  \Doctrine\ORM\Configuration                     $config
     * @return \Doctrine\ORM\EntityManager
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if $entity_manager can not be created
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
     * @param  \Doctrine\DBAL\Connection                       $connection
     * @param  array                                           $options
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if charset is invalid
     */
    private static function _setConnectionCharset(Connection $connection, array $options = array())
    {
        if (true === array_key_exists('charset', $options)) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery('SET SESSION character_set_client = "'.addslashes($options['charset']).'";');
                    $connection->executeQuery('SET SESSION character_set_connection = "'.addslashes($options['charset']).'";');
                    $connection->executeQuery('SET SESSION character_set_results = "'.addslashes($options['charset']).'";');
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid database character set `%s`', $options['charset']), InvalidArgumentException::INVALID_ARGUMENT, $e);
            }
        }
    }

    /**
     * Sets the collation for the provided connection
     * @param  \Doctrine\DBAL\Connection                       $connection
     * @param  array                                           $options
     * @throws \BackBee\Exception\InvalidArgumentException Occurs if collation is invalid
     */
    private static function _setConnectionCollation(Connection $connection, array $options = array())
    {
        if (true === array_key_exists('collation', $options)) {
            try {
                if ('pdo_mysql' === $connection->getDriver()->getName()) {
                    $connection->executeQuery('SET SESSION collation_connection = "'.addslashes($options['collation']).'";');
                }
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid database collation `%s`', $options['collation']), InvalidArgumentException::INVALID_ARGUMENT, $e);
            }
        }
    }
}
