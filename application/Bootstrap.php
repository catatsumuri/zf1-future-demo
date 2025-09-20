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
        $options = $this->getOption('session');

        if (is_array($options)) {
            $this->configureSessionOptions($options);

            if (($options['save_handler'] ?? null) === 'db') {
                $this->configureDbSessionSaveHandler($options['db'] ?? []);
            }
        }

        if (!Zend_Session::isStarted()) {
            Zend_Session::start();
        }
    }

    /**
     * Apply high level Zend_Session::setOptions configuration.
     */
    private function configureSessionOptions(array $options): void
    {
        $sessionOptions = [];

        if (isset($options['name'])) {
            $sessionOptions['name'] = (string) $options['name'];
        }

        if (isset($options['remember_me_seconds'])) {
            $sessionOptions['remember_me_seconds'] = (int) $options['remember_me_seconds'];
        }

        if (isset($options['cookie']) && is_array($options['cookie'])) {
            $cookie = $options['cookie'];

            if (isset($cookie['domain'])) {
                $sessionOptions['cookie_domain'] = (string) $cookie['domain'];
            }

            if (isset($cookie['path'])) {
                $sessionOptions['cookie_path'] = (string) $cookie['path'];
            }

            if (isset($cookie['secure'])) {
                $sessionOptions['cookie_secure'] = (bool) $cookie['secure'];
            }

            if (isset($cookie['http_only'])) {
                $sessionOptions['cookie_httponly'] = (bool) $cookie['http_only'];
            }

            if (isset($cookie['lifetime'])) {
                $sessionOptions['cookie_lifetime'] = (int) $cookie['lifetime'];
            }
        }

        if ($sessionOptions !== []) {
            Zend_Session::setOptions($sessionOptions);
        }
    }

    /**
     * Configure Zend_Session to use a database table for shared storage.
     */
    private function configureDbSessionSaveHandler(array $options): void
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();

        if (!$dbAdapter instanceof Zend_Db_Adapter_Abstract) {
            error_log('Session handler configuration skipped: database adapter is not available.');

            return;
        }

        $tableName = isset($options['table']) ? (string) $options['table'] : 'sessions';

        $primaryOption = $options['primary'] ?? 'session_id';
        $primary = is_array($primaryOption) ? array_values($primaryOption) : (string) $primaryOption;

        $saveHandlerOptions = [
            'name' => $tableName,
            'primary' => $primary,
            'modifiedColumn' => isset($options['modified_column']) ? (string) $options['modified_column'] : 'modified',
            'dataColumn' => isset($options['data_column']) ? (string) $options['data_column'] : 'data',
            'lifetimeColumn' => isset($options['lifetime_column']) ? (string) $options['lifetime_column'] : 'lifetime',
        ];

        try {
            $dbAdapter->describeTable($tableName);
        } catch (Zend_Db_Exception $exception) {
            error_log('Session handler configuration skipped: table not available (' . $exception->getMessage() . ').');

            return;
        }

        try {
            $saveHandler = new Zend_Session_SaveHandler_DbTable($saveHandlerOptions);
            Zend_Session::setSaveHandler($saveHandler);
        } catch (Zend_Db_Exception | Zend_Session_SaveHandler_Exception $exception) {
            error_log('Failed to initialise database session save handler: ' . $exception->getMessage());
        }
    }
}
