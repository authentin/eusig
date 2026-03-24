<?php

declare(strict_types=1);

namespace Authentin\Eusig\Dss;

use Authentin\Eusig\Model\Document;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class DssClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
    ) {}

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws DssException
     */
    public function post(string $endpoint, array $payload): array
    {
        $json = \json_encode($payload, \JSON_THROW_ON_ERROR);

        $request = $this->requestFactory
            ->createRequest('POST', \rtrim($this->baseUrl, '/') . '/' . \ltrim($endpoint, '/'))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream($json));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            throw new DssException('DSS request failed: ' . $e->getMessage(), previous: $e);
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new DssException(\sprintf(
                'DSS returned HTTP %d: %s',
                $statusCode,
                $body,
            ));
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DssException('DSS returned invalid JSON: ' . $e->getMessage(), previous: $e);
        }

        return $decoded;
    }

    /**
     * @internal
     *
     * @return array{bytes: string, name: string}
     */
    public static function documentToPayload(Document $document): array
    {
        return [
            'bytes' => \base64_encode($document->content),
            'name' => $document->filename,
        ];
    }
}
