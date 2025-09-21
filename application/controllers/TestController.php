<?php

class TestController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $connectionParams = $this->buildConnectionParams();
        $environment = getenv();

        $sessionTrace = $this->buildSessionTrace();

        if (!is_array($environment)) {
            $environment = [];
        }

        $this->view->connectionParams = $connectionParams;
        $this->view->environmentVariables = $environment;
        $this->view->sessionTrace = $sessionTrace;

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

    private function buildSessionTrace(): array
    {
        $configuredOptions = [];
        $bootstrap = $this->getInvokeArg('bootstrap');

        if ($bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            $option = $bootstrap->getOption('session');

            if (is_array($option)) {
                $configuredOptions = $option;
            }
        }

        $runtimeOptions = [];

        try {
            $runtimeOptions = Zend_Session::getOptions();
        } catch (Zend_Session_Exception $exception) {
            $runtimeOptions = ['__error' => $exception->getMessage()];
        }

        return [
            'configured' => $configuredOptions,
            'runtime' => $runtimeOptions,
            'handler' => $this->describeSessionSaveHandler(),
        ];
    }

    private function describeSessionSaveHandler(): array
    {
        try {
            $handler = Zend_Session::getSaveHandler();
        } catch (Zend_Session_Exception $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }

        if ($handler === null) {
            return ['status' => 'none'];
        }

        $description = [
            'status' => 'active',
            'class' => get_class($handler),
        ];

        if ($handler instanceof Zend_Session_SaveHandler_DbTable) {
            $description['driver'] = $this->describeDbTableHandler($handler);
        }

        return $description;
    }

    private function describeDbTableHandler(Zend_Session_SaveHandler_DbTable $handler): array
    {
        $tableName = $handler->getTableName();
        $adapter = $handler->getAdapter();
        $info = [
            'table' => $tableName,
            'primary' => $handler->info(Zend_Db_Table_Abstract::PRIMARY),
            'adapter' => get_class($adapter),
        ];

        try {
            $adapter->describeTable($tableName);
            $info['table_exists'] = true;
        } catch (Zend_Db_Exception $exception) {
            $info['table_exists'] = false;
            $info['error'] = $exception->getMessage();
        }

        return $info;
    }
}
