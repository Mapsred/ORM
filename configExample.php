<?php
/**
 * Created by PhpStorm.
 * User: Maps_red
 * Date: 05/08/2016
 * Time: 20:19
 */

$sep = DIRECTORY_SEPARATOR;

return [
    "db_name" => "orm",
    "db_host" => "127.0.0.1",
    "db_user" => "root",
    "db_pass" => "",
    "namespace_entity" => "ORM\\Entity",
    "namespace_repository" => "ORM\\Repository",
    "dir_entity" => sprintf("%s%s..%sEntity%s", __DIR__, $sep, $sep, $sep),
    "dir_repository" => sprintf("%s%s..%sRepository%s", __DIR__, $sep, $sep, $sep),
];