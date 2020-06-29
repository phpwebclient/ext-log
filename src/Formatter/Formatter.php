<?php

declare(strict_types=1);

namespace Webclient\Extension\Log\Formatter;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Formatter
{

    public function request(RequestInterface $request, string $id): string;

    public function response(ResponseInterface $response, string $id): string;

    public function error(ClientExceptionInterface $error, string $id): string;
}
