<?php

declare(strict_types=1);

namespace Stuff\Webclient\Extension\Log;

use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Handler implements RequestHandlerInterface
{

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        if (!array_key_exists('status', $query) || (int)$query['status'] < 100 || (int)(int)$query['status'] > 599) {
            throw new Exception('whoops!');
        }
        $status = (int)$query['status'];
        $headers = [
            'Content-Type' => 'text/plain',
        ];
        if ($status >= 300 && $status <= 399) {
            $location = $request
                ->getUri()
                ->withUserInfo('')
                ->withPath('')
                ->withFragment('')
                ->withQuery('')
                ->__toString()
            ;
            $headers['Location'] = $location;
        }
        return new Response($status, $headers, 'phpunit', '1.1');
    }
}
