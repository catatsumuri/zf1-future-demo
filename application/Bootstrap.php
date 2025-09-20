<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload(): Zend_Application_Module_Autoloader
    {
        return new Zend_Application_Module_Autoloader([
            'namespace' => 'Application',
            'basePath' => APPLICATION_PATH,
        ]);
    }

    protected function _initDb(): ?Zend_Db_Adapter_Abstract
    {
        $adapterName = getenv('DB_ADAPTER') ?: 'pdo_mysql';

        $params = [
            'host' => getenv('DB_HOST') ?: 'mysql',
            'username' => getenv('DB_USER') ?: 'app',
            'password' => getenv('DB_PASSWORD') ?: 'app',
            'dbname' => getenv('DB_NAME') ?: 'app',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ];

        if (!$params['username'] || !$params['dbname']) {
            return null;
        }

        try {
            $db = Zend_Db::factory($adapterName, $params);
            Zend_Db_Table::setDefaultAdapter($db);
            Zend_Registry::set('db', $db);

            return $db;
        } catch (Zend_Db_Exception $exception) {
            error_log('Database bootstrap failed: ' . $exception->getMessage());

            return null;
        }
    }

    protected function _initSession(): void
    {
        if (!Zend_Session::isStarted()) {
            Zend_Session::start();
        }
    }
}
