<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

enum SignatureFormat: string
{
    case PAdES = 'pades';
    case XAdES = 'xades';
    case CAdES = 'cades';
    case JAdES = 'jades';
    case ASiCE = 'asice';
}
