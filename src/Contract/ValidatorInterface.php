<?php

declare(strict_types=1);

namespace Authentin\Eusig\Contract;

use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\ValidationResult;

interface ValidatorInterface
{
    public function validateSignature(Document $signedDocument, ?Document $originalDocument = null): ValidationResult;

    /**
     * @return list<Document>
     */
    public function getOriginalDocuments(Document $signedDocument, ?string $signatureId = null): array;
}
