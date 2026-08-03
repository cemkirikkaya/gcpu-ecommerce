<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Testing\PendingCommand;
use Illuminate\Testing\TestResponse;

/**
 * @property Application $app
 *
 * @method TestResponse get(string $uri, array $headers = [])
 * @method TestResponse getJson(string $uri, array $headers = [], int $options = 0)
 * @method TestResponse post(string $uri, array $data = [], array $headers = [])
 * @method TestResponse actingAs(Authenticatable $user, ?string $driver = null)
 * @method Wormhole travel(int|float $value)
 * @method PendingCommand artisan(string $command, array $parameters = [])
 * @method void seed(array|string $class = 'Database\\Seeders\\DatabaseSeeder')
 * @method void expectException(string $exception)
 * @method void expectExceptionMessage(string $message)
 * @method void assertSame(mixed $expected, mixed $actual, string $message = '')
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
}
