<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class ToBeSigned
{
    public function __construct(
        public string $bytes,
    ) {}
}
