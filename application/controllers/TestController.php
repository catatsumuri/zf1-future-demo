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
        $this->view->permissionChecks = $this->collectPermissionChecks();

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

    private function collectPermissionChecks(): array
    {
        $checks = [];

        $uploadDirectory = $this->resolveUploadDir();
        $checks[] = $this->buildDirectoryCheck('Uploads directory', $uploadDirectory, true);

        $logDirectory = dirname($this->resolveUploadLogPath());
        $checks[] = $this->buildDirectoryCheck('Upload log directory', $logDirectory, false);

        return $checks;
    }

    private function buildDirectoryCheck(string $label, string $path, bool $mustExist): array
    {
        $exists = file_exists($path);
        $isDirectory = is_dir($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);

        $messages = [];

        if ($mustExist && (!$exists || !$isDirectory)) {
            $messages[] = 'Directory is missing';
        }

        if ($exists && !$isDirectory) {
            $messages[] = 'Path exists but is not a directory';
        }

        if ($exists && $isDirectory && !$writable) {
            $messages[] = 'Directory is not writable';
        }

        if (!$exists) {
            $parentWritable = $this->isParentWritable($path);

            if ($parentWritable === true) {
                $messages[] = 'Parent directory is writable (directory can be created)';
            } elseif ($parentWritable === false) {
                $messages[] = 'Parent directory is not writable';
            }
        }

        return [
            'label' => $label,
            'path' => $path,
            'exists' => $exists,
            'isDirectory' => $isDirectory,
            'readable' => $readable,
            'writable' => $writable,
            'notes' => $messages,
        ];
    }

    private function isParentWritable(string $path): ?bool
    {
        $parent = dirname($path);

        if ($parent === '' || $parent === '.') {
            return null;
        }

        if (!is_dir($parent)) {
            return null;
        }

        return is_writable($parent);
    }

    private function resolveUploadDir(): string
    {
        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $configured = $options['uploads']['path'] ?? null;
        $path = is_string($configured) && $configured !== ''
            ? $configured
            : APPLICATION_PATH . '/../data/uploads';

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    private function resolveUploadLogPath(): string
    {
        $options = $this->getInvokeArg('bootstrap')->getOptions();
        $configured = $options['uploads']['logPath'] ?? null;

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return APPLICATION_PATH . '/../data/logs/upload_failures.log';
    }
}
