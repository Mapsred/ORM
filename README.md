#ORM

Installation
------------

1) Use [Composer](https://getcomposer.org/) to download the library

```
php composer.phar require maps_red/orm dev-master
```

2) Then create a file to initiate the ORM config


```php
// config.php
// You need to give the following parameters :
//db_name, db_host, db_user, db_pass, namespace_entity, namespace_repository, 
//dir_entity, dir_repository

require_once(__DIR__."/../vendor/autoload.php");

//Manually 

\Maps_red\Kernel::setDbName($db_name)->setDbHost($db_host)->setDbuser($db_user)
    ->setDbPass($db_pass)->setNamespaceEntity($namespace_entity)
    ->setNamespaceRepository($namespace_repository)
    ->setDirEntity($dir_entity)->setDirRepository($dir_repository);

//From config array

\Maps_red\Kernel::setByArray($parameters);
```

3) Extend the console
```php
//console.php
require_once("config.php");
require_once(__DIR__."/../vendor/maps_red/orm/console");
```


4) Generate your content
```
php console.php generator:entity table_name
php console.php generator:repository table_name
or
php console.php generator:both table_name
```
