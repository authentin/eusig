<?php

declare(strict_types=1);

namespace Authentin\Eusig\Contract;

use Authentin\Eusig\Exception\SigningFailedException;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignedDocument;

interface SignerInterface
{
    /**
     * @throws SigningFailedException
     */
    public function sign(Document $document, SignatureParameters $parameters): SignedDocument;
}
