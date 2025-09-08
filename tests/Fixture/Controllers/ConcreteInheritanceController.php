<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Fixture\Controllers;

use Charcoal\Http\Commons\Enums\CacheControl;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Attributes\CacheControlAttribute;

#[CacheControlAttribute(new CacheControlDirectives(CacheControl::Private, maxAge: 0, noCache: true))]
final class ConcreteInheritanceController extends AbstractApiController
{
    public function get(): void
    {
    }

    #[CacheControlAttribute(new CacheControlDirectives(CacheControl::Private, maxAge: 1800))]
    public function post(): void
    {
    }
}
