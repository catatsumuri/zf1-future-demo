<?php
chdir(dirname(__DIR__));

define('APPLICATION_PATH', realpath(__DIR__ . '/../application'));
define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'development');

require_once __DIR__ . '/../vendor/autoload.php';

set_include_path(implode(PATH_SEPARATOR, [
    realpath(__DIR__ . '/../vendor/shardj/zf1-future/library'),
    get_include_path(),
]));

require_once 'Zend/Application.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()->run();
