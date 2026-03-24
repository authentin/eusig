<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

use Authentin\Eusig\Exception\InvalidDocumentException;
use Psr\Http\Message\StreamInterface;

final readonly class Document
{
    public function __construct(
        public string $content,
        public string $filename,
    ) {
        if ('' === $content) {
            throw new InvalidDocumentException('Document content cannot be empty.');
        }

        if ('' === $filename) {
            throw new InvalidDocumentException('Document filename cannot be empty.');
        }
    }

    public function hash(string $algo = 'sha256'): string
    {
        return \hash($algo, $this->content);
    }

    public static function fromLocalFile(string $path): self
    {
        if (!\file_exists($path)) {
            throw new InvalidDocumentException(\sprintf('File not found: %s', $path));
        }

        $content = \file_get_contents($path);

        if (false === $content) {
            throw new InvalidDocumentException(\sprintf('Unable to read file: %s', $path));
        }

        return new self(
            content: $content,
            filename: \basename($path),
        );
    }

    public static function fromBase64(string $base64, string $filename): self
    {
        $content = \base64_decode($base64, true);

        if (false === $content) {
            throw new InvalidDocumentException('Invalid base64 content.');
        }

        return new self(
            content: $content,
            filename: $filename,
        );
    }

    public static function fromStream(StreamInterface $stream, string $filename): self
    {
        $content = (string) $stream;

        if ('' === $content) {
            throw new InvalidDocumentException('Stream returned empty content.');
        }

        return new self(
            content: $content,
            filename: $filename,
        );
    }

    /**
     * @param resource $resource
     */
    public static function fromResource($resource, string $filename): self
    {
        $content = \stream_get_contents($resource);

        if (false === $content) {
            throw new InvalidDocumentException('Unable to read from resource.');
        }

        return new self(
            content: $content,
            filename: $filename,
        );
    }
}
