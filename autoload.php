<?php
/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 05/08/2016
 * Time: 18:38
 */
require_once(__DIR__."/vendor/autoload.php");

$configPath = __DIR__."/../app/config.php";
//For test purpose - to remove
if (is_file(__DIR__."/ConfigTest.php")) {
    $content = require_once(__DIR__."/ConfigTest.php");
}else if (!is_file($configPath)) {
    throw new Exception(sprintf("file %s does not exists", $configPath));
} else {
    $parameters = require_once($configPath);
}

\ORM\Kernel::setByArray($content);


$entityNamespace = str_replace("\\", "\\\\", \ORM\Kernel::getNamespaceEntity());