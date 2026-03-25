<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Dss;

use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssSigningClient;
use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\ContainerType;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\TimestampParameters;
use Authentin\Eusig\Model\ToBeSigned;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class DssSigningClientTest extends TestCase
{
    #[Test]
    public function it_calls_get_data_to_sign(): void
    {
        $client = $this->createSigningClient(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/signature/one-document/getDataToSign', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame('PAdES_BASELINE_B', $body['parameters']['signatureLevel']);
            self::assertSame('SHA256', $body['parameters']['digestAlgorithm']);
            self::assertSame(\base64_encode('pdf-bytes'), $body['toSignDocument']['bytes']);
            self::assertSame('test.pdf', $body['toSignDocument']['name']);

            return new Response(200, [], \json_encode(['bytes' => 'dG9CZVNpZ25lZA==']));
        });

        $document = new Document('pdf-bytes', 'test.pdf');
        $params = new SignatureParameters(
            signatureLevel: SignatureLevel::PAdES_BASELINE_B,
            signingCertificate: new Certificate('Y2VydA=='),
        );

        $result = $client->getDataToSign($document, $params);

        self::assertInstanceOf(ToBeSigned::class, $result);
        self::assertSame('toBeSigned', $result->bytes);
    }

    #[Test]
    public function it_calls_sign_document(): void
    {
        $client = $this->createSigningClient(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/signature/one-document/signDocument', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame('RSA_SHA256', $body['signatureValue']['algorithm']);
            self::assertSame('c2lnbmF0dXJl', $body['signatureValue']['value']);

            return new Response(200, [], \json_encode([
                'bytes' => \base64_encode('signed-pdf-bytes'),
                'name' => 'test-signed.pdf',
            ]));
        });

        $document = new Document('pdf-bytes', 'test.pdf');
        $params = new SignatureParameters(
            signatureLevel: SignatureLevel::PAdES_BASELINE_B,
            signingCertificate: new Certificate('Y2VydA=='),
        );

        $result = $client->signDocument($document, $params, new SignatureValue('RSA_SHA256', 'signature'));

        self::assertSame('signed-pdf-bytes', $result->content);
        self::assertSame('test-signed.pdf', $result->filename);
    }

    #[Test]
    public function it_calls_extend_document(): void
    {
        $client = $this->createSigningClient(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/signature/one-document/extendDocument', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame('PAdES_BASELINE_LT', $body['parameters']['signatureLevel']);
            self::assertArrayHasKey('toExtendDocument', $body);

            return new Response(200, [], \json_encode([
                'bytes' => \base64_encode('extended-bytes'),
                'name' => 'signed-extended.pdf',
            ]));
        });

        $document = new Document('signed-bytes', 'signed.pdf');
        $params = new SignatureParameters(signatureLevel: SignatureLevel::PAdES_BASELINE_LT);

        $result = $client->extendDocument($document, $params);

        self::assertSame('extended-bytes', $result->content);
    }

    #[Test]
    public function it_calls_timestamp_document(): void
    {
        $client = $this->createSigningClient(function (RequestInterface $request): Response {
            self::assertStringEndsWith('/signature/one-document/timestampDocument', (string) $request->getUri());

            $body = \json_decode((string) $request->getBody(), true);
            self::assertSame('SHA256', $body['timestampParameters']['digestAlgorithm']);
            self::assertSame('ASiC_E', $body['timestampParameters']['timestampContainerForm']);

            return new Response(200, [], \json_encode([
                'bytes' => \base64_encode('timestamped-bytes'),
                'name' => 'test-timestamped.pdf',
            ]));
        });

        $document = new Document('pdf-bytes', 'test.pdf');
        $params = new TimestampParameters(
            digestAlgorithm: DigestAlgorithm::SHA256,
            containerType: ContainerType::ASiC_E,
        );

        $result = $client->timestampDocument($document, $params);

        self::assertSame('timestamped-bytes', $result->content);
    }

    /**
     * @param callable(RequestInterface): Response $handler
     */
    private function createSigningClient(callable $handler): DssSigningClient
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback($handler);

        $dssClient = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');

        return new DssSigningClient($dssClient);
    }
}
