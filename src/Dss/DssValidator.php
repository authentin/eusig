<?php

declare(strict_types=1);

namespace Authentin\Eusig\Dss;

use Authentin\Eusig\Contract\ValidatorInterface;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureValidation;
use Authentin\Eusig\Model\ValidationResult;

final readonly class DssValidator implements ValidatorInterface
{
    public function __construct(
        private DssClient $client,
    ) {}

    public function validateSignature(Document $signedDocument, ?Document $originalDocument = null): ValidationResult
    {
        $payload = [
            'signedDocument' => DssClient::documentToPayload($signedDocument),
            'tokenExtractionStrategy' => 'NONE',
        ];

        if (null !== $originalDocument) {
            $payload['originalDocuments'] = [DssClient::documentToPayload($originalDocument)];
        }

        $response = $this->client->post('/validation/validateSignature', $payload);

        return self::mapValidationResult($response);
    }

    /**
     * @return list<Document>
     */
    public function getOriginalDocuments(Document $signedDocument, ?string $signatureId = null): array
    {
        $payload = [
            'signedDocument' => DssClient::documentToPayload($signedDocument),
            'tokenExtractionStrategy' => 'NONE',
        ];

        if (null !== $signatureId) {
            $payload['signatureId'] = $signatureId;
        }

        /** @var list<array{bytes: string, name?: string}> $response */
        $response = $this->client->post('/validation/getOriginalDocuments', $payload);

        return \array_map(static function (array $doc): Document {
            $decoded = \base64_decode($doc['bytes'], true);

            if (false === $decoded) {
                throw new DssException('Invalid base64 in getOriginalDocuments response');
            }

            return new Document(
                content: $decoded,
                filename: $doc['name'] ?? 'original-document',
            );
        }, $response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private static function mapValidationResult(array $response): ValidationResult
    {
        $simpleReport = $response['simpleReport'] ?? [];
        $signaturesCount = $simpleReport['signaturesCount'] ?? 0;
        $validSignaturesCount = $simpleReport['validSignaturesCount'] ?? 0;

        $signatures = [];
        foreach ($simpleReport['signatureOrTimestampOrEvidenceRecord'] ?? [] as $entry) {
            $signingTime = null;
            if (isset($entry['signingTime'])) {
                try {
                    $signingTime = new \DateTimeImmutable($entry['signingTime']);
                } catch (\Exception) {
                    // ignore unparseable dates
                }
            }

            $signatures[] = new SignatureValidation(
                indication: $entry['indication'] ?? 'INDETERMINATE',
                subIndication: $entry['subIndication'] ?? null,
                signatureLevel: isset($entry['signatureLevel']['value']) ? $entry['signatureLevel']['value'] : null,
                signedBy: $entry['signedBy'] ?? null,
                signingTime: $signingTime,
            );
        }

        return new ValidationResult(
            valid: $validSignaturesCount > 0 && $validSignaturesCount === $signaturesCount,
            signaturesCount: $signaturesCount,
            validSignaturesCount: $validSignaturesCount,
            signatures: $signatures,
            rawReport: $response,
        );
    }
}
