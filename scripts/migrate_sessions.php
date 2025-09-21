#!/usr/bin/env php
<?php
declare(strict_types=1);

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

$application->bootstrap(['autoload', 'db']);

$db = Zend_Db_Table::getDefaultAdapter();

if (!$db instanceof Zend_Db_Adapter_Abstract) {
    fwrite(STDERR, "Database adapter not available.\n");
    exit(1);
}

try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'sessions'");

    if ($tableExists === false) {
        $db->query(<<<'SQL'
CREATE TABLE `sessions` (
    `session_id` VARCHAR(128) NOT NULL,
    `modified` INT UNSIGNED NOT NULL,
    `lifetime` INT UNSIGNED NOT NULL,
    `data` LONGBLOB NOT NULL,
    PRIMARY KEY (`session_id`),
    KEY `idx_sessions_modified` (`modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );

        echo "Created sessions table.\n";
    } else {
        echo "Sessions table already exists.\n";
    }

    echo "Migration complete.\n";
} catch (Zend_Db_Exception $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
