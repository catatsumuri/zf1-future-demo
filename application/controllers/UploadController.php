<?php

class UploadController extends Zend_Controller_Action
{
    private Application_Service_Storage_StorageInterface $storage;
    private string $uploadLogPath;

    public function init(): void
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $bootstrap->bootstrap('storage');
        $resource = $bootstrap->getResource('storage');

        if (!$resource instanceof Application_Service_Storage_StorageInterface) {
            throw new RuntimeException('Storage service is not configured.');
        }

        $this->storage = $resource;
        $this->uploadLogPath = $this->resolveUploadLogPath();
    }

    public function indexAction(): void
    {
        if ($this->getRequest()->isPost()) {
            $errorMessage = $this->handleUpload();

            if ($errorMessage === null) {
                $this->_helper->flashMessenger(['success' => 'ファイルをアップロードしました。']);
            } else {
                $this->_helper->flashMessenger(['error' => $errorMessage]);
            }

            $this->_redirect('/upload');

            return;
        }

        try {
            $this->storage->ensureReady();
        } catch (RuntimeException $exception) {
            $this->_helper->flashMessenger(['error' => $exception->getMessage()]);
        }

        $this->view->storageDescription = $this->storage->describeLocation();
        $this->view->files = $this->storage->listFiles();
    }

    private function handleUpload(): ?string
    {
        $fileInfo = $_FILES['upload'] ?? null;

        if (!is_array($fileInfo)) {
            return 'アップロードされたファイルが見つかりません。';
        }

        $errorCode = (int) ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return 'ファイルを選択してください。';
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            return $this->translateUploadError($errorCode);
        }

        $originalName = (string) ($fileInfo['name'] ?? '');
        $safeName = $this->createSafeFilename($originalName);

        if ($safeName === '') {
            $safeName = 'upload_' . date('Ymd_His');
        }

        $temporaryPath = (string) ($fileInfo['tmp_name'] ?? '');

        if (!is_uploaded_file($temporaryPath)) {
            return 'アップロードされたファイルを確認できませんでした。';
        }

        try {
            $this->storage->ensureReady();
            $this->storage->store($temporaryPath, $safeName);
        } catch (RuntimeException $exception) {
            $this->logUploadFailure($fileInfo, $safeName, $temporaryPath, $exception->getMessage());

            return 'ファイルの保存に失敗しました。';
        }

        return null;
    }

    private function logUploadFailure(array $fileInfo, string $suggestedName, string $temporaryPath, string $reason = ''): void
    {
        $directory = dirname($this->uploadLogPath);

        if ($directory !== '' && !is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                error_log('Failed to create upload log directory: ' . $directory);

                return;
            }
        }

        $payload = [
            'timestamp' => date('c'),
            'location' => $this->storage->describeLocation(),
            'suggestedName' => $suggestedName,
            'temporaryPath' => $temporaryPath,
            'reason' => $reason,
            'error' => $fileInfo['error'] ?? null,
            'size' => $fileInfo['size'] ?? null,
            'type' => $fileInfo['type'] ?? null,
            'originalName' => $fileInfo['name'] ?? null,
        ];

        $message = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($message === false) {
            $message = sprintf(
                'Upload storage failed: tmp=%s suggested=%s error=%s reason=%s',
                $temporaryPath,
                $suggestedName,
                $fileInfo['error'] ?? 'unknown',
                $reason
            );
        }

        error_log('Upload failure: ' . $message . PHP_EOL, 3, $this->uploadLogPath);
    }

    private function createSafeFilename(string $original): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
        $sanitized = trim((string) $sanitized, '._-');

        if ($sanitized === '') {
            return '';
        }

        return substr($sanitized, 0, 200);
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

    private function translateUploadError(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'ファイルサイズが大きすぎます。';
            case UPLOAD_ERR_PARTIAL:
                return 'ファイルが途中で中断されました。';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '一時ディレクトリがありません。';
            case UPLOAD_ERR_CANT_WRITE:
                return 'ディスクへの書き込みに失敗しました。';
            case UPLOAD_ERR_EXTENSION:
                return '拡張機能によってアップロードが中止されました。';
            default:
                return 'ファイルのアップロードに失敗しました。';
        }
    }
}
