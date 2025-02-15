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

/**
 * Class RoutingTest
 */
class RoutingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testRouting1(): void
    {
        $router = new \Charcoal\Http\Router\Router();
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

        $this->assertNull($route1->try($req1, checkClassExists: false));
        $this->assertEquals('MyApp\Services\Docs\Controllers\Libs\HttpRouter', $route1->try($req2, checkClassExists: false));
        $this->assertNull($route1->try($req3, checkClassExists: false));
        $this->assertNull($route1->try($req4, checkClassExists: false));

        $this->assertNull($route2->try($req1, checkClassExists: false));
        $this->assertNull($route2->try($req2, checkClassExists: false));
        $this->assertEquals('MyApp\Services\API\Controllers\Account\Dashboard', $route2->try($req3, checkClassExists: false));
        $this->assertNull($route2->try($req4, checkClassExists: false));

        $this->assertNull($route3->try($req1, checkClassExists: false));
        $this->assertNull($route3->try($req2, checkClassExists: false));
        $this->assertNull($route3->try($req3, checkClassExists: false));
        $this->assertEquals('MyApp\Services\API\Controllers\PublicProfile', $route3->try($req4, checkClassExists: false));

        // Primary route to "/*" will basically match everything, therefore should be last-one defined,
        // Which is why "checkClassExists" is always true (only exception is this unit test)
        $this->assertEquals('MyApp\Services\API\Controllers\Home', $mainRoute->try($req1, checkClassExists: false));
        $this->assertEquals('MyApp\Services\API\Controllers\Docs\Lib\HttpRouter', $mainRoute->try($req2, checkClassExists: false));
        $this->assertEquals('MyApp\Services\API\Controllers\Auth\Dashboard', $mainRoute->try($req3, checkClassExists: false));
        $this->assertEquals('MyApp\Services\API\Controllers\Profiles\Charcoal\Main', $mainRoute->try($req4, checkClassExists: false));
    }

    /**
     * @return void
     */
    public function testRouting2(): void
    {
        $router = new \Charcoal\Http\Router\Router();
        $r1 = $router->route('/wss', 'MyApp\WSS\ServerController');
        $r2 = $router->route('/wss/*', 'MyApp\WSS\ServerController');
        $r3 = $router->route('/*', 'MyApp\Controllers\*');

        $this->assertNull($r1->try($this->createRequest("/home"), checkClassExists: false));
        $this->assertNull($r1->try($this->createRequest("/user/profile"), checkClassExists: false));
        $this->assertEquals('MyApp\WSS\ServerController', $r1->try($this->createRequest("/wss"), checkClassExists: false));
        $this->assertNull($r1->try($this->createRequest("/wss/some-path"), checkClassExists: false));

        $this->assertNull($r2->try($this->createRequest("/home"), checkClassExists: false));
        $this->assertNull($r2->try($this->createRequest("/user/profile"), checkClassExists: false));
        $this->assertEquals('MyApp\WSS\ServerController', $r2->try($this->createRequest("/wss"), checkClassExists: false));
        $this->assertEquals('MyApp\WSS\ServerController', $r2->try($this->createRequest("/wss/some-path"), checkClassExists: false));

        $this->assertEquals('MyApp\Controllers\Home', $r3->try($this->createRequest("/home"), checkClassExists: false));
        $this->assertEquals('MyApp\Controllers\User\Profile', $r3->try($this->createRequest("/user/profile"), checkClassExists: false));
        $this->assertEquals('MyApp\Controllers\Wss', $r3->try($this->createRequest("/wss"), checkClassExists: false));
        $this->assertEquals('MyApp\Controllers\Wss\Path1', $r3->try($this->createRequest("/wss/path1"), checkClassExists: false));
    }

    /**
     * @param string $url
     * @param \Charcoal\Http\Commons\HttpMethod $method
     * @return \Charcoal\Http\Router\Controllers\Request
     */
    private function createRequest(string $url, \Charcoal\Http\Commons\HttpMethod $method = \Charcoal\Http\Commons\HttpMethod::GET): \Charcoal\Http\Router\Controllers\Request
    {
        return new \Charcoal\Http\Router\Controllers\Request(
            $method,
            new \Charcoal\Http\Commons\UrlInfo($url),
            new \Charcoal\Http\Commons\Headers(),
            new \Charcoal\Http\Commons\ReadOnlyPayload(),
            new \Charcoal\Buffers\Buffer()
        );
    }
}

