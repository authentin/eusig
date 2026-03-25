<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class SignatureValue
{
    public function __construct(
        public string $algorithm,
        public string $bytes,
    ) {}
}
