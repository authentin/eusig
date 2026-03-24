<?php

declare(strict_types=1);

namespace Authentin\Eusig;

use Authentin\Eusig\Contract\SignerInterface;
use Authentin\Eusig\Contract\SigningClientInterface;
use Authentin\Eusig\Contract\TokenInterface;
use Authentin\Eusig\Exception\SigningFailedException;
use Authentin\Eusig\Model\Document;
use Authentin\Eusig\Model\SignatureParameters;
use Authentin\Eusig\Model\SignedDocument;

final readonly class Signer implements SignerInterface
{
    public function __construct(
        private SigningClientInterface $signingClient,
        private TokenInterface $token,
    ) {}

    public function sign(Document $document, SignatureParameters $parameters): SignedDocument
    {
        try {
            $parametersWithCert = $parameters->withSigningCertificate(
                $this->token->getCertificate(),
                $this->token->getCertificateChain(),
            );

            $toBeSigned = $this->signingClient->getDataToSign($document, $parametersWithCert);

            $signatureValue = $this->token->sign($toBeSigned, $parametersWithCert->digestAlgorithm);

            return $this->signingClient->signDocument($document, $parametersWithCert, $signatureValue);
        } catch (SigningFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SigningFailedException('Signing failed: ' . $e->getMessage(), previous: $e);
        }
    }
}
