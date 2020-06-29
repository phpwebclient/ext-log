[![Latest Stable Version](https://img.shields.io/packagist/v/webclient/ext-log.svg?style=flat-square)](https://packagist.org/packages/webclient/ext-log)
[![Total Downloads](https://img.shields.io/packagist/dt/webclient/ext-log.svg?style=flat-square)](https://packagist.org/packages/webclient/ext-log/stats)
[![License](https://img.shields.io/packagist/l/webclient/ext-log.svg?style=flat-square)](https://github.com/ddrv/php-http-client-wrapper-log/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/webclient/ext-log.svg?style=flat-square)](https://php.net)

# webclient/ext-log

Logging decorator for PSR-18 HTTP clients. 

# Install

Install this package, your favorite [psr-3 implementation](https://packagist.org/providers/psr/log-implementation) and your favorite [psr-18 implementation](https://packagist.org/providers/psr/http-client-implementation).

```bash
composer require webclient/ext-log:^1.0
```

# Using

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Webclient\Extension\Log\Client;

/** 
 * @var ClientInterface $client 
 * @var LoggerInterface $logger 
 */
$http = new Client($client, $logger);

/** @var RequestInterface $request */
$response = $http->sendRequest($request);
```

# Custom Request ID

You may implement `\Webclient\Extension\Log\IdGenerator\IdGenerator` for your Request ID in logs

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Webclient\Extension\Log\Client;
use Webclient\Extension\Log\IdGenerator\IdGenerator;

/** 
 * @var ClientInterface $client 
 * @var LoggerInterface $logger 
 * @var IdGenerator $idGenerator 
 */
$http = new Client($client, $logger, $idGenerator);

/** @var RequestInterface $request */
$response = $http->sendRequest($request);
```

# Custom log output

You may implement `\Webclient\Extension\Log\Formatter\Formatter` for your Request/Response output in logs

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Webclient\Extension\Log\Client;
use Webclient\Extension\Log\Formatter\Formatter;

/** 
 * @var ClientInterface $client 
 * @var LoggerInterface $logger 
 * @var Formatter $formatter 
 */
$http = new Client($client, $logger, null, $formatter);

/** @var RequestInterface $request */
$response = $http->sendRequest($request);
```

# Log levels

You may set your log levels

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webclient\Extension\Log\Client;

/** 
 * @var ClientInterface $client 
 * @var LoggerInterface $logger 
 */
$http = new Client(
    $client,
    $logger,
    null,
    null,
    LogLevel::INFO, // Request log level
    LogLevel::INFO, // Info responses (status codes 1xx)
    LogLevel::INFO, // Success responses (status codes 2xx)
    LogLevel::INFO, // Redirect responses (status codes 3xx)
    LogLevel::EMERGENCY, // Client error responses (status codes 4xx)
    LogLevel::ERROR, // Server error responses (status codes 5xx)
    LogLevel::WARNING // Base HTTP client exceptions
);

/** @var RequestInterface $request */
$response = $http->sendRequest($request);
```