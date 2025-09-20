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

if ($db === null) {
    fwrite(STDERR, "Database adapter not available.\n");
    exit(1);
}

try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'users'");

    if ($tableExists === false) {
        $db->query(<<<'SQL'
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `password_hash` CHAR(64) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_email` (`email`)
)
SQL
        );
        echo "Created users table.\n";
    }

    $seedUsers = [
        'admin@example.com' => [
            'name' => 'Administrator',
            'password_hash' => 'd8b076148c939d9d2d6eb60458969c486794a4c0fcf0632be58fa5bf6d15aafa',
        ],
        'test@example.com' => [
            'name' => 'Test User',
            'password_hash' => '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8',
        ],
    ];

    foreach ($seedUsers as $email => $definition) {
        $exists = (int) $db->fetchOne(
            'SELECT COUNT(*) FROM `users` WHERE `email` = ?',
            $email
        );

        if ($exists === 0) {
            $db->insert('users', array_merge(['email' => $email], $definition));
            printf("Inserted user %s.\n", $email);
        } else {
            printf("User %s already present.\n", $email);
        }
    }

    echo "Seeding complete.\n";
} catch (Zend_Db_Exception $exception) {
    fwrite(STDERR, 'Seeding failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
