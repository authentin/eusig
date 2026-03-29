# eusig

[![CI](https://github.com/authentin/authentin/actions/workflows/ci.yml/badge.svg)](https://github.com/authentin/authentin/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/authentin/eusig.svg)](https://packagist.org/packages/authentin/eusig)
[![PHP Version](https://img.shields.io/packagist/php-v/authentin/eusig.svg)](https://packagist.org/packages/authentin/eusig)
[![License](https://img.shields.io/packagist/l/authentin/eusig.svg)](https://packagist.org/packages/authentin/eusig)

PHP library for creating and validating eIDAS-compliant electronic signatures (PAdES, XAdES, CAdES, JAdES) via the [EU DSS](https://github.com/esig/dss) REST API.

## Installation

```bash
composer require authentin/eusig
```

### Prerequisites

You need a running [EU DSS](https://github.com/esig/dss) instance. The easiest way is Docker:

```bash
docker run -d -p 8080:8080 ghcr.io/authentin/dss:latest
```

> DSS requires ~2 GB of RAM and takes about 60 seconds to start. Wait for the healthcheck before making requests.

You also need a PSR-18 HTTP client and PSR-17 factories. For example:

```bash
composer require symfony/http-client nyholm/psr7
```

## Quick start

Sign a PDF in 10 lines:

```php
use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssSigningClient;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Signer;
use Authentin\Eusig\Token\Pkcs12Token;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$psr17 = new Psr17Factory();
$dssClient = new DssClient(new Psr18Client(), $psr17, $psr17, 'http://localhost:8080/services/rest');

$token = Pkcs12Token::fromFile('/path/to/keystore.p12', 'password');
$signer = new Signer(new DssSigningClient($dssClient), $token);

$signed = $signer->sign(
    Document::fromLocalFile('/path/to/document.pdf'),
    new SignatureParameters(signatureLevel: SignatureLevel::PAdES_BASELINE_B),
);

$signed->saveToFile('/path/to/signed.pdf');
```

## How it works

eusig wraps the [EU DSS REST API](https://github.com/esig/dss) two-step signing flow:

```
1. getDataToSign  →  DSS computes the digest that needs signing
2. Token::sign    →  Your key signs the digest (PKCS#12, HSM, remote provider, ...)
3. signDocument   →  DSS embeds the signature into the document
```

The `Signer` class orchestrates all three steps. For advanced control, use `SigningClientInterface` directly.

## Interfaces

| Interface | Purpose |
|-----------|---------|
| `SignerInterface` | Convenience — wraps all 3 steps into one `sign()` call |
| `SigningClientInterface` | Low-level DSS client — `getDataToSign()`, `signDocument()`, `extendDocument()`, `timestampDocument()` |
| `ValidatorInterface` | DSS validation — `validateSignature()`, `getOriginalDocuments()` |
| `TokenInterface` | Abstracts the cryptographic signing step — implement for PKCS#12, HSM, remote providers |

## Validation

```php
use Authentin\Eusig\Dss\DssClient;
use Authentin\Eusig\Dss\DssValidator;
use Authentin\Eusig\Model\Document;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$psr17 = new Psr17Factory();
$dssClient = new DssClient(new Psr18Client(), $psr17, $psr17, 'http://localhost:8080/services/rest');
$validator = new DssValidator($dssClient);

$result = $validator->validateSignature(Document::fromLocalFile('/path/to/signed.pdf'));

echo $result->valid ? 'Valid' : 'Invalid';
echo "Signatures: {$result->signaturesCount}";

foreach ($result->signatures as $sig) {
    echo "{$sig->signedBy}: {$sig->indication}";
}
```

## Extending signatures

Upgrade a signature level (e.g. B-B to B-T by adding a trusted timestamp):

```php
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignatureParameters;

$extended = $signingClient->extendDocument(
    $signed->toDocument(),
    new SignatureParameters(signatureLevel: SignatureLevel::PAdES_BASELINE_T),
);
```

## Signature levels

| Format | B (basic) | T (timestamp) | LT (long-term) | LTA (archival) |
|--------|-----------|---------------|-----------------|----------------|
| PAdES (PDF) | `PAdES_BASELINE_B` | `PAdES_BASELINE_T` | `PAdES_BASELINE_LT` | `PAdES_BASELINE_LTA` |
| XAdES (XML) | `XAdES_BASELINE_B` | `XAdES_BASELINE_T` | `XAdES_BASELINE_LT` | `XAdES_BASELINE_LTA` |
| CAdES (CMS) | `CAdES_BASELINE_B` | `CAdES_BASELINE_T` | `CAdES_BASELINE_LT` | `CAdES_BASELINE_LTA` |
| JAdES (JSON) | `JAdES_BASELINE_B` | `JAdES_BASELINE_T` | `JAdES_BASELINE_LT` | `JAdES_BASELINE_LTA` |

## Custom tokens

Implement `TokenInterface` to sign with any key source (HSM, remote provider, smart card):

```php
use Authentin\Eusig\Contract\TokenInterface;
use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\ToBeSigned;

class MyHsmToken implements TokenInterface
{
    public function sign(ToBeSigned $toBeSigned, DigestAlgorithm $digestAlgorithm): SignatureValue
    {
        // Send $toBeSigned->bytes to your HSM / signing service
        // Return the signature value
    }

    public function getCertificate(): Certificate { /* ... */ }

    public function getCertificateChain(): array { /* ... */ }
}
```

## Symfony integration

For Symfony projects, use the [eusig-bundle](https://github.com/authentin/eusig-bundle) for autowiring, configuration, and DI:

```bash
composer require authentin/eusig-bundle
```

## License

MIT
