<?php

declare(strict_types=1);

namespace Tests\Webclient\Extension\Log;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Stuff\Webclient\Extension\Log\Error;
use Webclient\Extension\Log\Formatter\RawHttpFormatter;

class RawHttpFormatterTest extends TestCase
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var RawHttpFormatter
     */
    private $formatter;

    /**
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param string $protocolVersion
     *
     * @dataProvider provideRequest
     */
    public function testRequest(string $method, string $uri, array $headers, string $body, string $protocolVersion)
    {
        $this->init();
        $request = new Request($method, $uri, $headers, $body, $protocolVersion);
        $expected = 'Request ' . $this->id . PHP_EOL;
        $expected .= $method . ' ' . $uri . ' HTTP/' . $protocolVersion . PHP_EOL;
        if (preg_match('/http(s?):\/\/(?<host>[^\/]+)($|\/)/ui', $uri, $m) && !array_key_exists('Host', $headers)) {
            $expected .= 'Host: ' . $m['host'] . PHP_EOL;
        }
        foreach ($headers as $key => $value) {
            $expected .= $key . ': ' . $value . PHP_EOL;
        }
        $expected .= PHP_EOL . $body;
        $this->assertSame($expected, $this->formatter->request($request, $this->id));
    }

    /**
     * @param int $status
     * @param string $phrase
     * @param array $headers
     * @param string $body
     * @param string $protocolVersion
     *
     * @dataProvider provideResponse
     */
    public function testResponse(int $status, string $phrase, array $headers, string $body, string $protocolVersion)
    {
        $this->init();
        $response = new Response($status, $headers, $body, $protocolVersion, $phrase);
        $expected = 'Response ' . $this->id . PHP_EOL;
        $expected .= 'HTTP/' . $protocolVersion . ' ' . $status . ' ' . $phrase . PHP_EOL;
        foreach ($headers as $key => $value) {
            $expected .= $key . ': ' . $value . PHP_EOL;
        }
        $expected .= PHP_EOL . $body;
        $this->assertSame($expected, $this->formatter->response($response, $this->id));
    }

    /**
     * @param string $message
     *
     * @dataProvider provideError
     */
    public function testError(string $message)
    {
        $this->init();
        $error = new Error($message);
        $expected = 'Connection ' . $this->id . PHP_EOL;
        $expected .= 'Error: ' . $message;
        $this->assertSame($expected, $this->formatter->error($error, $this->id));
    }

    public function provideRequest(): array
    {
        return [
            ['GET', 'https://api.io/', ['Accept' => 'text/html'], '', '1.0'],
            ['POST', 'https://api.io/users', ['Content-Type' => 'application/json'], '{"name":"Tyler"}', '1.1'],
            ['PUT', 'https://api.io/users/1', ['Content-Type' => 'application/json'], '{"name":"Tyler"}', '2.0'],
            ['PATCH', 'https://api.io/users/1', ['Content-Type' => 'application/json'], '{"name":"Tyler"}', '2'],
            ['DELETE', 'https://api.io/users/1', [], '', '1.1'],
        ];
    }

    public function provideResponse(): array
    {
        return [
            [200, 'OK', ['Content-Type' => 'text/plain'], 'It works!', '1.0'],
            [301, 'Found', ['Content-Type' => 'text/plain', 'Location' => '/new'], 'redirect to /new', '1.1'],
            [204, 'No contents', [], '', '2.0'],
        ];
    }

    public function provideError(): array
    {
        return [
            ['Timeout connection'],
            ['Network error'],
        ];
    }

    private function init()
    {
        $this->id = 'r1';
        $this->formatter = new RawHttpFormatter();
    }
}
