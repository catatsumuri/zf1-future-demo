<?php

interface Application_Service_Storage_StorageInterface
{
    public function ensureReady(): void;

    public function store(string $temporaryPath, string $suggestedName): string;

    /**
     * @return array<int, array{name: string, size: int, mtime: int}>
     */
    public function listFiles(): array;

    public function describeLocation(): string;
}
