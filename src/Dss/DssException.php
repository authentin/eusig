<?php

declare(strict_types=1);

namespace Authentin\Eusig\Dss;

use Authentin\Eusig\Exception\EusigException;

final class DssException extends \RuntimeException implements EusigException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
