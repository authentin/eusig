<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Dss;

use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class DssClientTest extends TestCase
{
    #[Test]
    public function it_sends_post_request_and_returns_decoded_json(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);

        $httpClient->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request): Response {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('http://dss.local/services/rest/signature/one-document/getDataToSign', (string) $request->getUri());
                self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

                return new Response(200, ['Content-Type' => 'application/json'], '{"bytes":"dGVzdA=="}');
            });

        $client = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');
        $result = $client->post('/signature/one-document/getDataToSign', ['foo' => 'bar']);

        self::assertSame(['bytes' => 'dGVzdA=='], $result);
    }

    #[Test]
    public function it_throws_on_http_error(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);

        $httpClient->method('sendRequest')
            ->willReturn(new Response(500, [], 'Internal Server Error'));

        $client = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');

        $this->expectException(DssException::class);
        $this->expectExceptionMessage('DSS returned HTTP 500');

        $client->post('/test', []);
    }

    #[Test]
    public function it_throws_on_invalid_json_response(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);

        $httpClient->method('sendRequest')
            ->willReturn(new Response(200, [], 'not json'));

        $client = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');

        $this->expectException(DssException::class);
        $this->expectExceptionMessage('DSS returned invalid JSON');

        $client->post('/test', []);
    }

    #[Test]
    public function it_throws_on_network_error(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);

        $httpClient->method('sendRequest')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $client = new DssClient($httpClient, $factory, $factory, 'http://dss.local/services/rest');

        $this->expectException(DssException::class);
        $this->expectExceptionMessage('DSS request failed: Connection refused');

        $client->post('/test', []);
    }
}
