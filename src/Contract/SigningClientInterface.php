<?php

declare(strict_types=1);

namespace Authentin\Eusig\Contract;

use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignatureValue;
use Authentin\Eusig\Model\SignedDocument;
use Authentin\Eusig\Model\TimestampParameters;
use Authentin\Eusig\Model\ToBeSigned;

interface SigningClientInterface
{
    public function getDataToSign(Document $document, SignatureParameters $parameters): ToBeSigned;

    public function signDocument(Document $document, SignatureParameters $parameters, SignatureValue $signatureValue): SignedDocument;

    public function extendDocument(Document $document, SignatureParameters $parameters): SignedDocument;

    public function timestampDocument(Document $document, TimestampParameters $parameters): SignedDocument;
}
