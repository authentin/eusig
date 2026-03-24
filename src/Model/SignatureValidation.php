<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class SignatureValidation
{
    public function __construct(
        public string $indication,
        public ?string $subIndication = null,
        public ?string $signatureLevel = null,
        public ?string $signedBy = null,
        public ?\DateTimeImmutable $signingTime = null,
    ) {}

    public function isValid(): bool
    {
        return 'TOTAL_PASSED' === $this->indication;
    }
}
