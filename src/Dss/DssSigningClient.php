<?php

declare(strict_types=1);

namespace Authentin\Eusig\Dss;

use Authentin\Eusig\Contract\SigningClientInterface;
use Authentin\Eusig\Exception\DssException;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\SignedDocument;
use Authentin\Eusig\Model\TimestampParameters;
use Authentin\Eusig\Model\ToBeSigned;

final readonly class DssSigningClient implements SigningClientInterface
{
    public function __construct(
        private DssClient $client,
    ) {}

    public function getDataToSign(Document $document, SignatureParameters $parameters): ToBeSigned
    {
        $response = $this->client->post('/signature/one-document/getDataToSign', [
            'parameters' => $parameters->toDssParameters(),
            'toSignDocument' => DssClient::documentToPayload($document),
        ]);

        $encodedBytes = $response['bytes'] ?? throw new DssException('Missing "bytes" in getDataToSign response');

        $decoded = \base64_decode($encodedBytes, true);

        if (false === $decoded) {
            throw new DssException('Invalid base64 in getDataToSign response');
        }

        return new ToBeSigned($decoded);
    }

    public function signDocument(Document $document, SignatureParameters $parameters, SignatureValue $signatureValue): SignedDocument
    {
        $response = $this->client->post('/signature/one-document/signDocument', [
            'parameters' => $parameters->toDssParameters(),
            'signatureValue' => [
                'algorithm' => $signatureValue->algorithm,
                'value' => \base64_encode($signatureValue->bytes),
            ],
            'toSignDocument' => DssClient::documentToPayload($document),
        ]);

        return self::responseToSignedDocument($response);
    }

    public function extendDocument(Document $document, SignatureParameters $parameters): SignedDocument
    {
        $response = $this->client->post('/signature/one-document/extendDocument', [
            'parameters' => $parameters->toDssParameters(),
            'toExtendDocument' => DssClient::documentToPayload($document),
        ]);

        return self::responseToSignedDocument($response);
    }

    public function timestampDocument(Document $document, TimestampParameters $parameters): SignedDocument
    {
        $response = $this->client->post('/signature/one-document/timestampDocument', [
            'timestampParameters' => $parameters->toDssParameters(),
            'toTimestampDocument' => DssClient::documentToPayload($document),
        ]);

        return self::responseToSignedDocument($response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private static function responseToSignedDocument(array $response): SignedDocument
    {
        $bytes = $response['bytes'] ?? throw new DssException('Missing "bytes" in DSS response');
        $name = $response['name'] ?? 'signed-document';

        $decoded = \base64_decode($bytes, true);

        if (false === $decoded) {
            throw new DssException('Invalid base64 in DSS response');
        }

        return new SignedDocument(
            content: $decoded,
            filename: $name,
        );
    }
}
