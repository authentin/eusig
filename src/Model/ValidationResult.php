<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class ValidationResult
{
    /**
     * @param list<SignatureValidation> $signatures
     * @param array<string, mixed>     $rawReport   Full DSS WSReportsDTO for advanced consumers
     */
    public function __construct(
        public bool $valid,
        public int $signaturesCount,
        public int $validSignaturesCount,
        public array $signatures = [],
        public array $rawReport = [],
    ) {}
}
