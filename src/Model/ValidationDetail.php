<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class ValidationDetail
{
    public function __construct(
        public string $message,
        public string $severity = 'info',
    ) {}
}
