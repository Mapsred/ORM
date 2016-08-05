<?php
/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 14/05/2016
 * Time: 00:12
 */

namespace Maps_red\ORM\Abstracts;

abstract class MainEntity
{
    /**
     * @param $classname
     * @param $set
     * @return array
     */
    final protected static function _getFields($classname, $set = false)
    {
        $class = new \ReflectionClass($classname);
        $rtn = $class->getDefaultProperties();
        if ($set == true) {
            unset($rtn['id']);
        }

        return array_keys($rtn);
    }

    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @return string
     */
    public function __toString()
    {
        $fields = $this->getFields();
        $datas = [];
        foreach ($fields as $field) {
            $getter = "get".ucfirst($field);
            $data = $this->$getter();
            if (!$data) {
                continue;
            }
            $datas[] = $field." : ".$data;
        }
        $string = implode(" - ", $datas);

        return $string;
    }

    /**
     * @param bool $set
     * @return mixed
     */
    abstract public function getFields($set = false);

    public function _destruct()
    {
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $setter = "set".ucfirst($field);
            $this->$setter(null);
        }

        return true;
    }

    /**
     * @param string $name
     */
    public function _unset($name)
    {
        $setter = "set".ucfirst($name);
        $this->$setter(null);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function _call($name, $arguments)
    {
        return $this->$name(implode(",", $arguments));
    }

    /**
     * @param string $name
     */
    public function _get($name)
    {
        $getter = "set".ucfirst($name);

        return $this->$getter();
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return MainEntity
     */
    public function _set($name, $value)
    {
        $setter = "set".ucfirst($name);
        $this->$setter($value);

        return $this;
    }

    /**
     * @param bool $recursive
     * @return array
     */
    public function __toArray($recursive = false)
    {
        $fields = $this->getFields();
        $datas = [];
        foreach ($fields as $field) {
            $getter = "get".ucfirst($field);
            $data = $this->$getter();
            if (is_object($data) && $recursive) {
                /** @var MainEntity $data */
                $data = $data->__toArray($recursive);
            }
            $datas[$field] = $data;
        }

        return $datas;
    }
}