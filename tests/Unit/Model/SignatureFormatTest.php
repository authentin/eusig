<?php

declare(strict_types=1);

namespace Authentin\Eusig\Tests\Unit\Model;

use Authentin\Eusig\Model\SignatureFormat;
use Authentin\Eusig\Model\SignatureLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignatureFormatTest extends TestCase
{
    #[Test]
    public function it_has_expected_formats(): void
    {
        self::assertSame('pades', SignatureFormat::PAdES->value);
        self::assertSame('xades', SignatureFormat::XAdES->value);
        self::assertSame('cades', SignatureFormat::CAdES->value);
        self::assertSame('jades', SignatureFormat::JAdES->value);
        self::assertSame('asice', SignatureFormat::ASiCE->value);
    }

    #[Test]
    public function it_has_expected_signature_levels(): void
    {
        self::assertSame('PAdES_BASELINE_B', SignatureLevel::PAdES_BASELINE_B->value);
        self::assertSame('XAdES_BASELINE_LT', SignatureLevel::XAdES_BASELINE_LT->value);
        self::assertSame('CAdES_BASELINE_LTA', SignatureLevel::CAdES_BASELINE_LTA->value);
        self::assertSame('JAdES_BASELINE_T', SignatureLevel::JAdES_BASELINE_T->value);
    }

    #[Test]
    public function format_can_be_created_from_string(): void
    {
        self::assertSame(SignatureFormat::PAdES, SignatureFormat::from('pades'));
        self::assertSame(SignatureLevel::PAdES_BASELINE_LTA, SignatureLevel::from('PAdES_BASELINE_LTA'));
    }
}
