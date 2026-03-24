<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

enum DigestAlgorithm: string
{
    case SHA1 = 'SHA1';
    case SHA224 = 'SHA224';
    case SHA256 = 'SHA256';
    case SHA384 = 'SHA384';
    case SHA512 = 'SHA512';
    case SHA3_256 = 'SHA3-256';
    case SHA3_384 = 'SHA3-384';
    case SHA3_512 = 'SHA3-512';
}
