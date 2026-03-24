<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class ValidationResult
{
    /**
     * @param list<ValidationDetail> $details
     */
    public function __construct(
        public bool $valid,
        public array $details = [],
        public ?SignatureFormat $format = null,
        public ?SignatureLevel $level = null,
    ) {}
}
