<?php

declare(strict_types=1);

namespace Authentin\Eusig\Model;

final readonly class TimestampParameters
{
    public function __construct(
        public DigestAlgorithm $digestAlgorithm = DigestAlgorithm::SHA256,
        public ?ContainerType $containerType = null,
    ) {}

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toDssParameters(): array
    {
        $params = [
            'digestAlgorithm' => $this->digestAlgorithm->value,
        ];

        if (null !== $this->containerType) {
            $params['timestampContainerForm'] = $this->containerType->value;
        }

        return $params;
    }
}
