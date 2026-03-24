<?php

declare(strict_types=1);

namespace Authentin\Eusig\Contract;

use Authentin\Eusig\Model\Certificate;
use Authentin\Eusig\Model\DigestAlgorithm;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\ToBeSigned;

interface TokenInterface
{
    public function sign(ToBeSigned $toBeSigned, DigestAlgorithm $digestAlgorithm): SignatureValue;

    public function getCertificate(): Certificate;

    /**
     * @return list<Certificate>
     */
    public function getCertificateChain(): array;
}
