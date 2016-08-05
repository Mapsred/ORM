<?php

namespace ORM;

/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 05/08/2016
 * Time: 18:48
 */

/**
 * Class Kernel
 */
class Kernel
{
    /** @var string $db_name */
    private static $db_name;
    /** @var string $db_host */
    private static $db_host;
    /** @var string $db_user */
    private static $db_user;
    /** @var string $db_pass */
    private static $db_pass;
    /** @var string $namespace_entity */
    private static $namespace_entity;
    /** @var string $namespace_repository */
    private static $namespace_repository;
    /** @var string $dir_entity */
    private static $dir_entity;
    /** @var string $dir_repository */
    private static $dir_repository;

    /**
     * @return string
     */
    public static function getDbName()
    {
        return self::$db_name;
    }

    /**
     * @param string $db_name
     */
    public static function setDbName($db_name)
    {
        self::$db_name = $db_name;
    }

    /**
     * @return string
     */
    public static function getDbHost()
    {
        return self::$db_host;
    }

    /**
     * @param string $db_host
     */
    public static function setDbHost($db_host)
    {
        self::$db_host = $db_host;
    }

    /**
     * @return string
     */
    public static function getDbUser()
    {
        return self::$db_user;
    }

    /**
     * @param string $db_user
     */
    public static function setDbUser($db_user)
    {
        self::$db_user = $db_user;
    }

    /**
     * @return string
     */
    public static function getDbPass()
    {
        return self::$db_pass;
    }

    /**
     * @param string $db_pass
     */
    public static function setDbPass($db_pass)
    {
        self::$db_pass = $db_pass;
    }

    /**
     * @return string
     */
    public static function getNamespaceEntity()
    {
        return self::$namespace_entity;
    }

    /**
     * @param string $namespace_entity
     */
    public static function setNamespaceEntity($namespace_entity)
    {
        self::$namespace_entity = $namespace_entity;
    }

    /**
     * @return string
     */
    public static function getNamespaceRepository()
    {
        return self::$namespace_repository;
    }

    /**
     * @param string $namespace_repository
     */
    public static function setNamespaceRepository($namespace_repository)
    {
        self::$namespace_repository = $namespace_repository;
    }

    /**
     * @return string
     */
    public static function getConfigPath()
    {
        return self::$configPath;
    }

    /**
     * @param string $configPath
     */
    public static function setConfigPath($configPath)
    {
        self::$configPath = $configPath;
    }

    /**
     * @return string
     */
    public static function getDirEntity()
    {
        return self::$dir_entity;
    }

    /**
     * @param string $dir_entity
     */
    public static function setDirEntity($dir_entity)
    {
        self::$dir_entity = $dir_entity;
    }

    /**
     * @return string
     */
    public static function getDirRepository()
    {
        return self::$dir_repository;
    }

    /**
     * @param string $dir_repository
     */
    public static function setDirRepository($dir_repository)
    {
        self::$dir_repository = $dir_repository;
    }

    /**
     * @param array $parameters
     */
    public static function setByArray(array $parameters)
    {
        foreach ($parameters as $key => $item) {
            $key = self::dashesToCamelCase($key, true);
            $setter = 'set'.$key;
            self::$setter($item);
        }
    }

    /**
     * @param string $string
     * @param bool $capitalizeFirst
     * @return string
     */
    private static function dashesToCamelCase($string, $capitalizeFirst = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $str[0] = $capitalizeFirst ? $str[0]:  strtolower($str[0]);

        return $str;
    }

}