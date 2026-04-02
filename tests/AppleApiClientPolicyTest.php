<?php

declare(strict_types=1);

use App\Services\AppleApiClient;
use PHPUnit\Framework\TestCase;

final class AppleApiClientPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setEnv('ASC_HTTP_TIMEOUT', null);
        $this->setEnv('ASC_HTTP_CONNECT_TIMEOUT', null);
        $this->setEnv('ASC_HTTP_READ_TIMEOUT', null);

        parent::tearDown();
    }

    public function testIdempotentMethodPolicy(): void
    {
        $client = new AppleApiClient(null, 2, 5, 2, 100, [503]);

        $this->assertTrue($this->invokePrivate($client, 'isIdempotentMethod', ['GET']));
        $this->assertTrue($this->invokePrivate($client, 'isIdempotentMethod', ['DELETE']));
        $this->assertTrue($this->invokePrivate($client, 'isIdempotentMethod', ['PUT']));

        $this->assertFalse($this->invokePrivate($client, 'isIdempotentMethod', ['POST']));
        $this->assertFalse($this->invokePrivate($client, 'isIdempotentMethod', ['PATCH']));
    }

    public function testRetryDecisionHonorsMethodStatusAndAttempts(): void
    {
        $client = new AppleApiClient(null, 2, 5, 2, 100, [503]);

        $this->assertTrue($this->invokePrivate($client, 'shouldRetryAttempt', ['GET', 503, null, 1, 3]));
        $this->assertFalse($this->invokePrivate($client, 'shouldRetryAttempt', ['GET', 503, null, 3, 3]));

        $this->assertFalse($this->invokePrivate($client, 'shouldRetryAttempt', ['POST', 503, null, 1, 3]));
        $this->assertTrue($this->invokePrivate($client, 'shouldRetryAttempt', ['GET', 0, 'timeout', 1, 3]));
    }

    public function testBackoffDelayIsExponential(): void
    {
        $client = new AppleApiClient(null, 2, 5, 2, 200, [503]);

        $this->assertSame(200, $this->invokePrivate($client, 'backoffDelayMs', [1]));
        $this->assertSame(400, $this->invokePrivate($client, 'backoffDelayMs', [2]));
        $this->assertSame(800, $this->invokePrivate($client, 'backoffDelayMs', [3]));
    }

    public function testSplitTimeoutFallsBackToLegacyTimeoutWhenNotConfigured(): void
    {
        $this->setEnv('ASC_HTTP_TIMEOUT', '25');
        $this->setEnv('ASC_HTTP_CONNECT_TIMEOUT', null);
        $this->setEnv('ASC_HTTP_READ_TIMEOUT', null);

        $client = new AppleApiClient();

        $this->assertSame(10, $this->readPrivateProperty($client, 'connectTimeoutSeconds'));
        $this->assertSame(25, $this->readPrivateProperty($client, 'readTimeoutSeconds'));
    }

    public function testSplitTimeoutUsesDedicatedConfigWhenPresent(): void
    {
        $this->setEnv('ASC_HTTP_TIMEOUT', '25');
        $this->setEnv('ASC_HTTP_CONNECT_TIMEOUT', '3');
        $this->setEnv('ASC_HTTP_READ_TIMEOUT', '40');

        $client = new AppleApiClient();

        $this->assertSame(3, $this->readPrivateProperty($client, 'connectTimeoutSeconds'));
        $this->assertSame(40, $this->readPrivateProperty($client, 'readTimeoutSeconds'));
    }

    private function invokePrivate(object $object, string $method, array $args): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
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
