<?php

declare(strict_types=1);

namespace Tests\Webclient\Extension\Log;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LogLevel;
use Stuff\Webclient\Extension\Log\Handler;
use Stuff\Webclient\Extension\Log\Logger;
use Stuff\Webclient\Extension\Log\StuffIdGenerator;
use Webclient\Fake\Client as FakeClient;
use Webclient\Extension\Log\Formatter\Formatter;
use Webclient\Extension\Log\Formatter\RawHttpFormatter;
use Webclient\Extension\Log\IdGenerator\IdGenerator;
use Webclient\Extension\Log\IdGenerator\UniqueIdGenerator;
use Webclient\Extension\Log\Client;

class LoggedClientTest extends TestCase
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StuffIdGenerator
     */
    private $generator;

    /**
     * @param int $status
     * @param string $requestLevel
     * @param string $responseLevel
     *
     * @dataProvider provideLogging
     *
     * @throws ClientExceptionInterface
     */
    public function testLogging(int $status, string $requestLevel, string $responseLevel)
    {
        $this->init();
        $levels = $this->getFreeLevels([$requestLevel, $responseLevel]);
        $otherLevel = $levels[0];
        $params = [
            'req' => $requestLevel,
            '1xx' => $otherLevel,
            '2xx' => $otherLevel,
            '3xx' => $otherLevel,
            '4xx' => $otherLevel,
            '5xx' => $otherLevel,
            'err' => $otherLevel,
        ];
        $key = (int)($status / 100) . 'xx';
        $params[$key] = $responseLevel;

        $client = $this->getLoggedClient($params);

        $request = new Request('GET', 'http://phpunit.de?status=' . $status, ['Accept' => 'text/plain'], '', '1.1');

        $this->logger->reset();
        $response = $client->sendRequest($request);

        $requestLog = '===' . PHP_EOL . $this->formatter->request($request, $this->generator->lastId()) . PHP_EOL;
        $responseLog = '===' . PHP_EOL . $this->formatter->response($response, $this->generator->lastId()) . PHP_EOL;

        $this->assertLogs($requestLog, $responseLog, $requestLevel, $responseLevel, $levels);
    }

    /**
     * @param string $requestLevel
     * @param string $errorLevel
     *
     * @dataProvider provideExceptionHandling
     */
    public function testExceptionHandling(string $requestLevel, string $errorLevel)
    {
        $this->init();
        $levels = $this->getFreeLevels([$requestLevel, $errorLevel]);
        $otherLevel = $levels[0];
        $params = [
            'req' => $requestLevel,
            '1xx' => $otherLevel,
            '2xx' => $otherLevel,
            '3xx' => $otherLevel,
            '4xx' => $otherLevel,
            '5xx' => $otherLevel,
            'err' => $errorLevel,
        ];
        $client = $this->getLoggedClient($params);
        $request = new Request('GET', 'http://phpunit.de', ['Accept' => 'text/plain'], '', '1.1');
        $requestLog = $responseLog = '';
        try {
            $client->sendRequest($request);
            $this->fail('Failed asserting that exception of type "' . ClientExceptionInterface::class . '" is thrown.');
        } catch (ClientExceptionInterface $exception) {
            $requestLog = '===' . PHP_EOL . $this->formatter->request($request, $this->generator->lastId()) . PHP_EOL;
            $responseLog = '===' . PHP_EOL . $this->formatter->error($exception, $this->generator->lastId()) . PHP_EOL;
        }
        $this->assertLogs($requestLog, $responseLog, $requestLevel, $errorLevel, $levels);
    }

    public function provideLogging(): array
    {
        return [
            [102, LogLevel::ALERT,     LogLevel::EMERGENCY],
            [200, LogLevel::CRITICAL,  LogLevel::EMERGENCY],
            [301, LogLevel::ERROR,     LogLevel::EMERGENCY],
            [404, LogLevel::WARNING,   LogLevel::EMERGENCY],
            [500, LogLevel::NOTICE,    LogLevel::EMERGENCY],
            [200, LogLevel::INFO,      LogLevel::EMERGENCY],
            [200, LogLevel::DEBUG,     LogLevel::EMERGENCY],

            [102, LogLevel::EMERGENCY, LogLevel::EMERGENCY],
            [200, LogLevel::ALERT,     LogLevel::ALERT],
            [301, LogLevel::CRITICAL,  LogLevel::CRITICAL],
            [404, LogLevel::ERROR,     LogLevel::ERROR],
            [500, LogLevel::WARNING,   LogLevel::WARNING],
            [200, LogLevel::NOTICE,    LogLevel::NOTICE],
            [200, LogLevel::INFO,      LogLevel::INFO],
            [200, LogLevel::DEBUG,     LogLevel::DEBUG],

            [102, LogLevel::EMERGENCY, LogLevel::ALERT],
            [200, LogLevel::EMERGENCY, LogLevel::CRITICAL],
            [301, LogLevel::EMERGENCY, LogLevel::ERROR],
            [404, LogLevel::EMERGENCY, LogLevel::WARNING],
            [500, LogLevel::EMERGENCY, LogLevel::NOTICE],
            [200, LogLevel::EMERGENCY, LogLevel::INFO],
            [200, LogLevel::EMERGENCY, LogLevel::DEBUG],
        ];
    }

    public function provideExceptionHandling(): array
    {
        return [
            [LogLevel::ALERT,     LogLevel::EMERGENCY],
            [LogLevel::CRITICAL,  LogLevel::EMERGENCY],
            [LogLevel::ERROR,     LogLevel::EMERGENCY],
            [LogLevel::WARNING,   LogLevel::EMERGENCY],
            [LogLevel::NOTICE,    LogLevel::EMERGENCY],
            [LogLevel::INFO,      LogLevel::EMERGENCY],
            [LogLevel::DEBUG,     LogLevel::EMERGENCY],

            [LogLevel::EMERGENCY, LogLevel::EMERGENCY],
            [LogLevel::ALERT,     LogLevel::ALERT],
            [LogLevel::CRITICAL,  LogLevel::CRITICAL],
            [LogLevel::ERROR,     LogLevel::ERROR],
            [LogLevel::WARNING,   LogLevel::WARNING],
            [LogLevel::NOTICE,    LogLevel::NOTICE],
            [LogLevel::INFO,      LogLevel::INFO],
            [LogLevel::DEBUG,     LogLevel::DEBUG],

            [LogLevel::EMERGENCY, LogLevel::ALERT],
            [LogLevel::EMERGENCY, LogLevel::CRITICAL],
            [LogLevel::EMERGENCY, LogLevel::ERROR],
            [LogLevel::EMERGENCY, LogLevel::WARNING],
            [LogLevel::EMERGENCY, LogLevel::NOTICE],
            [LogLevel::EMERGENCY, LogLevel::INFO],
            [LogLevel::EMERGENCY, LogLevel::DEBUG],
        ];
    }

    protected function getFormatter(): Formatter
    {
        return new RawHttpFormatter();
    }

    protected function getIdGenerator(): IdGenerator
    {
        return new UniqueIdGenerator();
    }

    private function assertLogs(
        string $requestLog,
        string $responseLog,
        string $requestLevel,
        string $responseLevel,
        array $otherLevels
    ) {
        if ($requestLevel === $responseLevel) {
            $this->assertSame($requestLog . $responseLog, $this->logger->get($requestLevel));
        } else {
            $this->assertSame($requestLog, $this->logger->get($requestLevel));
            $this->assertSame($responseLog, $this->logger->get($responseLevel));
        }
        foreach ($otherLevels as $level) {
            $this->assertSame('', $this->logger->get($level));
        }
    }

    private function getLoggedClient(array $levels): Client
    {
        return new Client(
            new FakeClient(new Handler()),
            $this->logger,
            $this->generator,
            $this->formatter,
            $levels['req'],
            $levels['1xx'],
            $levels['2xx'],
            $levels['3xx'],
            $levels['4xx'],
            $levels['5xx'],
            $levels['err']
        );
    }

    private function getFreeLevels(array $allowed): array
    {
        $levels = [
            LogLevel::DEBUG => true,
            LogLevel::INFO => true,
            LogLevel::NOTICE => true,
            LogLevel::WARNING => true,
            LogLevel::ERROR => true,
            LogLevel::CRITICAL => true,
            LogLevel::ALERT => true,
            LogLevel::EMERGENCY => true,
        ];
        foreach ($allowed as $level) {
            if (array_key_exists($level, $levels)) {
                unset($levels[$level]);
            }
        }
        if (!$levels) {
            return ['none'];
        }
        return array_keys($levels);
    }

    private function init()
    {
        // this code moved from setUp() method for support older phpunit versions
        $this->logger = new Logger();
        $this->generator = new StuffIdGenerator($this->getIdGenerator());
        $this->formatter = $this->getFormatter();
    }
}
