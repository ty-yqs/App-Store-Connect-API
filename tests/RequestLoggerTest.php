<?php

declare(strict_types=1);

use App\Http\Request;
use App\Services\RequestLogger;
use PHPUnit\Framework\TestCase;

final class RequestLoggerTest extends TestCase
{
    private array $originalServer = [];
    private array $originalGet = [];
    private array $originalEnv = [];
    private ?string $logFile = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_ENV = $this->originalEnv;

        $this->setEnv('LOG_ENABLED', null);
        $this->setEnv('LOG_LEVEL', null);
        $this->setEnv('LOG_HTTP_ENABLED', null);
        $this->setEnv('LOG_STDERR_ENABLED', null);
        $this->setEnv('LOG_FILEPATH', null);

        RequestLogger::clearContext();

        if ($this->logFile !== null && is_file($this->logFile)) {
            @unlink($this->logFile);
        }

        parent::tearDown();
    }

    public function testInboundAndOutboundAreWrittenAndMasked(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'request-log-');

        $this->setEnv('LOG_ENABLED', 'true');
        $this->setEnv('LOG_LEVEL', 'debug');
        $this->setEnv('LOG_HTTP_ENABLED', 'true');
        $this->setEnv('LOG_STDERR_ENABLED', 'false');
        $this->setEnv('LOG_FILEPATH', $this->logFile);

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/v1/apps?token=mytoken',
            'HTTP_AUTHORIZATION' => 'Bearer mytoken123456',
            'HTTP_USER_AGENT' => 'phpunit',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $_GET = [
            'token' => 'mytoken',
            'q' => 'apps',
        ];

        $request = Request::capture();
        RequestLogger::setRequestIdContext($request->requestId());

        RequestLogger::logInbound($request, $request->requestId());
        RequestLogger::logOutbound($request, 200, ['ok' => true], 15.0, $request->requestId());

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);

        $inbound = json_decode($lines[0], true);
        $outbound = json_decode($lines[1], true);

        $this->assertSame('inbound', $inbound['event']);
        $this->assertNotSame('Bearer mytoken123456', $inbound['headers']['authorization']);
        $this->assertNotSame('mytoken', $inbound['query']['token']);

        $this->assertSame('outbound', $outbound['event']);
        $this->assertSame(200, $outbound['status']);
    }

    public function testLoggingNeverThrowsWhenFileWriteFails(): void
    {
        $this->setEnv('LOG_ENABLED', 'true');
        $this->setEnv('LOG_LEVEL', 'debug');
        $this->setEnv('LOG_HTTP_ENABLED', 'true');
        $this->setEnv('LOG_STDERR_ENABLED', 'false');
        $this->setEnv('LOG_FILEPATH', '/dev/null/request.log');

        RequestLogger::logError(new \RuntimeException('boom'), 'req-fail', null, 2.0);

        $this->assertTrue(true);
    }

    public function testUpstreamLoggingCanBeDisabled(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'request-log-');

        $this->setEnv('LOG_ENABLED', 'true');
        $this->setEnv('LOG_LEVEL', 'debug');
        $this->setEnv('LOG_HTTP_ENABLED', 'false');
        $this->setEnv('LOG_STDERR_ENABLED', 'false');
        $this->setEnv('LOG_FILEPATH', $this->logFile);

        RequestLogger::setRequestIdContext('req-upstream-disabled');
        RequestLogger::logUpstreamAttempt('GET', 'https://example.com/v1/apps?token=abc', 1, 2);
        RequestLogger::logUpstreamResult('GET', 'https://example.com/v1/apps?token=abc', 1, 2, 200, null, 6.0);

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->assertTrue($lines === false || $lines === []);
    }

    private function setEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
            return;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}
