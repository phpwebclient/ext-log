<?php

declare(strict_types=1);

namespace Webclient\Extension\Log;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webclient\Extension\Log\Formatter\Formatter;
use Webclient\Extension\Log\Formatter\RawHttpFormatter;
use Webclient\Extension\Log\IdGenerator\IdGenerator;
use Webclient\Extension\Log\IdGenerator\UniqueIdGenerator;

final class Client implements ClientInterface
{

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @var Formatter|null
     */
    private $formatter;

    /**
     * @var string[]
     */
    private $levels = [
        'request' => LogLevel::INFO,
        'resp1xx' => LogLevel::INFO,
        'resp2xx' => LogLevel::INFO,
        'resp3xx' => LogLevel::INFO,
        'resp4xx' => LogLevel::INFO,
        'resp5xx' => LogLevel::INFO,
        'error'   => LogLevel::INFO,
    ];

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        IdGenerator $idGenerator = null,
        Formatter $formatter = null,
        string $requestLogLevel = LogLevel::INFO,
        string $responseInfoLogLevel = LogLevel::INFO,
        string $responseSuccessLogLevel = LogLevel::INFO,
        string $responseRedirectLogLevel = LogLevel::INFO,
        string $responseClientErrorLogLevel = LogLevel::INFO,
        string $responseServerErrorLogLevel = LogLevel::INFO,
        string $errorLogLevel = LogLevel::INFO
    ) {
        $this->client = $client;
        $this->logger = $logger;
        if (is_null($idGenerator)) {
            $idGenerator = new UniqueIdGenerator();
        }
        $this->idGenerator = $idGenerator;
        if (is_null($formatter)) {
            $formatter = new RawHttpFormatter();
        }
        $this->formatter = $formatter;
        $this->setLogLevel('request', $requestLogLevel);
        $this->setLogLevel('resp1xx', $responseInfoLogLevel);
        $this->setLogLevel('resp2xx', $responseSuccessLogLevel);
        $this->setLogLevel('resp3xx', $responseRedirectLogLevel);
        $this->setLogLevel('resp4xx', $responseClientErrorLogLevel);
        $this->setLogLevel('resp5xx', $responseServerErrorLogLevel);
        $this->setLogLevel('error', $errorLogLevel);
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $id = $this->idGenerator->generate();
        $this->log($this->levels['request'], $this->formatter->request($request, $id));
        try {
            $response = $this->client->sendRequest($request);

            $code = 'resp' . substr((string)$response->getStatusCode(), 0, 1) . 'xx';
            $level = array_key_exists($code, $this->levels) ? $this->levels[$code] : 'none';
            $this->log($level, $this->formatter->response($response, $id));
            return $response;
        } catch (ClientExceptionInterface $exception) {
            $this->log($this->levels['error'], $this->formatter->error($exception, $id));
            throw $exception;
        }
    }

    private function setLogLevel(string $type, string $level)
    {
        $all = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];
        $this->levels[$type] = in_array($level, $all) ? $level : 'none';
    }

    private function log(string $level, string $log)
    {
        switch ($level) {
            case LogLevel::DEBUG:
                $this->logger->debug($log);
                break;
            case LogLevel::INFO:
                $this->logger->info($log);
                break;
            case LogLevel::NOTICE:
                $this->logger->notice($log);
                break;
            case LogLevel::WARNING:
                $this->logger->warning($log);
                break;
            case LogLevel::ERROR:
                $this->logger->error($log);
                break;
            case LogLevel::CRITICAL:
                $this->logger->critical($log);
                break;
            case LogLevel::ALERT:
                $this->logger->alert($log);
                break;
            case LogLevel::EMERGENCY:
                $this->logger->emergency($log);
                break;
        }
    }
}
