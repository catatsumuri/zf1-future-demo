<?php

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use DateTimeInterface;

class Application_Service_Storage_S3Storage implements Application_Service_Storage_StorageInterface
{
    private S3Client $client;
    private string $bucket;
    private string $prefix;
    private string $acl;

    public function __construct(S3Client $client, string $bucket, string $prefix = '', string $acl = 'private')
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefix = $this->normalizePrefix($prefix);
        $this->acl = $acl;
    }

    public function ensureReady(): void
    {
        // Bucket lifecycle is managed outside the application; nothing to do here.
    }

    public function store(string $temporaryPath, string $suggestedName): string
    {
        $finalName = $this->resolveUniqueName($suggestedName);
        $key = $this->buildObjectKey($finalName);

        $resource = fopen($temporaryPath, 'rb');

        if ($resource === false) {
            throw new RuntimeException('Failed to open temporary file for S3 upload.');
        }

        try {
            $this->client->upload(
                $this->bucket,
                $key,
                $resource,
                $this->acl,
                [
                    'Metadata' => [
                        'submitted-name' => $suggestedName,
                        'stored-name' => $finalName,
                    ],
                ]
            );
        } catch (AwsException $exception) {
            throw new RuntimeException('Failed to upload to S3: ' . $exception->getMessage(), 0, $exception);
        } finally {
            fclose($resource);
        }

        return $finalName;
    }

    public function listFiles(): array
    {
        $files = [];
        $params = [
            'Bucket' => $this->bucket,
            'Prefix' => $this->prefix,
        ];

        do {
            $result = $this->client->listObjectsV2($params);

            foreach ((array) ($result['Contents'] ?? []) as $object) {
                $key = (string) ($object['Key'] ?? '');

                if (!$this->isWithinPrefix($key)) {
                    continue;
                }

                $name = $this->stripPrefix($key);

                if ($name === '') {
                    continue;
                }

                $lastModified = $object['LastModified'] ?? null;

                if ($lastModified instanceof DateTimeInterface) {
                    $timestamp = $lastModified->getTimestamp();
                } elseif (is_string($lastModified)) {
                    $parsed = strtotime($lastModified);
                    $timestamp = $parsed !== false ? $parsed : time();
                } else {
                    $timestamp = time();
                }

                $files[] = [
                    'name' => $name,
                    'size' => (int) ($object['Size'] ?? 0),
                    'mtime' => $timestamp,
                ];
            }

            $params['ContinuationToken'] = $result['NextContinuationToken'] ?? null;
        } while (!empty($params['ContinuationToken']));

        usort($files, static function (array $a, array $b): int {
            return $b['mtime'] <=> $a['mtime'];
        });

        return $files;
    }

    public function describeLocation(): string
    {
        $suffix = $this->prefix === '' ? '' : rtrim($this->prefix, '/');

        return $suffix === ''
            ? sprintf('s3://%s', $this->bucket)
            : sprintf('s3://%s/%s', $this->bucket, $suffix);
    }

    private function resolveUniqueName(string $suggestedName): string
    {
        $name = $suggestedName;
        $key = $this->buildObjectKey($name);

        if (!$this->objectExists($key)) {
            return $name;
        }

        $pathInfo = pathinfo($suggestedName);
        $base = (string) ($pathInfo['filename'] ?? 'file');
        $extension = isset($pathInfo['extension']) && $pathInfo['extension'] !== ''
            ? '.' . $pathInfo['extension']
            : '';
        $counter = 1;

        do {
            $candidate = sprintf('%s_%d%s', $base, $counter, $extension);
            $key = $this->buildObjectKey($candidate);
            $counter++;
        } while ($this->objectExists($key));

        return $candidate;
    }

    private function objectExists(string $key): bool
    {
        try {
            return $this->client->doesObjectExistV2($this->bucket, $key);
        } catch (AwsException $exception) {
            throw new RuntimeException('Failed to determine S3 object existence: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function normalizePrefix(string $prefix): string
    {
        $trimmed = trim($prefix);

        if ($trimmed === '') {
            return '';
        }

        $normalized = ltrim($trimmed, '/');

        if ($normalized !== '' && substr($normalized, -1) !== '/') {
            $normalized .= '/';
        }

        return $normalized;
    }

    private function buildObjectKey(string $name): string
    {
        return $this->prefix . ltrim($name, '/');
    }

    private function stripPrefix(string $key): string
    {
        if ($this->prefix === '') {
            return ltrim($key, '/');
        }

        if (strpos($key, $this->prefix) !== 0) {
            return '';
        }

        return substr($key, strlen($this->prefix));
    }

    private function isWithinPrefix(string $key): bool
    {
        if ($this->prefix === '') {
            return true;
        }

        return strpos($key, $this->prefix) === 0;
    }
}
