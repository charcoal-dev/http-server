<?php
/*
 * This file is a part of "charcoal-dev/http-router" package.
 * https://github.com/charcoal-dev/http-router
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-router/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Data\UrlInfo;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Policy\RouterPolicy;
use Charcoal\Http\Router\Request\Request;
use Charcoal\Http\Router\Support\PolicyHelper;

/**
 * Class RoutingTest
 */
class RoutingTest extends \PHPUnit\Framework\TestCase
{
    public static function getPolicy(): RouterPolicy
    {
        return new RouterPolicy(
            PolicyHelper::getRequestHeaderPolicy(),
            PolicyHelper::getRequestPayloadPolicy(),
            PolicyHelper::getResponseHeaderPolicy(),
            PolicyHelper::getResponsePayloadPolicy(),
            null,
        );
    }

    /**
     * @return void
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Http\Commons\Exceptions\InvalidUrlException
     */
    public function testRouting1(): void
    {
        $router = new \Charcoal\Http\Router\Router(static::getPolicy());
        $route1 = $router->route('/docs/*', 'MyApp\Services\Docs\Controllers\Libs\*')
            ->ignorePathIndexes(0, 1);
        $route2 = $router->route('/auth/*', 'MyApp\Services\API\Controllers\Account\*')
            ->ignorePathIndexes(0);
        $route3 = $router->route('/profiles/*', 'MyApp\Services\API\Controllers\PublicProfile');

        // Primary wildcard rule should be defined in last
        $mainRoute = $router->route('/*', 'MyApp\Services\API\Controllers\*');

        $req1 = $this->createRequest("https://www.charcoal.dev/home");
        $req2 = $this->createRequest("/docs/lib/http_router");
        $req3 = $this->createRequest("/auth/dashboard?param=arg1");
        $req4 = $this->createRequest("/profiles/charcoal/main");

        $this->assertNull($route1->getControllerClass($req1->url->path));
        $this->assertEquals('MyApp\Services\Docs\Controllers\Libs\HttpRouter', $route1->getControllerClass($req2->url->path));
        $this->assertNull($route1->getControllerClass($req3->url->path));
        $this->assertNull($route1->getControllerClass($req4->url->path));

        $this->assertNull($route2->getControllerClass($req1->url->path));
        $this->assertNull($route2->getControllerClass($req2->url->path));
        $this->assertEquals('MyApp\Services\API\Controllers\Account\Dashboard', $route2->getControllerClass($req3->url->path));
        $this->assertNull($route2->getControllerClass($req4->url->path));

        $this->assertNull($route3->getControllerClass($req1->url->path));
        $this->assertNull($route3->getControllerClass($req2->url->path));
        $this->assertNull($route3->getControllerClass($req3->url->path));
        $this->assertEquals('MyApp\Services\API\Controllers\PublicProfile', $route3->getControllerClass($req4->url->path));

        // Primary route to "/*" will basically match everything, therefore, should be last-one defined,
        // Which is why "checkClassExists" is always true (only exception is this unit test)
        $this->assertEquals('MyApp\Services\API\Controllers\Home', $mainRoute->getControllerClass($req1->url->path));
        $this->assertEquals('MyApp\Services\API\Controllers\Docs\Lib\HttpRouter', $mainRoute->getControllerClass($req2->url->path));
        $this->assertEquals('MyApp\Services\API\Controllers\Auth\Dashboard', $mainRoute->getControllerClass($req3->url->path));
        $this->assertEquals('MyApp\Services\API\Controllers\Profiles\Charcoal\Main', $mainRoute->getControllerClass($req4->url->path));
    }

    /**
     * @return void
     */
    public function testRouting2(): void
    {
        $router = new \Charcoal\Http\Router\Router(static::getPolicy());
        $r1 = $router->route('/wss', 'MyApp\WSS\ServerController');
        $r2 = $router->route('/wss/*', 'MyApp\WSS\ServerController');
        $r3 = $router->route('/*', 'MyApp\Controllers\*');

        $this->assertNull($r1->getControllerClass($this->createRequest("/home")->url->path));
        $this->assertNull($r1->getControllerClass($this->createRequest("/user/profile")->url->path));
        $this->assertEquals('MyApp\WSS\ServerController', $r1->getControllerClass($this->createRequest("/wss")->url->path));
        $this->assertNull($r1->getControllerClass($this->createRequest("/wss/some-path")->url->path));

        $this->assertNull($r2->getControllerClass($this->createRequest("/home")->url->path));
        $this->assertNull($r2->getControllerClass($this->createRequest("/user/profile")->url->path));
        $this->assertEquals('MyApp\WSS\ServerController', $r2->getControllerClass($this->createRequest("/wss")->url->path));
        $this->assertEquals('MyApp\WSS\ServerController', $r2->getControllerClass($this->createRequest("/wss/some-path")->url->path));

        $this->assertEquals('MyApp\Controllers\Home', $r3->getControllerClass($this->createRequest("/home")->url->path));
        $this->assertEquals('MyApp\Controllers\User\Profile', $r3->getControllerClass($this->createRequest("/user/profile")->url->path));
        $this->assertEquals('MyApp\Controllers\Wss', $r3->getControllerClass($this->createRequest("/wss")->url->path));
        $this->assertEquals('MyApp\Controllers\Wss\Path1', $r3->getControllerClass($this->createRequest("/wss/path1")->url->path));
    }

    /**
     * @param string $url
     * @param HttpMethod $method
     * @return Request
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Http\Commons\Exceptions\InvalidUrlException
     */
    private function createRequest(string $url, HttpMethod $method = HttpMethod::GET): Request
    {
        $policy = static::getPolicy();
        return new Request(
            $method,
            new UrlInfo($url),
            new Headers($policy->incomingHeaders),
            new UnsafePayload($policy->incomingPayload),
            new Buffer()
        );
    }
}

