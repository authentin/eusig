<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

enum SignatureLevel: string
{
    // PAdES (PDF)
    case PAdES_BASELINE_B = 'PAdES_BASELINE_B';
    case PAdES_BASELINE_T = 'PAdES_BASELINE_T';
    case PAdES_BASELINE_LT = 'PAdES_BASELINE_LT';
    case PAdES_BASELINE_LTA = 'PAdES_BASELINE_LTA';

    // XAdES (XML)
    case XAdES_BASELINE_B = 'XAdES_BASELINE_B';
    case XAdES_BASELINE_T = 'XAdES_BASELINE_T';
    case XAdES_BASELINE_LT = 'XAdES_BASELINE_LT';
    case XAdES_BASELINE_LTA = 'XAdES_BASELINE_LTA';

    // CAdES (CMS)
    case CAdES_BASELINE_B = 'CAdES_BASELINE_B';
    case CAdES_BASELINE_T = 'CAdES_BASELINE_T';
    case CAdES_BASELINE_LT = 'CAdES_BASELINE_LT';
    case CAdES_BASELINE_LTA = 'CAdES_BASELINE_LTA';

    // JAdES (JSON)
    case JAdES_BASELINE_B = 'JAdES_BASELINE_B';
    case JAdES_BASELINE_T = 'JAdES_BASELINE_T';
    case JAdES_BASELINE_LT = 'JAdES_BASELINE_LT';
    case JAdES_BASELINE_LTA = 'JAdES_BASELINE_LTA';
}
