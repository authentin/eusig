<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

enum SignaturePackaging: string
{
    case ENVELOPED = 'ENVELOPED';
    case ENVELOPING = 'ENVELOPING';
    case DETACHED = 'DETACHED';
    case INTERNALLY_DETACHED = 'INTERNALLY_DETACHED';
}
