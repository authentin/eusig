<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class SignedDocument
{
    public function __construct(
        public string $bytes,
        public string $filename,
    ) {}

    public function saveToFile(string $path): void
    {
        $dir = \dirname($path);

        if (!\is_dir($dir)) {
            \mkdir($dir, 0o755, true);
        }

        \file_put_contents($path, $this->bytes);
    }
}
