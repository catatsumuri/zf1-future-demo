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

    protected function _initStorage(): Application_Service_Storage_StorageInterface
    {
        $options = $this->getOptions();
        $uploads = $options['uploads'] ?? [];
        $backend = strtolower((string) ($uploads['backend'] ?? 'local'));

        if ($backend === 's3') {
            $storage = $this->createS3Storage($uploads);
        } else {
            $path = $uploads['path'] ?? APPLICATION_PATH . '/../data/uploads';
            $storage = new Application_Service_Storage_LocalStorage($path);
        }

        Zend_Registry::set('storage', $storage);

        return $storage;
    }

    private function createS3Storage(array $uploads): Application_Service_Storage_StorageInterface
    {
        if (!class_exists('Aws\\S3\\S3Client')) {
            throw new RuntimeException('Aws\\S3\\S3Client is required for the S3 storage backend.');
        }

        $config = $uploads['s3'] ?? [];
        $bucket = (string) ($config['bucket'] ?? '');

        if ($bucket === '') {
            throw new RuntimeException('uploads.s3.bucket must be configured.');
        }

        $clientConfig = [
            'version' => $config['version'] ?? 'latest',
            'region' => $config['region'] ?? 'ap-northeast-1',
        ];

        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
        }

        if (!empty($config['profile'])) {
            $clientConfig['profile'] = $config['profile'];
        } elseif (!empty($config['credentials']['key']) && !empty($config['credentials']['secret'])) {
            $clientConfig['credentials'] = [
                'key' => $config['credentials']['key'],
                'secret' => $config['credentials']['secret'],
            ];
        }

        $client = new Aws\S3\S3Client($clientConfig);
        $prefix = (string) ($config['prefix'] ?? '');
        $acl = (string) ($config['acl'] ?? 'private');

        return new Application_Service_Storage_S3Storage($client, $bucket, $prefix, $acl);
    }
}
