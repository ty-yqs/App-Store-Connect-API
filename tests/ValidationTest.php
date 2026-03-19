<?php

declare(strict_types=1);

use App\Http\ApiException;
use App\Services\Validation;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testRequireStringReturnsTrimmedValue(): void
    {
        $value = Validation::requireString(['iss' => '  team-id  '], 'iss');

        $this->assertSame('team-id', $value);
    }

    public function testRequireStringThrowsWhenFieldIsMissing(): void
    {
        $this->expectException(ApiException::class);
        Validation::requireString([], 'kid');
    }

    public function testNormalizeListQueryAcceptsValidLimit(): void
    {
        $query = Validation::normalizeListQuery(['limit' => '50', 'cursor' => 'abc']);

        $this->assertSame(50, $query['limit']);
        $this->assertSame('abc', $query['cursor']);
    }

    public function testNormalizeListQueryRejectsOutOfRangeLimit(): void
    {
        $this->expectException(ApiException::class);
        Validation::normalizeListQuery(['limit' => '999']);
    }
}
