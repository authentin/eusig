<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class Certificate
{
    public function __construct(
        public string $encodedCertificate,
    ) {}
}
