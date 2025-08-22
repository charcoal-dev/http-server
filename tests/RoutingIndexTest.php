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

use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Group\RouteGroup;
use Charcoal\Http\Router\Routing\Registry\RouteInspect;
use Charcoal\Http\Router\Routing\Route;
use Charcoal\Http\Tests\Router\Fixture\RoutingFixtures;

/**
 * Class RoutingTest
 */
class RoutingIndexTest extends \PHPUnit\Framework\TestCase
{
    private readonly AppRoutes $routes;

    /**
     * @return void
     * @throws RoutingBuilderException
     */
    public function setUp(): void
    {
        Router::$checkControllerExists = false;
        $this->routes = RoutingFixtures::webBlogShipApi2AccountAdmin();
    }

    /**
     * @return void
     */
    public function testRoutesIndex(): void
    {
        $routes = $this->routes->manifest();

        // First, root AppRoutes group
        $this->assertArrayHasKey("/", $routes->routes);
        $this->assertCount(2, $routes->routes["/"]);
        $this->assertInstanceOf(AppRoutes::class, $routes->routes["/"][0]);
        $this->assertNull($routes->routes["/"][0]->namespace);
        $this->assertInstanceOf(Route::class, $routes->routes["/"][1]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/"][1]->methods)));

        // /about
        $this->assertArrayHasKey("/about", $routes->routes);
        $this->assertCount(1, $routes->routes["/about"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/about"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/about"][0]->methods)));

        // /assets/:anyThing
        $this->assertArrayHasKey("/assets/:anyThing", $routes->routes);
        $this->assertCount(1, $routes->routes["/assets/:anyThing"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/assets/:anyThing"][0]);

        // /web
        $this->assertArrayHasKey("/web", $routes->routes);
        $this->assertCount(1, $routes->routes["/web"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web"][0]);

        // /web/blog (merged group + index route)
        $this->assertArrayHasKey("/web/blog", $routes->routes);
        $this->assertCount(2, $routes->routes["/web/blog"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web/blog"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/blog"][1]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/web/blog"][1]->methods)));

        // /web/blog/archive/:year/:month
        $this->assertArrayHasKey("/web/blog/archive/:year/:month", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/blog/archive/:year/:month"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/blog/archive/:year/:month"][0]);

        // /web/blog/post/:slug
        $this->assertArrayHasKey("/web/blog/post/:slug", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/blog/post/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/blog/post/:slug"][0]);

        // /web/blog/post/:slug/edit (merged group + index route)
        $this->assertArrayHasKey("/web/blog/post/:slug/edit", $routes->routes);
        $this->assertCount(2, $routes->routes["/web/blog/post/:slug/edit"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web/blog/post/:slug/edit"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/blog/post/:slug/edit"][1]);
        $this->assertEquals("GET,HEAD,POST,PUT", implode(",", array_keys($routes->routes["/web/blog/post/:slug/edit"][1]->methods)));

        // /web/shop
        $this->assertArrayHasKey("/web/shop", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/shop"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web/shop"][0]);

        // /web/shop/products
        $this->assertArrayHasKey("/web/shop/products", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/shop/products"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/shop/products"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/web/shop/products"][0]->methods)));

        // /web/shop/products/:slug
        $this->assertArrayHasKey("/web/shop/products/:slug", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/shop/products/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/shop/products/:slug"][0]);

        // /web/shop/cart (merged group + index route)
        $this->assertArrayHasKey("/web/shop/cart", $routes->routes);
        $this->assertCount(2, $routes->routes["/web/shop/cart"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web/shop/cart"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/shop/cart"][1]);

        // /web/shop/cart/items/:id
        $this->assertArrayHasKey("/web/shop/cart/items/:id", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/shop/cart/items/:id"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/shop/cart/items/:id"][0]);
        $this->assertEquals("DELETE,POST", implode(",", array_keys($routes->routes["/web/shop/cart/items/:id"][0]->methods)));

        // /web/account
        $this->assertArrayHasKey("/web/account", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/account"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/web/account"][0]);

        // /web/account/login
        $this->assertArrayHasKey("/web/account/login", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/account/login"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/account/login"][0]);

        // /web/account/profile/:id
        $this->assertArrayHasKey("/web/account/profile/:id", $routes->routes);
        $this->assertCount(1, $routes->routes["/web/account/profile/:id"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/web/account/profile/:id"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/web/account/profile/:id"][0]->methods)));

        // /api
        $this->assertArrayHasKey("/api", $routes->routes);
        $this->assertCount(1, $routes->routes["/api"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api"][0]);

        // /api/v1
        $this->assertArrayHasKey("/api/v1", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v1"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v1"][0]);

        // /api/v1/users (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/users", $routes->routes);
        $this->assertCount(3, $routes->routes["/api/v1/users"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v1/users"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/users"][1]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/api/v1/users"][1]->methods)));
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/users"][2]);
        $this->assertEquals("POST", implode(",", array_keys($routes->routes["/api/v1/users"][2]->methods)));

        // /api/v1/users/:id
        $this->assertArrayHasKey("/api/v1/users/:id", $routes->routes);
        $this->assertCount(3, $routes->routes["/api/v1/users/:id"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/users/:id"][0]);
        $this->assertEquals("GET", implode(",", array_keys($routes->routes["/api/v1/users/:id"][0]->methods)));
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/users/:id"][1]);
        $this->assertEquals("PATCH", implode(",", array_keys($routes->routes["/api/v1/users/:id"][1]->methods)));
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/users/:id"][2]);
        $this->assertEquals("DELETE", implode(",", array_keys($routes->routes["/api/v1/users/:id"][2]->methods)));

        // /api/v1/articles (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/articles", $routes->routes);
        $this->assertCount(2, $routes->routes["/api/v1/articles"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v1/articles"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/articles"][1]);
        $this->assertEquals("GET,HEAD,POST", implode(",", array_keys($routes->routes["/api/v1/articles"][1]->methods)));

        // /api/v1/articles/:slug
        $this->assertArrayHasKey("/api/v1/articles/:slug", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v1/articles/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/articles/:slug"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/api/v1/articles/:slug"][0]->methods)));

        // /api/v1/articles/:slug/comments (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/articles/:slug/comments", $routes->routes);
        $this->assertCount(2, $routes->routes["/api/v1/articles/:slug/comments"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v1/articles/:slug/comments"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/articles/:slug/comments"][1]);
        $this->assertEquals("GET,HEAD,POST", implode(",", array_keys($routes->routes["/api/v1/articles/:slug/comments"][1]->methods)));

        // /api/v1/articles/:slug/comments/:commentId
        $this->assertArrayHasKey("/api/v1/articles/:slug/comments/:commentId", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v1/articles/:slug/comments/:commentId"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/articles/:slug/comments/:commentId"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/api/v1/articles/:slug/comments/:commentId"][0]->methods)));

        // /api/v1/search/:anyThing
        $this->assertArrayHasKey("/api/v1/search/:anyThing", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v1/search/:anyThing"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v1/search/:anyThing"][0]);

        // /api/v2
        $this->assertArrayHasKey("/api/v2", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v2"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v2"][0]);

        // /api/v2/reports
        $this->assertArrayHasKey("/api/v2/reports", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v2/reports"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/api/v2/reports"][0]);

        // /api/v2/reports/summary
        $this->assertArrayHasKey("/api/v2/reports/summary", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v2/reports/summary"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v2/reports/summary"][0]);
        $this->assertEquals("GET,HEAD", implode(",", array_keys($routes->routes["/api/v2/reports/summary"][0]->methods)));

        // /api/v2/reports/:year/:month
        $this->assertArrayHasKey("/api/v2/reports/:year/:month", $routes->routes);
        $this->assertCount(1, $routes->routes["/api/v2/reports/:year/:month"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/api/v2/reports/:year/:month"][0]);

        // /admin (merged group + index route)
        $this->assertArrayHasKey("/admin", $routes->routes);
        $this->assertCount(2, $routes->routes["/admin"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/admin"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/admin"][1]);

        // /admin/users (merged group + index route)
        $this->assertArrayHasKey("/admin/users", $routes->routes);
        $this->assertCount(2, $routes->routes["/admin/users"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/admin/users"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/admin/users"][1]);

        // /admin/users/:id
        $this->assertArrayHasKey("/admin/users/:id", $routes->routes);
        $this->assertCount(1, $routes->routes["/admin/users/:id"]);
        $this->assertInstanceOf(Route::class, $routes->routes["/admin/users/:id"][0]);

        // /admin/users/:id/settings (merged group + index route with methods)
        $this->assertArrayHasKey("/admin/users/:id/settings", $routes->routes);
        $this->assertCount(2, $routes->routes["/admin/users/:id/settings"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->routes["/admin/users/:id/settings"][0]);
        $this->assertInstanceOf(Route::class, $routes->routes["/admin/users/:id/settings"][1]);
        $this->assertEquals("GET,HEAD,PATCH,POST", implode(",", array_keys($routes->routes["/admin/users/:id/settings"][1]->methods)));
    }

    public function testInspectRoot(): void
    {
        $inspect = $this->routes->inspect("/");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "HomeController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "HomeController", $inspect->methods["HEAD"]);
    }

    public function testInspectAbout(): void
    {
        $inspect = $this->routes->inspect("/about");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "PageController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "PageController", $inspect->methods["HEAD"]);
    }

    public function testInspectAssetsAny(): void
    {
        $inspect = $this->routes->inspect("/assets/:anyThing");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AssetsController", $inspect->methods["*"]);
    }

    public function testInspectWebGroup(): void
    {
        $inspect = $this->routes->inspect("/web");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectWebBlog(): void
    {
        $inspect = $this->routes->inspect("/web/blog");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "BlogController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "BlogController", $inspect->methods["HEAD"]);
    }

    public function testInspectWebBlogArchiveAny(): void
    {
        $inspect = $this->routes->inspect("/web/blog/archive/:year/:month");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "BlogController", $inspect->methods["*"]);
    }

    public function testInspectWebBlogPostSlugAny(): void
    {
        $inspect = $this->routes->inspect("/web/blog/post/:slug");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "BlogController", $inspect->methods["*"]);
    }

    public function testInspectWebBlogPostSlugEdit(): void
    {
        $inspect = $this->routes->inspect("/web/blog/post/:slug/edit");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(4, $inspect->methods);
        foreach (["GET","HEAD","POST","PUT"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "BlogEditorController", $inspect->methods[$m]);
        }
    }

    public function testInspectWebShopGroup(): void
    {
        $inspect = $this->routes->inspect("/web/shop");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectWebShopProducts(): void
    {
        $inspect = $this->routes->inspect("/web/shop/products");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ProductsController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ProductsController", $inspect->methods["HEAD"]);
    }

    public function testInspectWebShopProductsSlugAny(): void
    {
        $inspect = $this->routes->inspect("/web/shop/products/:slug");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ProductsController", $inspect->methods["*"]);
    }

    public function testInspectWebShopCart(): void
    {
        $inspect = $this->routes->inspect("/web/shop/cart");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "CartController", $inspect->methods["*"]);
    }

    public function testInspectWebShopCartItemsId(): void
    {
        $inspect = $this->routes->inspect("/web/shop/cart/items/:id");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        foreach (["DELETE","POST"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "CartController", $inspect->methods[$m]);
        }
    }

    public function testInspectWebAccountGroup(): void
    {
        $inspect = $this->routes->inspect("/web/account");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectWebAccountLoginAny(): void
    {
        $inspect = $this->routes->inspect("/web/account/login");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AccountController", $inspect->methods["*"]);
    }

    public function testInspectWebAccountProfileId(): void
    {
        $inspect = $this->routes->inspect("/web/account/profile/:id");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AccountController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AccountController", $inspect->methods["HEAD"]);
    }

    public function testInspectApiGroup(): void
    {
        $inspect = $this->routes->inspect("/api");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectApiV1Group(): void
    {
        $inspect = $this->routes->inspect("/api/v1");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectApiV1Users(): void
    {
        $inspect = $this->routes->inspect("/api/v1/users");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(3, $inspect->methods);
        foreach (["GET","HEAD","POST"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "UsersController", $inspect->methods[$m]);
        }
    }

    public function testInspectApiV1UsersId(): void
    {
        $inspect = $this->routes->inspect("/api/v1/users/:id");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(3, $inspect->methods);
        foreach (["DELETE","GET","PATCH"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "UsersController", $inspect->methods[$m]);
        }
    }

    public function testInspectApiV1Articles(): void
    {
        $inspect = $this->routes->inspect("/api/v1/articles");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(3, $inspect->methods);
        foreach (["GET","HEAD","POST"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ArticlesController", $inspect->methods[$m]);
        }
    }

    public function testInspectApiV1ArticlesSlug(): void
    {
        $inspect = $this->routes->inspect("/api/v1/articles/:slug");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ArticlesController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ArticlesController", $inspect->methods["HEAD"]);
    }

    public function testInspectApiV1ArticlesSlugComments(): void
    {
        $inspect = $this->routes->inspect("/api/v1/articles/:slug/comments");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(3, $inspect->methods);
        foreach (["GET","HEAD","POST"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "CommentsController", $inspect->methods[$m]);
        }
    }

    public function testInspectApiV1ArticlesSlugCommentsCommentId(): void
    {
        $inspect = $this->routes->inspect("/api/v1/articles/:slug/comments/:commentId");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "CommentsController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "CommentsController", $inspect->methods["HEAD"]);
    }

    public function testInspectApiV1SearchAny(): void
    {
        $inspect = $this->routes->inspect("/api/v1/search/:anyThing");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "SearchController", $inspect->methods["*"]);
    }

    public function testInspectApiV2Group(): void
    {
        $inspect = $this->routes->inspect("/api/v2");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectApiV2ReportsGroup(): void
    {
        $inspect = $this->routes->inspect("/api/v2/reports");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertFalse($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertNull($inspect->methods);
    }

    public function testInspectApiV2ReportsSummary(): void
    {
        $inspect = $this->routes->inspect("/api/v2/reports/summary");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(2, $inspect->methods);
        $this->assertArrayHasKey("GET", $inspect->methods);
        $this->assertArrayHasKey("HEAD", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ReportsController", $inspect->methods["GET"]);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ReportsController", $inspect->methods["HEAD"]);
    }

    public function testInspectApiV2ReportsYearMonthAny(): void
    {
        $inspect = $this->routes->inspect("/api/v2/reports/:year/:month");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "ReportsController", $inspect->methods["*"]);
    }

    public function testInspectAdmin(): void
    {
        $inspect = $this->routes->inspect("/admin");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AdminDashboardController", $inspect->methods["*"]);
    }

    public function testInspectAdminUsers(): void
    {
        $inspect = $this->routes->inspect("/admin/users");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AdminUsersController", $inspect->methods["*"]);
    }

    public function testInspectAdminUsersIdAny(): void
    {
        $inspect = $this->routes->inspect("/admin/users/:id");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertFalse($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(1, $inspect->methods);
        $this->assertArrayHasKey("*", $inspect->methods);
        $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AdminUsersController", $inspect->methods["*"]);
    }

    public function testInspectAdminUsersIdSettings(): void
    {
        $inspect = $this->routes->inspect("/admin/users/:id/settings");
        $this->assertInstanceOf(RouteInspect::class, $inspect);
        $this->assertTrue($inspect->isGroup);
        $this->assertTrue($inspect->isController);
        $this->assertNull($inspect->groupNamespace);
        $this->assertIsArray($inspect->methods);
        $this->assertCount(4, $inspect->methods);
        foreach (["GET","HEAD","PATCH","POST"] as $m) {
            $this->assertArrayHasKey($m, $inspect->methods);
            $this->assertEquals(RoutingFixtures::FAKE_NAMESPACE . "AdminUserSettingsController", $inspect->methods[$m]);
        }
    }
}

