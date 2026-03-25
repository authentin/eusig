<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Model;

use Authentin\Eusig\Exception\InvalidDocumentException;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\Document;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class DocumentTest extends TestCase
{
    #[Test]
    public function it_creates_a_document(): void
    {
        $doc = new Document('PDF content', 'test.pdf');

        self::assertSame('PDF content', $doc->content);
        self::assertSame('test.pdf', $doc->filename);
    }

    #[Test]
    public function it_computes_hash(): void
    {
        $doc = new Document('PDF content', 'test.pdf');

        self::assertSame(\hash('sha256', 'PDF content'), $doc->hash());
        self::assertSame(\hash('sha512', 'PDF content'), $doc->hash(DigestAlgorithm::SHA512));
    }

    #[Test]
    public function it_rejects_empty_content(): void
    {
        $this->expectException(InvalidDocumentException::class);

        new Document('', 'test.pdf');
    }

    #[Test]
    public function it_rejects_empty_filename(): void
    {
        $this->expectException(InvalidDocumentException::class);

        new Document('content', '');
    }

    #[Test]
    public function it_creates_from_base64(): void
    {
        $content = 'Hello World';
        $base64 = \base64_encode($content);

        $doc = Document::fromBase64($base64, 'test.txt');

        self::assertSame($content, $doc->content);
        self::assertSame('test.txt', $doc->filename);
    }

    #[Test]
    public function it_rejects_invalid_base64(): void
    {
        $this->expectException(InvalidDocumentException::class);

        Document::fromBase64('not-valid-base64!!!', 'test.txt');
    }

    #[Test]
    public function it_creates_from_local_file(): void
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'eusig_test_');
        self::assertNotFalse($tmpFile);
        \file_put_contents($tmpFile, 'file content');

        try {
            $doc = Document::fromLocalFile($tmpFile);

            self::assertSame('file content', $doc->content);
            self::assertSame(\basename($tmpFile), $doc->filename);
        } finally {
            \unlink($tmpFile);
        }
    }

    #[Test]
    public function it_rejects_nonexistent_file(): void
    {
        $this->expectException(InvalidDocumentException::class);

        Document::fromLocalFile('/nonexistent/path/file.pdf');
    }

    #[Test]
    public function it_creates_from_stream(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('stream content');

        $doc = Document::fromStream($stream, 'remote.pdf');

        self::assertSame('stream content', $doc->content);
        self::assertSame('remote.pdf', $doc->filename);
    }

    #[Test]
    public function it_rejects_empty_stream(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('');

        $this->expectException(InvalidDocumentException::class);

        Document::fromStream($stream, 'empty.pdf');
    }

    #[Test]
    public function it_creates_from_resource(): void
    {
        $resource = \fopen('php://memory', 'r+');
        self::assertIsResource($resource);
        \fwrite($resource, 'resource content');
        \rewind($resource);

        $doc = Document::fromResource($resource, 'uploaded.pdf');

        self::assertSame('resource content', $doc->content);
        self::assertSame('uploaded.pdf', $doc->filename);

        \fclose($resource);
    }
}
