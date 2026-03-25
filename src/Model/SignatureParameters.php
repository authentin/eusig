<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class SignatureParameters
{
    /**
     * @param list<Certificate> $certificateChain
     *
     * Note: $signingCertificate is required for signing operations (getDataToSign/signDocument).
     * The Signer convenience class sets it automatically from the TokenInterface.
     * It is not needed for extendDocument or timestampDocument.
     */
    public function __construct(
        public SignatureLevel $signatureLevel,
        public DigestAlgorithm $digestAlgorithm = DigestAlgorithm::SHA256,
        public SignaturePackaging $signaturePackaging = SignaturePackaging::ENVELOPED,
        public ?Certificate $signingCertificate = null,
        public array $certificateChain = [],
        public ?ContainerType $asicContainerType = null,
    ) {}

    /**
     * @param list<Certificate> $certificateChain
     */
    public function withSigningCertificate(Certificate $signingCertificate, array $certificateChain = []): self
    {
        return new self(
            signatureLevel: $this->signatureLevel,
            digestAlgorithm: $this->digestAlgorithm,
            signaturePackaging: $this->signaturePackaging,
            signingCertificate: $signingCertificate,
            certificateChain: $certificateChain,
            asicContainerType: $this->asicContainerType,
        );
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toDssParameters(): array
    {
        $params = [
            'signatureLevel' => $this->signatureLevel->value,
            'digestAlgorithm' => $this->digestAlgorithm->value,
            'signaturePackaging' => $this->signaturePackaging->value,
        ];

        if (null !== $this->signingCertificate) {
            $params['signingCertificate'] = [
                'encodedCertificate' => $this->signingCertificate->encoded,
            ];
        }

        if ([] !== $this->certificateChain) {
            $params['certificateChain'] = \array_map(
                static fn(Certificate $cert): array => ['encodedCertificate' => $cert->encoded],
                $this->certificateChain,
            );
        }

        if (null !== $this->asicContainerType) {
            $params['asicContainerType'] = $this->asicContainerType->value;
        }

        return $params;
    }
}
