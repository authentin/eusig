<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Token;

use Authentin\Eusig\Exception\SigningFailedException;
use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\ToBeSigned;
use Authentin\Eusig\Token\Pkcs12Token;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class Pkcs12TokenTest extends TestCase
{
    private static string $pkcs12Content = '';

    public static function setUpBeforeClass(): void
    {
        $keyPair = \openssl_pkey_new([
            'private_key_type' => \OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);

        self::assertNotFalse($keyPair);

        $csr = \openssl_csr_new(['CN' => 'Test Signer'], $keyPair);
        self::assertNotFalse($csr);

        $cert = \openssl_csr_sign($csr, null, $keyPair, 365);
        self::assertNotFalse($cert);

        \openssl_pkcs12_export($cert, self::$pkcs12Content, $keyPair, 'test-password');
    }

    #[Test]
    public function it_loads_certificate_from_pkcs12(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');

        $cert = $token->getCertificate();

        self::assertInstanceOf(Certificate::class, $cert);
        self::assertNotEmpty($cert->encoded);

        $decoded = \base64_decode($cert->encoded, true);
        self::assertNotFalse($decoded);
    }

    #[Test]
    public function it_returns_empty_certificate_chain_for_self_signed(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');

        self::assertSame([], $token->getCertificateChain());
    }

    #[Test]
    public function it_signs_data_with_sha256(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');
        $toBeSigned = new ToBeSigned('data to sign');

        $signatureValue = $token->sign($toBeSigned, DigestAlgorithm::SHA256);

        self::assertSame('RSA_SHA256', $signatureValue->algorithm);
        self::assertNotEmpty($signatureValue->bytes);
    }

    #[Test]
    public function it_signs_data_with_sha512(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');
        $toBeSigned = new ToBeSigned('data to sign');

        $signatureValue = $token->sign($toBeSigned, DigestAlgorithm::SHA512);

        self::assertSame('RSA_SHA512', $signatureValue->algorithm);
    }

    #[Test]
    public function it_produces_verifiable_signatures(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');
        $data = 'important document content';
        $toBeSigned = new ToBeSigned($data);

        $signatureValue = $token->sign($toBeSigned, DigestAlgorithm::SHA256);

        $certs = [];
        \openssl_pkcs12_read(self::$pkcs12Content, $certs, 'test-password');
        $publicKey = \openssl_pkey_get_public($certs['cert']);

        $verified = \openssl_verify(
            $data,
            $signatureValue->bytes,
            $publicKey,
            \OPENSSL_ALGO_SHA256,
        );

        self::assertSame(1, $verified);
    }

    #[Test]
    public function it_throws_on_wrong_password(): void
    {
        $this->expectException(SigningFailedException::class);

        new Pkcs12Token(self::$pkcs12Content, 'wrong-password');
    }

    #[Test]
    public function it_throws_on_invalid_pkcs12_content(): void
    {
        $this->expectException(SigningFailedException::class);

        new Pkcs12Token('not-a-pkcs12-file', 'password');
    }

    #[Test]
    public function it_throws_on_unsupported_sha3_algorithm(): void
    {
        $token = new Pkcs12Token(self::$pkcs12Content, 'test-password');
        $toBeSigned = new ToBeSigned('data');

        $this->expectException(SigningFailedException::class);
        $this->expectExceptionMessage('not supported');

        $token->sign($toBeSigned, DigestAlgorithm::SHA3_256);
    }

    #[Test]
    public function it_loads_from_file(): void
    {
        $path = \sys_get_temp_dir() . '/eusig-test-' . \uniqid() . '.p12';
        \file_put_contents($path, self::$pkcs12Content);

        try {
            $token = Pkcs12Token::fromFile($path, 'test-password');

            self::assertInstanceOf(Certificate::class, $token->getCertificate());
        } finally {
            \unlink($path);
        }
    }

    #[Test]
    public function it_throws_on_missing_file(): void
    {
        $this->expectException(SigningFailedException::class);
        $this->expectExceptionMessage('not found');

        Pkcs12Token::fromFile('/nonexistent/path.p12', 'password');
    }
}
