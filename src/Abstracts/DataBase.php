<?php

/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 12/05/2016
 * Time: 21:34
 */

namespace Maps_red\ORM\Abstracts;

use Maps_red\ORM\Kernel;

abstract class DataBase
{
    /** * @var \PDO $pdo */
    private static $pdo;
    /** * @var string $database */
    private $database;

    /**
     * @param $database
     * DataBase constructor.
     */
    public function __construct($database)
    {
        $this->setDatabase($database);
        $this->generatePdo();
    }

    /**
     * @return \PDO
     */
    public static function generatePdo()
    {
        $pdo = sprintf("mysql:dbname=%s;host=%s", Kernel::getDbName(), Kernel::getDbHost());
        self::$pdo = new \PDO($pdo, Kernel::getDbUser(), Kernel::getDbPass());

        return self::$pdo;
    }

    /**
     * @param $value
     * @return int|string
     */
    public static function secureEncodeSQL($value)
    {
        $oupperValue = strtoupper($value);
        if ($oupperValue != "NULL" && $oupperValue != "*") {
            $encodedValue = addslashes($value);
            $value = is_numeric($value) ? $value : "'$encodedValue'";
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     * @return DataBase
     */
    public function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @param $request
     * @return \PDOStatement
     */
    public function executing($request)
    {
        $request = sprintf($request, $this->database);
        $req = self::$pdo->prepare($request);
        $req->execute();

        return $req;
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return self::$pdo;
    }

    /**
     * @param \PDO $pdo
     */
    public function setPdo($pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * @param $value
     * @return string
     */
    protected function DateTimeFormat($value)
    {
        if (is_object($value) && $value instanceof \DateTime) {
            $value = $value->format("Y-m-d h:i:s");
        }

        return $value;
    }
}