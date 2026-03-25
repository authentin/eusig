<?php

declare(strict_types=1);

namespace Authentin\Eusig\Token;

use Authentin\Eusig\Contract\TokenInterface;
use Authentin\Eusig\Exception\SigningFailedException;
use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\ToBeSigned;

final class Pkcs12Token implements TokenInterface
{
    private \OpenSSLAsymmetricKey $privateKey;

    private Certificate $certificate;

    /** @var list<Certificate> */
    private array $certificateChain;

    public function __construct(string $pkcs12Content, #[\SensitiveParameter] string $password)
    {
        $certs = [];

        if (!\openssl_pkcs12_read($pkcs12Content, $certs, $password)) {
            throw new SigningFailedException('Failed to read PKCS#12 file: ' . \openssl_error_string());
        }

        $privateKey = \openssl_pkey_get_private($certs['pkey']);

        if (false === $privateKey) {
            throw new SigningFailedException('Failed to extract private key from PKCS#12: ' . \openssl_error_string());
        }

        $this->privateKey = $privateKey;
        $this->certificate = new Certificate(\base64_encode($this->pemToDer($certs['cert'])));

        $this->certificateChain = [];

        if (isset($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $extraCert) {
                $this->certificateChain[] = new Certificate(\base64_encode($this->pemToDer($extraCert)));
            }
        }
    }

    public static function fromFile(string $path, #[\SensitiveParameter] string $password): self
    {
        if (!\file_exists($path)) {
            throw new SigningFailedException(\sprintf('PKCS#12 file not found: %s', $path));
        }

        $content = \file_get_contents($path);

        if (false === $content) {
            throw new SigningFailedException(\sprintf('Unable to read PKCS#12 file: %s', $path));
        }

        return new self($content, $password);
    }

    public function sign(ToBeSigned $toBeSigned, DigestAlgorithm $digestAlgorithm): SignatureValue
    {
        $signature = '';
        $algorithm = self::mapDigestAlgorithm($digestAlgorithm);

        if (!\openssl_sign($toBeSigned->bytes, $signature, $this->privateKey, $algorithm)) {
            throw new SigningFailedException('OpenSSL signing failed: ' . \openssl_error_string());
        }

        return new SignatureValue(
            algorithm: self::resolveSignatureAlgorithm($this->privateKey, $digestAlgorithm),
            bytes: $signature,
        );
    }

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function getCertificateChain(): array
    {
        return $this->certificateChain;
    }

    private static function mapDigestAlgorithm(DigestAlgorithm $algorithm): int
    {
        return match ($algorithm) {
            DigestAlgorithm::SHA1 => \OPENSSL_ALGO_SHA1,
            DigestAlgorithm::SHA224 => \OPENSSL_ALGO_SHA224,
            DigestAlgorithm::SHA256 => \OPENSSL_ALGO_SHA256,
            DigestAlgorithm::SHA384 => \OPENSSL_ALGO_SHA384,
            DigestAlgorithm::SHA512 => \OPENSSL_ALGO_SHA512,
            DigestAlgorithm::SHA3_256, DigestAlgorithm::SHA3_384, DigestAlgorithm::SHA3_512 => throw new SigningFailedException(
                \sprintf('Digest algorithm %s is not supported by OpenSSL PKCS#12 signing', $algorithm->value),
            ),
        };
    }

    private static function resolveSignatureAlgorithm(\OpenSSLAsymmetricKey $key, DigestAlgorithm $digest): string
    {
        $details = \openssl_pkey_get_details($key);

        if (false === $details) {
            throw new SigningFailedException('Failed to read key details: ' . \openssl_error_string());
        }

        $keyType = match ($details['type']) {
            \OPENSSL_KEYTYPE_RSA => 'RSA',
            \OPENSSL_KEYTYPE_EC => 'ECDSA',
            \OPENSSL_KEYTYPE_DSA => 'DSA',
            default => throw new SigningFailedException(\sprintf('Unsupported key type: %d', $details['type'])),
        };

        return $keyType . '_' . $digest->value;
    }

    private function pemToDer(string $pem): string
    {
        $lines = \array_filter(
            \explode("\n", $pem),
            static fn(string $line): bool => !\str_starts_with($line, '-----'),
        );

        $decoded = \base64_decode(\implode('', $lines), true);

        if (false === $decoded) {
            throw new SigningFailedException('Failed to decode PEM certificate');
        }

        return $decoded;
    }
}
