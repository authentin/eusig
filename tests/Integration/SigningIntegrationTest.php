<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Integration;

use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssSigningClient;
use Authentin\Eusig\Dss\DssValidator;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Signer;
use Authentin\Eusig\Token\Pkcs12Token;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

#[Group('integration')]
final class SigningIntegrationTest extends TestCase
{
    private static string $pkcs12Content = '';

    private static string $dssBaseUrl = '';

    public static function setUpBeforeClass(): void
    {
        self::$dssBaseUrl = \getenv('DSS_BASE_URL') ?: 'http://localhost:8080/services/rest';

        // Generate a test PKCS#12 keystore with a self-signed certificate
        $keyPair = \openssl_pkey_new([
            'private_key_type' => \OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);

        self::assertNotFalse($keyPair);

        $csr = \openssl_csr_new([
            'CN' => 'eusig Integration Test',
            'O' => 'Authentin',
            'C' => 'EU',
        ], $keyPair);

        self::assertNotFalse($csr);

        $cert = \openssl_csr_sign($csr, null, $keyPair, 365);
        self::assertNotFalse($cert);

        \openssl_pkcs12_export($cert, self::$pkcs12Content, $keyPair, 'integration-test');
    }

    #[Test]
    public function it_signs_a_pdf_end_to_end(): void
    {
        $this->assertDssIsReachable();

        $signer = $this->createSigner();

        // Minimal valid PDF
        $pdf = $this->createMinimalPdf();
        $document = new Document($pdf, 'test-document.pdf');
        $params = new SignatureParameters(
            signatureLevel: SignatureLevel::PAdES_BASELINE_B,
            digestAlgorithm: DigestAlgorithm::SHA256,
        );

        $signed = $signer->sign($document, $params);

        self::assertNotEmpty($signed->content);
        self::assertNotSame($pdf, $signed->content, 'Signed document should differ from original');
        self::assertStringStartsWith('%PDF', $signed->content, 'Signed document should be a valid PDF');
    }

    #[Test]
    public function it_signs_and_validates_a_pdf(): void
    {
        $this->assertDssIsReachable();

        $signer = $this->createSigner();
        $validator = $this->createValidator();

        $document = new Document($this->createMinimalPdf(), 'invoice.pdf');
        $params = new SignatureParameters(
            signatureLevel: SignatureLevel::PAdES_BASELINE_B,
        );

        $signed = $signer->sign($document, $params);

        $result = $validator->validateSignature($signed->toDocument());

        // DSS sees the embedded signature and identifies our signer.
        // Full TOTAL_PASSED requires a certificate trusted by the EU TSL,
        // which is not possible with a self-signed test certificate.
        self::assertSame(1, $result->signaturesCount);
        self::assertCount(1, $result->signatures);
        self::assertSame('eusig Integration Test', $result->signatures[0]->signedBy);
    }

    private function assertDssIsReachable(): void
    {
        try {
            $factory = new Psr17Factory();
            $client = $this->createHttpClient();
            $request = $factory->createRequest('GET', self::$dssBaseUrl . '/server-signing/keys');
            $response = $client->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                self::markTestSkipped('DSS is not reachable at ' . self::$dssBaseUrl);
            }
        } catch (\Throwable) {
            self::markTestSkipped('DSS is not reachable at ' . self::$dssBaseUrl);
        }
    }

    private function createSigner(): Signer
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'integration-test');
        $signingClient = new DssSigningClient($this->createDssClient());

        return new Signer($signingClient, $token);
    }

    private function createValidator(): DssValidator
    {
        return new DssValidator($this->createDssClient());
    }

    private function createDssClient(): DssClient
    {
        $factory = new Psr17Factory();

        return new DssClient($this->createHttpClient(), $factory, $factory, self::$dssBaseUrl);
    }

    private function createHttpClient(): ClientInterface
    {
        return new \Symfony\Component\HttpClient\Psr18Client();
    }

    private function createMinimalPdf(): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
    }
}
