<?php

class TestController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $connectionParams = $this->buildConnectionParams();
        $environment = getenv();

        if (!is_array($environment)) {
            $environment = [];
        }

        $this->view->connectionParams = $connectionParams;
        $this->view->environmentVariables = $environment;

        $adapter = Zend_Db_Table::getDefaultAdapter();

        if (!$adapter instanceof Zend_Db_Adapter_Abstract) {
            $this->view->isConnected = false;
            $this->view->message = 'Database adapter is not configured. Check the DB_* environment variables.';

            return;
        }

        try {
            $version = $adapter->fetchOne('SELECT VERSION()');
            $this->view->isConnected = true;
            $this->view->message = 'Successfully connected to MySQL.';
            $this->view->version = $version;
        } catch (Zend_Db_Exception $exception) {
            $this->view->isConnected = false;
            $this->view->message = 'Database connection failed.';
            $this->view->error = $exception->getMessage();
        }
    }

    private function buildConnectionParams(): array
    {
        return [
            'adapter' => getenv('DB_ADAPTER') ?: 'pdo_mysql',
            'host' => getenv('DB_HOST') ?: 'mysql',
            'port' => getenv('DB_PORT') ?: '3306',
            'dbname' => getenv('DB_NAME') ?: 'app',
            'username' => getenv('DB_USER') ?: 'app',
            'password' => getenv('DB_PASSWORD') ?: 'app',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ];
    }
}
