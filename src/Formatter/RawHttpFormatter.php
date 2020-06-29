<?php

declare(strict_types=1);

namespace Webclient\Extension\Log\Formatter;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RawHttpFormatter implements Formatter
{

    public function request(RequestInterface $request, string $id): string
    {

        $log = 'Request ' . $id . PHP_EOL;
        $log .= $request->getMethod() . ' ';
        $log .= $request->getUri()->__toString() . ' ';
        $log .=  'HTTP/' . $request->getProtocolVersion() . PHP_EOL;
        foreach (array_keys($request->getHeaders()) as $header) {
            $log .= $header . ': ' . $request->getHeaderLine($header) . PHP_EOL;
        }
        $log .= PHP_EOL . $request->getBody()->__toString();
        $request->getBody()->rewind();
        return $log;
    }

    public function response(ResponseInterface $response, string $id): string
    {
        $log = 'Response ' . $id . PHP_EOL;
        $code = $response->getStatusCode();
        $log .= 'HTTP/' . $response->getProtocolVersion() . ' ' . $code . ' ' . $response->getReasonPhrase() . PHP_EOL;
        foreach (array_keys($response->getHeaders()) as $header) {
            $log .= $header . ': ' . $response->getHeaderLine($header) . PHP_EOL;
        }
        $log .= PHP_EOL . $response->getBody()->__toString();
        $response->getBody()->rewind();
        return $log;
    }

    public function error(ClientExceptionInterface $error, string $id): string
    {
        return 'Connection ' . $id . PHP_EOL . 'Error: ' . $error->getMessage();
    }
}
