<?php

declare(strict_types=1);

namespace Stuff\Webclient\Extension\Log;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class Error extends Exception implements ClientExceptionInterface
{

    public function __construct(string $message)
    {
        parent::__construct($message, 1);
    }
}
