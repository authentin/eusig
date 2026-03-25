<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Dss;

use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssValidator;
use Authentin\Eusig\Model\Document;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class DssValidatorTest extends TestCase
{
    #[Test]
    public function it_validates_a_signed_document(): void
    {
        $validator = $this->createValidator(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/validation/validateSignature', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame(\base64_encode('signed-pdf'), $body['signedDocument']['bytes']);
            self::assertSame('NONE', $body['tokenExtractionStrategy']);

            return new Response(200, [], \json_encode([
                'SimpleReport' => [
                    'SignaturesCount' => 1,
                    'ValidSignaturesCount' => 1,
                    'signatureOrTimestampOrEvidenceRecord' => [
                        [
                            'Signature' => [
                                'Indication' => 'TOTAL_PASSED',
                                'SubIndication' => null,
                                'SignatureLevel' => ['value' => 'PAdES_BASELINE_B'],
                                'SignedBy' => 'CN=Test Signer',
                                'SigningTime' => '2026-03-24T18:00:00Z',
                            ],
                        ],
                    ],
                ],
            ]));
        });

        $result = $validator->validateSignature(new Document('signed-pdf', 'signed.pdf'));

        self::assertTrue($result->valid);
        self::assertSame(1, $result->signaturesCount);
        self::assertSame(1, $result->validSignaturesCount);
        self::assertCount(1, $result->signatures);
        self::assertTrue($result->signatures[0]->isValid());
        self::assertSame('TOTAL_PASSED', $result->signatures[0]->indication);
        self::assertSame('PAdES_BASELINE_B', $result->signatures[0]->signatureLevel);
        self::assertSame('CN=Test Signer', $result->signatures[0]->signedBy);
    }

    #[Test]
    public function it_reports_failed_validation(): void
    {
        $validator = $this->createValidator(fn(): Response => new Response(200, [], \json_encode([
            'SimpleReport' => [
                'SignaturesCount' => 1,
                'ValidSignaturesCount' => 0,
                'signatureOrTimestampOrEvidenceRecord' => [
                    [
                        'Signature' => [
                            'Indication' => 'TOTAL_FAILED',
                            'SubIndication' => 'SIG_CRYPTO_FAILURE',
                        ],
                    ],
                ],
            ],
        ])));

        $result = $validator->validateSignature(new Document('bad-pdf', 'bad.pdf'));

        self::assertFalse($result->valid);
        self::assertSame(0, $result->validSignaturesCount);
        self::assertFalse($result->signatures[0]->isValid());
        self::assertSame('SIG_CRYPTO_FAILURE', $result->signatures[0]->subIndication);
    }

    #[Test]
    public function it_passes_original_document_for_detached_signatures(): void
    {
        $validator = $this->createValidator(function (RequestInterface $request): Response {
            $body = \json_decode((string) $request->getBody(), true);
            self::assertArrayHasKey('originalDocuments', $body);
            self::assertCount(1, $body['originalDocuments']);

            return new Response(200, [], \json_encode([
                'SimpleReport' => [
                    'SignaturesCount' => 1,
                    'ValidSignaturesCount' => 1,
                    'signatureOrTimestampOrEvidenceRecord' => [
                        [
                            'Signature' => [
                                'Indication' => 'TOTAL_PASSED',
                            ],
                        ],
                    ],
                ],
            ]));
        });

        $result = $validator->validateSignature(
            new Document('signed-xml', 'signed.xml'),
            new Document('original-xml', 'original.xml'),
        );

        self::assertTrue($result->valid);
    }

    #[Test]
    public function it_gets_original_documents(): void
    {
        $validator = $this->createValidator(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/validation/getOriginalDocuments', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame('sig-id-123', $body['signatureId']);

            return new Response(200, [], \json_encode([
                [
                    'bytes' => \base64_encode('original content'),
                    'name' => 'document.txt',
                ],
            ]));
        });

        $documents = $validator->getOriginalDocuments(
            new Document('signed-container', 'container.asice'),
            'sig-id-123',
        );

        self::assertCount(1, $documents);
        self::assertSame('original content', $documents[0]->content);
        self::assertSame('document.txt', $documents[0]->filename);
    }

    /**
     * @param callable(RequestInterface): Response $handler
     */
    private function createValidator(callable $handler): DssValidator
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback($handler);

        $dssClient = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');

        return new DssValidator($dssClient);
    }
}
