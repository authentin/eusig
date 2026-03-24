<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit;

use Authentin\Eusig\Contract\SigningClientInterface;
use Authentin\Eusig\Contract\TokenInterface;
use Authentin\Eusig\Exception\SigningFailedException;
use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\SignedDocument;
use Authentin\Eusig\Model\ToBeSigned;
use Authentin\Eusig\Signer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignerTest extends TestCase
{
    #[Test]
    public function it_signs_a_document_using_the_full_dss_flow(): void
    {
        $document = new Document('pdf-content', 'test.pdf');
        $parameters = new SignatureParameters(SignatureLevel::PAdES_BASELINE_B);
        $certificate = new Certificate('base64cert');
        $toBeSigned = new ToBeSigned('digest-bytes');
        $signatureValue = new SignatureValue('RSA', 'sig-bytes');
        $signedDocument = new SignedDocument('signed-bytes', 'test-signed.pdf');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getCertificate')->willReturn($certificate);
        $token->method('getCertificateChain')->willReturn([]);
        $token->method('sign')->with($toBeSigned, DigestAlgorithm::SHA256)->willReturn($signatureValue);

        $client = $this->createMock(SigningClientInterface::class);
        $client->method('getDataToSign')->willReturn($toBeSigned);
        $client->method('signDocument')->willReturn($signedDocument);

        $signer = new Signer($client, $token);
        $result = $signer->sign($document, $parameters);

        self::assertSame($signedDocument, $result);
    }

    #[Test]
    public function it_wraps_unexpected_exceptions_in_signing_failed(): void
    {
        $document = new Document('pdf-content', 'test.pdf');
        $parameters = new SignatureParameters(SignatureLevel::PAdES_BASELINE_B);
        $certificate = new Certificate('base64cert');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getCertificate')->willReturn($certificate);
        $token->method('getCertificateChain')->willReturn([]);

        $client = $this->createMock(SigningClientInterface::class);
        $client->method('getDataToSign')->willThrowException(new \RuntimeException('DSS unreachable'));

        $signer = new Signer($client, $token);

        $this->expectException(SigningFailedException::class);
        $this->expectExceptionMessage('Signing failed: DSS unreachable');

        $signer->sign($document, $parameters);
    }

    #[Test]
    public function it_rethrows_signing_failed_exception_as_is(): void
    {
        $document = new Document('pdf-content', 'test.pdf');
        $parameters = new SignatureParameters(SignatureLevel::PAdES_BASELINE_B);
        $certificate = new Certificate('base64cert');
        $original = new SigningFailedException('Invalid certificate');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getCertificate')->willReturn($certificate);
        $token->method('getCertificateChain')->willReturn([]);

        $client = $this->createMock(SigningClientInterface::class);
        $client->method('getDataToSign')->willThrowException($original);

        $signer = new Signer($client, $token);

        try {
            $signer->sign($document, $parameters);
            self::fail('Expected SigningFailedException');
        } catch (SigningFailedException $e) {
            self::assertSame($original, $e);
        }
    }
}
