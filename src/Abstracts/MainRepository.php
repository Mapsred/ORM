<?php

/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 12/05/2016
 * Time: 21:51
 */
namespace ORM\Abstracts;

use ORM\Builder\QueryBuilder;
use ORM\Kernel;

abstract class MainRepository extends DataBase
{
    const SELECT = "SELECT * FROM %s ";
    const INSERT = "INSERT INTO %s ";
    const DELETE = "DELETE FROM %s ";
    /** @var object $entity */
    private $entity;

    /**
     * MainCollection constructor.
     * @param $database
     * @param $entity
     */
    public function __construct($database, $entity)
    {
        parent::__construct($database);
        $this->entity = $entity;
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
            /** @var MainRepository $objRepo */
            $objRepo = new $repository();
            $getter = "get".ucfirst($field);
            $fieldData = $object->$getter();
            $setter = "set".ucfirst($field);
            $object->$setter($fieldData, true);
        }

        return $object;
    }

    /**
     * @param object $object
     * @param array $data
     * @return object
     */
    public static function hydrate($object, array $data)
    {
        foreach ($data as $key => $item) {
            $setter = "set".ucfirst($key);
            $object->$setter($item);
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
            $getter = "get".ucfirst($field);
            $value = $entity->$getter();
            if (!isset($value)) {
                continue;
            } elseif (is_object($value)) {
                /** @var MainEntity $value */
                $value = $value->getId();
            }

            $columns[$field] = $field;
            $value = $this->DateTimeFormat($value);
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
     * @param array $order
     * @return mixed|object
     */
    public function findOneBy(array $array, array $order = null)
    {
        $key = key($array);
        $value = self::secureEncodeSQL($array[$key]);
        $request = sprintf("%sWHERE %s = %s", self::SELECT, $key, $value);
        if (isset($order)) {
            $request .= sprintf("ORDER BY %s %s", key($order), strtoupper($order[key($order)]));
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
     * @return array
     */
    public function findBy(array $array, array $order = null)
    {
        $key = key($array);
        $value = self::secureEncodeSQL($array[$key]);
        $request = sprintf("%sWHERE %s = %s", self::SELECT, $key, $value);
        if (isset($order)) {
            $request .= sprintf("ORDER BY %s %s", key($order), strtoupper($order[key($order)]));
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
    public function QueryBuilder()
    {
        $queryBuilder = new QueryBuilder();

        return $queryBuilder->from($this->getDatabase())->setEntity($this->entity);
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