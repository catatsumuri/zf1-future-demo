<?php

class Application_Service_Storage_LocalStorage implements Application_Service_Storage_StorageInterface
{
    private string $baseDirectory;

    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR);
    }

    public function ensureReady(): void
    {
        if (is_dir($this->baseDirectory)) {
            return;
        }

        if (!mkdir($this->baseDirectory, 0755, true) && !is_dir($this->baseDirectory)) {
            throw new RuntimeException('Failed to create upload directory: ' . $this->baseDirectory);
        }
    }

    public function store(string $temporaryPath, string $suggestedName): string
    {
        $this->ensureReady();

        $finalName = $this->resolveUniqueName($suggestedName);
        $targetPath = $this->baseDirectory . DIRECTORY_SEPARATOR . $finalName;

        if (!move_uploaded_file($temporaryPath, $targetPath)) {
            if (!rename($temporaryPath, $targetPath)) {
                throw new RuntimeException('Failed to move uploaded file to ' . $targetPath);
            }
        }

        @chmod($targetPath, 0644);

        return $finalName;
    }

    public function listFiles(): array
    {
        if (!is_dir($this->baseDirectory)) {
            return [];
        }

        $files = [];
        $handle = opendir($this->baseDirectory);

        if ($handle === false) {
            return [];
        }

        try {
            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $this->baseDirectory . DIRECTORY_SEPARATOR . $entry;

                if (!is_file($path)) {
                    continue;
                }

                $stat = stat($path);

                if ($stat === false) {
                    continue;
                }

                $files[] = [
                    'name' => $entry,
                    'size' => (int) $stat['size'],
                    'mtime' => (int) $stat['mtime'],
                ];
            }
        } finally {
            closedir($handle);
        }

        usort($files, static function (array $a, array $b): int {
            return $b['mtime'] <=> $a['mtime'];
        });

        return $files;
    }

    public function describeLocation(): string
    {
        return $this->baseDirectory;
    }

    private function resolveUniqueName(string $suggestedName): string
    {
        $name = $suggestedName;
        $targetPath = $this->baseDirectory . DIRECTORY_SEPARATOR . $name;

        if (!file_exists($targetPath)) {
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
            $targetPath = $this->baseDirectory . DIRECTORY_SEPARATOR . $candidate;
            $counter++;
        } while (file_exists($targetPath));

        return $candidate;
    }
}
