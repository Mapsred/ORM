<?php
/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 16/05/2016
 * Time: 13:27
 */

namespace Maps_red\ORM\Generator;

use Maps_red\ORM\Abstracts\DataBase;
use Maps_red\ORM\Kernel;

class Generator
{
    private $date;
    private $time;
    private $tableName;
    private $data;

    private $namespaceEntity;
    private $namespaceRepository;
    private $dir_entity;
    private $dir_repository;

    /**
     * Generator constructor.
     * @param $tableName
     */
    public function __construct($tableName)
    {
        $this->namespaceEntity = Kernel::getNamespaceEntity();
        $this->namespaceRepository = Kernel::getNamespaceRepository();
        $this->dir_entity = Kernel::getDirEntity();
        $this->dir_repository = Kernel::getDirRepository();
        $this->date = date("j/m/y");
        $this->time = date("H:i");
        $this->tableName = $tableName;
        $request = "SHOW COLUMNS FROM $tableName";
        $data = DataBase::generatePdo()->prepare($request);
        $data->execute();
        $this->data = $data->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $class
     */
    public function entity($class)
    {
        $className = ucfirst($class);
        $filename = $className.'.php';
        $contentArray = [];
        $fileContent = $this->buildComment();
        $fileContent .= "namespace $this->namespaceEntity;\n\n";
        $fileContent .= "use Maps_red\\ORM\\Abstracts\\MainEntity;\n";
        $fileContent .= "\nclass $className extends MainEntity\n";
        $fileContent .= "{\n";

        foreach ($this->data as $item) {
            $type = explode("(", $item['Type'])[0];
            $field = Kernel::dashesToCamelCase($item['Field']);
            $default = $item['Default'];
            $default = is_numeric($default) ? $default : "'$default'";
            $type = $type == "int" ? "integer" : $type;
            $type = $type == "varchar" ? "string" : $type;
            $type = $type == "date" || $type == "datetime" ? "\\DateTime" : $type;
            $fileContent .= "\t/** @var $type $$field */\n";
            $fileContent .= $item['Default'] ? "\tprivate $$field = $default;\n" : "\tprivate $$field;\n";

            $contentArray[] = ['type' => $type,'field' => $field];
        }

        $fileContent .= "\n";

        foreach ($contentArray as $item) {
            $type = $item['type'] == "integer" ? "int" : $item['type'];
            $field = $item['field'];
            $getter = "get".ucfirst($field);
            $setter = "set".ucfirst($field);

            $fileContent .= "\t/**\n";
            $fileContent .= "\t * @return $type\n";
            $fileContent .= "\t */\n";
            $fileContent .= "\t public function $getter()\n";
            $fileContent .= "\t{\n";
            $fileContent .= "\t\treturn ".'$this->'."$field;\n";
            $fileContent .= "\t}\n\n";

            $fileContent .= "\t/**\n";
            $fileContent .= "\t * @param $type $$field\n";
            $fileContent .= "\t * @return $className\n";
            $fileContent .= "\t */\n";
            $fileContent .= "\t public function $setter($$field)\n";
            $fileContent .= "\t{\n";
            $fileContent .= "\t\t".'$this->'."$field = $$field;\n";
            $fileContent .= "\n";
            $fileContent .= "\t\treturn ".'$this;'."\n";
            $fileContent .= "\t}\n\n";
        }

        $fileContent .= "\t/**\n";
        $fileContent .= "\t * @param bool ".'$set'."\n";
        $fileContent .= "\t * @return array\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\t public function getFields(".'$set = false'.")\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\treturn self::_getFields(__CLASS__, ".'$set'.");\n";
        $fileContent .= "\t}\n";

        $fileContent .= "}";
        $file = $this->dir_entity.$filename;

        if (is_file($file)) {
            rename($file, $file.".old");
        }
        if (!is_dir($this->dir_entity)) {
            mkdir($this->dir_entity);
        }
        file_put_contents($file, $fileContent);
    }

    /**
     * @param $class
     */
    public function repository($class)
    {
        $entity = ucfirst($class);
        $varEntity = strtolower($entity);
        $className = $entity."Repository";
        $filename = $className.'.php';

        $fileContent = $this->buildComment();
        $fileContent .= "namespace $this->namespaceRepository;\n\n";
        $fileContent .= "use $this->namespaceEntity\\$entity;\n";
        $fileContent .= "use Maps_red\\ORM\\Abstracts\\MainRepository;\n";
        $fileContent .= "\nclass $className extends MainRepository\n";
        $fileContent .= "{\n";
        $fileContent .= "\t/**\n";
        $fileContent .= "\t * $className constructor.\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\tpublic function __construct()\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\t".'$database = "'.$this->tableName.'";'."\n";
        $entityNamespace = str_replace("\\", "\\\\", $this->namespaceEntity);
        $fileContent .= "\t\tparent::__construct(".'$database,'." \"$entityNamespace\\\\$entity\");\n";
        $fileContent .= "\t}\n\n";
        $fileContent .= "\t/**\n";
        $fileContent .= "\t * @return $entity|null.\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\tpublic function findOne()\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\treturn parent::findOne();\n";
        $fileContent .= "\t}\n\n";
        $fileContent .= "\t/**\n";
        $fileContent .= "\t * @param ".'$id'."\n";
        $fileContent .= "\t * @return $entity|null\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\tpublic function findOneById(".'$id'.")\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\treturn parent::findOneById(".'$id'.");\n";
        $fileContent .= "\t}\n\n";
        $fileContent .= "\t/**\n";
        $fileContent .= "\t * @param array ".'$array'."\n";
        $fileContent .= "\t * @param array ".'$order'."\n";
        $fileContent .= "\t * @return $entity|null\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\tpublic function findOneBy(".'array $array, array $order = null'.")\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\treturn parent::findOneBy(".'$array, $order'.");\n";
        $fileContent .= "\t}\n\n";
        $fileContent .= "\t/**\n";
        $fileContent .= "\t * @param $entity $$varEntity\n";
        $fileContent .= "\t * @return $entity|null.\n";
        $fileContent .= "\t */\n";
        $fileContent .= "\tpublic function save($$varEntity)\n";
        $fileContent .= "\t{\n";
        $fileContent .= "\t\treturn parent::save($$varEntity);\n";
        $fileContent .= "\t}\n";
        $fileContent .= "}";

        $file = $this->dir_repository.$filename;
        if (is_file($file)) {
            rename($file, $file.".old");
        }
        if (!is_dir($this->dir_repository)) {
            mkdir($this->dir_repository);
        }
        file_put_contents($file, $fileContent);
    }

    /**
     * @return string
     */
    public function buildComment()
    {
        $fileContent = "<?php \n\n";
        $fileContent .= "/**\n";
        $fileContent .= " * Created by PhpStorm\n";
        $fileContent .= " * User: Maps_red\n";
        $fileContent .= " * Date: $this->date\n";
        $fileContent .= " * Time: $this->time\n";
        $fileContent .= " */\n\n";

        return $fileContent;
    }
}