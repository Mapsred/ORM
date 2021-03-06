<?php

/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 12/05/2016
 * Time: 21:51
 */
namespace Maps_red\ORM\Abstracts;

use Maps_red\ORM\Builder\QueryBuilder;
use Maps_red\ORM\Kernel;

abstract class MainRepository extends DataBase
{
    const SELECT = "SELECT %s.* FROM %s ";
    const INSERT = "INSERT INTO %s ";
    const DELETE = "DELETE FROM %s ";
    /** @var MainEntity $entity */
    private $entity;
    /** @var MainRepository $repository */
    private $repository;

    /**
     * MainCollection constructor.
     * @param $database
     * @param $entity
     * @param $repository
     */
    public function __construct($database, $entity, $repository)
    {
        parent::__construct($database);
        $this->entity = $entity;
        $this->repository = $repository;
    }

    /**
     * @param $object
     * @param array $data
     * @param array $fields
     * @param array $repository
     * @return object
     */
    public static function customHydrate($object, array $data, array $fields, array $repository)
    {
        /** @var MainEntity $object */
        $object = self::hydrate($object, $data);
        foreach ($fields as $key => $field) {
            $repository = Kernel::getNamespaceRepository().$repository[$key];
            $fieldCamel = Kernel::dashesToCamelCase($field, true);
            $getter = "get".$fieldCamel;
            $fieldData = $object->$getter();
            $setter = "set".$fieldCamel;
            $object->$setter($fieldData, true);
        }

        return $object;
    }

    /**
     * @param MainEntity $object
     * @param array $data
     * @return MainEntity
     */
    public static function hydrate($object, array $data)
    {
        foreach ($data as $key => $item) {
            $fieldCamel = Kernel::dashesToCamelCase($key, true);
            $setter = "set".$fieldCamel;
            if (method_exists($object, $setter)) {
                $object->$setter($item);
            }
        }

        return $object;
    }

    /**
     * @return mixed
     */
    public function findOne()
    {
        $request = self::SELECT."LIMIT 1";
        $data = $this->executing($request)->fetch(\PDO::FETCH_ASSOC);
        $object = new $this->entity();
        $data = !$data ? $data : $this->hydrate($object, $data);

        return $data;
    }

    /**
     * @return array
     */
    public function findAll()
    {
        $request = self::SELECT;
        $datas = $this->executing($request)->fetchAll(\PDO::FETCH_ASSOC);
        $returnArray = [];
        foreach ($datas as $item) {
            $object = new $this->entity();
            $data = !$item ? $item : $this->hydrate($object, $item);
            $returnArray[] = $data;
        }

        return $returnArray;
    }

    /**
     * @param $entity
     * @return object|null
     */
    public function save($entity)
    {
        /** @var object $entity */
        $fields = $entity->getId() ? $entity->getFields() : $entity->getFields(true);
        $values = [];
        $columns = [];
        $updating = [];
        foreach ($fields as $field) {
            $fieldCamel = Kernel::dashesToCamelCase($field, true);
            $getter = "get".$fieldCamel;
            $value = $entity->$getter();
            $value = $this->DateTimeFormat($value);
            if (!isset($value)) {
                continue;
            } elseif (is_object($value)) {
                /** @var MainEntity $value */
                $value = $value->getId();
            }

            $columns[$field] = $field;
            $value = self::secureEncodeSQL($value);
            $values[$field] = $value;
            $updating[] = "$field=$value";
        }

        if ($entity->getId()) {
            $request = "SET %s WHERE id = %s";
            $request = "UPDATE %s ".sprintf($request, implode(", ", $updating), $entity->getId());
        } else {
            $request = self::INSERT.sprintf(" (%s) VALUES (%s)", implode(", ", $columns), implode(", ", $values));
        }

        $this->executing($request);

        return $entity->getId() ? $this->findOneById($entity->getId()) : $this->findLastOne();
    }

    /**
     * @param $id
     * @return mixed|object
     */
    public function findOneById($id)
    {
        return self::findOneBy(['id' => $id]);
    }

    /**
     * @param array $array
     * @return mixed|object
     */
    public function findOneBy(array $array)
    {
        $key = key($array);
        $key = explode(".", $key, 2);
        if (count($key) == 2) {
            $db = $this->getDatabase();
            $field = implode(".", $key);
            $value = self::secureEncodeSQL($array[$field]);
            $request = sprintf("%sLEFT JOIN %s ON %s.%s = %s.id", self::SELECT, $key[0], $db, $key[0], $key[0]);
            $request .= sprintf(" WHERE %s = %s", $key[1], $value);
        } else {
            $value = self::secureEncodeSQL($array[$key[0]]);
            $request = sprintf("%sWHERE %s = %s", self::SELECT, $key[0], $value);
        }

        $request .= " LIMIT 1";
        $data = $this->executing($request)->fetch(\PDO::FETCH_ASSOC);
        $object = new $this->entity();
        $data = !$data ? $data : $this->hydrate($object, $data);

        return $data;
    }

    /**
     * @return object|null
     */
    public function findLastOne()
    {
        $request = self::SELECT."ORDER BY id DESC";
        $data = $this->executing($request)->fetch(\PDO::FETCH_ASSOC);
        $object = new $this->entity();
        $data = !$data ? $data : $this->hydrate($object, $data);

        return $data;
    }

    /**
     * @param array $array
     * @param array|null $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findBy(array $array, array $order = null, $limit = null, $offset = null)
    {
        if (!empty($array)) {
            $key = key($array);
            $key = explode(".", $key, 2);
            if (count($key) == 2) {
                $db = $this->getDatabase();
                $field = implode(".", $key);
                $value = self::secureEncodeSQL($array[$field]);
                $request = sprintf("%sLEFT JOIN %s ON %s.%s = %s.id", self::SELECT, $key[0], $db, $key[0], $key[0]);
                $request .= sprintf(" WHERE %s = %s", $key[1], $value);
            } else {
                $value = self::secureEncodeSQL($array[$key[0]]);
                $request = sprintf("%sWHERE %s = %s", self::SELECT, $key[0], $value);
            }
        } else {
            $request = self::SELECT;
        }
        if (isset($order) && !empty($order)) {
            $request .= sprintf("ORDER BY %s %s", key($order), strtoupper($order[key($order)]));
        }
        if (isset($limit)) {
            $request .= " LIMIT $limit";
        }
        if (isset($offset)) {
            $request .= " OFFSET $limit";
        }

        $datas = $this->executing($request)->fetchAll(\PDO::FETCH_ASSOC);
        $returnArray = [];
        foreach ($datas as $item) {
            $object = new $this->entity();
            $data = !$item ? $item : $this->hydrate($object, $item);
            $returnArray[] = $data;
        }

        return $returnArray;
    }

    /**
     * @param $entity
     * @return bool
     */
    public function remove($entity)
    {
        /** @var object $entity */
        $query = self::DELETE.sprintf("WHERE id = %s", $entity->getId());

        $this->executing($query);

        return empty($this->findOneById($entity->getId()));
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        $queryBuilder = new QueryBuilder();

        return $queryBuilder->from($this->getDatabase())->setEntity($this->entity)->setRepository($this->repository);
    }

    /**
     * @param $array
     * @return array
     */
    public function toArray($array)
    {
        $entities = [];
        /** @var MainEntity $item */
        foreach ($array as $item) {
            $entities[] = $item->__toArray();
        }

        return $entities;
    }
}