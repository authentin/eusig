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
     * @return array<string, mixed>
     */
    public function toDssParameters(): array
    {
        $params = [
            'digestAlgorithm' => $this->digestAlgorithm->value,
        ];

        if (null !== $this->containerType) {
            $params['containerType'] = $this->containerType->value;
        }

        return $params;
    }
}
