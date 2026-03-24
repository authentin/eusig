<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Model;

use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\ContainerType;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\SignatureLevel;
use Authentin\Eusig\Model\SignaturePackaging;
use Authentin\Eusig\Model\SignatureParameters;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignatureParametersTest extends TestCase
{
    #[Test]
    public function it_serializes_minimal_parameters_to_dss_format(): void
    {
        $params = new SignatureParameters(signatureLevel: SignatureLevel::PAdES_BASELINE_B);

        $dss = $params->toDssParameters();

        self::assertSame('PAdES_BASELINE_B', $dss['signatureLevel']);
        self::assertSame('SHA256', $dss['digestAlgorithm']);
        self::assertSame('ENVELOPED', $dss['signaturePackaging']);
        self::assertArrayNotHasKey('signingCertificate', $dss);
        self::assertArrayNotHasKey('certificateChain', $dss);
        self::assertArrayNotHasKey('asicContainerType', $dss);
    }

    #[Test]
    public function it_serializes_full_parameters_to_dss_format(): void
    {
        $cert = new Certificate('Y2VydA==');
        $chainCert = new Certificate('Y2hhaW4=');

        $params = new SignatureParameters(
            signatureLevel: SignatureLevel::XAdES_BASELINE_LT,
            digestAlgorithm: DigestAlgorithm::SHA512,
            signaturePackaging: SignaturePackaging::DETACHED,
            signingCertificate: $cert,
            certificateChain: [$chainCert],
            asicContainerType: ContainerType::ASiC_E,
        );

        $dss = $params->toDssParameters();

        self::assertSame('XAdES_BASELINE_LT', $dss['signatureLevel']);
        self::assertSame('SHA512', $dss['digestAlgorithm']);
        self::assertSame('DETACHED', $dss['signaturePackaging']);
        self::assertSame('Y2VydA==', $dss['signingCertificate']['encodedCertificate']);
        self::assertCount(1, $dss['certificateChain']);
        self::assertSame('Y2hhaW4=', $dss['certificateChain'][0]['encodedCertificate']);
        self::assertSame('ASiC_E', $dss['asicContainerType']);
    }

    #[Test]
    public function it_creates_new_instance_with_signing_certificate(): void
    {
        $params = new SignatureParameters(signatureLevel: SignatureLevel::PAdES_BASELINE_B);
        $cert = new Certificate('Y2VydA==');
        $chainCert = new Certificate('Y2hhaW4=');

        $withCert = $params->withSigningCertificate($cert, [$chainCert]);

        self::assertNull($params->signingCertificate);
        self::assertSame([], $params->certificateChain);

        self::assertSame($cert, $withCert->signingCertificate);
        self::assertCount(1, $withCert->certificateChain);
        self::assertSame(SignatureLevel::PAdES_BASELINE_B, $withCert->signatureLevel);
    }
}
