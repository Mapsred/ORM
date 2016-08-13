<?php
/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 15/05/2016
 * Time: 15:58
 */

namespace Maps_red\ORM\Builder;

use Maps_red\ORM\Abstracts\DataBase;
use Maps_red\ORM\Abstracts\MainEntity;
use Maps_red\ORM\Abstracts\MainRepository;

class QueryBuilder
{
    /** @var \PDO $pdo */
    private $pdo;
    /** @var string $select */
    private $select = "*";
    /** @var string $from */
    private $from;
    /** @var string $where */
    private $where;
    /** @var array $moreWhere */
    private $moreWhere;
    /** @var array $orderBy */
    private $orderBy;
    /** @var integer $_maxResults */
    private $_maxResults;
    /** @var string $query */
    private $query;
    /** @var MainEntity $entity */
    private $entity;
    /** @var MainRepository $repository */
    private $repository;
    /** @var array $joins */
    private $joins;

    const QUERY_UNIQUE = 1;

    /**
     * QueryBuilder constructor.
     */
    public function __construct()
    {
        $this->pdo = DataBase::generatePdo();
    }

    /**
     * @param null $select
     * @return QueryBuilder
     */
    public function select($select = null)
    {
        $select = $select ? $select : "$this->from.*";
        $this->select = DataBase::secureEncodeSQL($select);

        return $this;
    }

    /**
     * @param $from
     * @return QueryBuilder
     */
    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param null $where
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function where($where = null)
    {
        $whereArray = explode(" ", $where, 3);
        if (count($whereArray) < 3) {
            throw new QueryBuilderException("Where must have spaces between the operator");
        } else {
            $value = DataBase::secureEncodeSQL($whereArray[2]);
            $this->where = sprintf("%s %s %s", $whereArray[0], $whereArray[1], $value);
        }

        return $this;
    }

    /**
     * @param $maxResults
     * @return QueryBuilder
     */
    public function setMaxResults($maxResults)
    {
        $this->_maxResults = $maxResults;

        return $this;
    }

    /**
     * @param null $where
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function andWhere($where = null)
    {
        return $this->addWhere($where, "and");
    }

    /**
     * @param $where
     * @param $type
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    private function addWhere($where, $type)
    {
        $whereArray = explode(" ", $where, 3);
        if (count($whereArray) < 3) {
            throw new QueryBuilderException("Where must have spaces between the operator");
        } else {
            $value = DataBase::secureEncodeSQL($whereArray[2]);
            $content = sprintf("%s %s %s", $whereArray[0], $whereArray[1], $value);
        }
        $this->moreWhere[] = [
            'type' => strtoupper($type),
            'content' => $content,
        ];

        return $this;
    }

    /**
     * @param null $where
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function orWhere($where = null)
    {
        return $this->addWhere($where, "or");
    }

    /**
     * @param $sort
     * @param null $order
     * @return QueryBuilder
     */
    public function addOrder($sort, $order = null)
    {
        return $this->orderBy($sort, $order);
    }

    /**
     * @param $sort
     * @param null $order
     * @return QueryBuilder
     */
    public function orderBy($sort, $order = null)
    {
        $this->orderBy[] = sprintf("%s %s", $sort, strtoupper($order));

        return $this;
    }

    /**
     * @param $field
     * @return QueryBuilder
     */
    public function leftJoin($field)
    {
        $this->joins[] = $field;

        return $this;
    }

    /**
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function getQuery()
    {
        if (!$this->from) {
            throw new QueryBuilderException("FROM must be defined");
        }
        $this->select = $this->select != "*" ? $this->select : $this->from.".*";
        $query = sprintf("SELECT %s FROM %s", $this->select, $this->from);
        if ($this->joins) {
            foreach ($this->joins as $join) {
                $query.= sprintf(" LEFT JOIN %s ON %s.%s = %s.id", $join, $this->from, $join, $join);
            }
        }

        if ($this->where) {
            $query .= sprintf(" WHERE %s", $this->where);
        }
        if ($this->moreWhere) {
            foreach ($this->moreWhere as $item) {
                $query .= sprintf(" %s %s", $item['type'], $item['content']);
            }
        }

        if ($this->orderBy) {
            $orderBy = $this->orderBy;
            $order = $orderBy[0];
            $query .= sprintf(" ORDER BY %s", implode(", ", $order));
        }

        if ($this->_maxResults) {
            $query .= sprintf(" LIMIT %s", $this->_maxResults);
        }
        $this->query = $query;

        return $this;
    }

    /**
     * @return string
     */
    public function _getQuery()
    {
        return $this->query;
    }

    /**
     * @param int $uniq
     * @return array|mixed|object
     * @throws QueryBuilderException
     */
    public function getResult($uniq = null)
    {
        if (!$this->query) {
            throw new QueryBuilderException("Query not created yet, call getQuery first");
        }
        $req = $this->pdo->prepare($this->query);
        $req->execute();

        $datas = $uniq == self::QUERY_UNIQUE ? $req->fetch(\PDO::FETCH_ASSOC) : $req->fetchAll(\PDO::FETCH_ASSOC);

        $returnArray = [];
        if ($this->entity && $this->repository) {
            /** @var MainRepository $repo */
            $repo = new $this->repository();
            if (!$uniq) {
                foreach ($datas as $item) {
                    $object = new $this->entity();
                    $data = !$item ? $item : $repo::hydrate($object, $item);
                    $returnArray[] = $data;
                }
            }else {
                $object = new $this->entity();
                $data = !$datas ? $datas : $repo::hydrate($object, $datas);
                $returnArray = $data;
            }
        }

        return $this->entity ? $returnArray : $datas;
    }

    /**
     * @param $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @param MainRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @param $query
     * @return QueryBuilder
     */
    public function setCustomQuery($query)
    {
        $this->query = $query;

        return $this;
    }
}
