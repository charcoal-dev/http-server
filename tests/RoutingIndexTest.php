<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server;

use Charcoal\Http\Commons\Support\HttpMethods;
use Charcoal\Http\Server\Exceptions\RoutingBuilderException;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Routing\HttpRoutes;
use Charcoal\Http\Server\Routing\Group\RouteGroup;
use Charcoal\Http\Server\Routing\Registry\Route;
use Charcoal\Http\Server\Routing\Snapshot\RoutingSnapshot;
use Charcoal\Http\Tests\Server\Fixture\RoutingFixtures;

/**
 * Class RoutingTest
 */
final class RoutingIndexTest extends \PHPUnit\Framework\TestCase
{
    private readonly HttpRoutes $routes;
    private readonly RoutingSnapshot $routesDto;

    /**
     * @return void
     * @throws RoutingBuilderException
     */
    public function setUp(): void
    {
        HttpServer::$validateControllerClasses = false;
        $this->routes = RoutingFixtures::webBlogShipApi2AccountAdmin();
        $this->routesDto = $this->routes->snapshot();
    }

    /**
     * @param array $bindings
     * @return array
     */
    private function verbsFromBindings(array $bindings): array
    {
        $set = [];
        foreach ($bindings as $b) {
            foreach ($b->methods as $m) {
                $set[$m->name] = true;
            }
        }
        $out = array_keys($set);
        sort($out);
        return $out;
    }

    /**
     * @param array|HttpMethods|null $methods
     * @return array
     */
    private function httpMethodsToArray(null|array|HttpMethods $methods): array
    {
        $methods = is_array($methods) ? $methods : ($methods?->getArray() ?? []);
        return array_map(fn($m) => $m->name, $methods ?? []);
    }

    /**
     * @return void
     */
    public function testRoutesIndex(): void
    {
        $routes = $this->routes->inspect();

        // First, root AppRoutes group
        $this->assertArrayHasKey("/", $routes->declared);
        $this->assertCount(2, $routes->declared["/"]);
        $this->assertInstanceOf(HttpRoutes::class, $routes->declared["/"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/"][1]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/"][1]->methods)));

        // /about
        $this->assertArrayHasKey("/about", $routes->declared);
        $this->assertCount(1, $routes->declared["/about"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/about"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/about"][0]->methods)));

        // /assets/:anyThing
        $this->assertArrayHasKey("/assets/:anyThing", $routes->declared);
        $this->assertCount(1, $routes->declared["/assets/:anyThing"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/assets/:anyThing"][0]);

        // /web
        $this->assertArrayHasKey("/web", $routes->declared);
        $this->assertCount(1, $routes->declared["/web"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web"][0]);

        // /web/blog (merged group + index route)
        $this->assertArrayHasKey("/web/blog", $routes->declared);
        $this->assertCount(2, $routes->declared["/web/blog"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web/blog"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/blog"][1]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/web/blog"][1]->methods)));

        // /web/blog/archive/:year/:month
        $this->assertArrayHasKey("/web/blog/archive/:year/:month", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/blog/archive/:year/:month"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/blog/archive/:year/:month"][0]);

        // /web/blog/post/:slug
        $this->assertArrayHasKey("/web/blog/post/:slug", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/blog/post/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/blog/post/:slug"][0]);

        // /web/blog/post/:slug/edit (merged group + index route)
        $this->assertArrayHasKey("/web/blog/post/:slug/edit", $routes->declared);
        $this->assertCount(2, $routes->declared["/web/blog/post/:slug/edit"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web/blog/post/:slug/edit"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/blog/post/:slug/edit"][1]);
        $this->assertEquals("GET,HEAD,POST,PUT", implode(",", $this->httpMethodsToArray($routes->declared["/web/blog/post/:slug/edit"][1]->methods)));

        // /web/shop
        $this->assertArrayHasKey("/web/shop", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/shop"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web/shop"][0]);

        // /web/shop/products
        $this->assertArrayHasKey("/web/shop/products", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/shop/products"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/shop/products"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/web/shop/products"][0]->methods)));

        // /web/shop/products/:slug
        $this->assertArrayHasKey("/web/shop/products/:slug", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/shop/products/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/shop/products/:slug"][0]);

        // /web/shop/cart (merged group + index route)
        $this->assertArrayHasKey("/web/shop/cart", $routes->declared);
        $this->assertCount(2, $routes->declared["/web/shop/cart"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web/shop/cart"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/shop/cart"][1]);

        // /web/shop/cart/items/:id
        $this->assertArrayHasKey("/web/shop/cart/items/:id", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/shop/cart/items/:id"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/shop/cart/items/:id"][0]);
        $this->assertEquals("POST,DELETE", implode(",", $this->httpMethodsToArray($routes->declared["/web/shop/cart/items/:id"][0]->methods)));

        // /web/account
        $this->assertArrayHasKey("/web/account", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/account"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/web/account"][0]);

        // /web/account/login
        $this->assertArrayHasKey("/web/account/login", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/account/login"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/account/login"][0]);

        // /web/account/profile/:id
        $this->assertArrayHasKey("/web/account/profile/:id", $routes->declared);
        $this->assertCount(1, $routes->declared["/web/account/profile/:id"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/web/account/profile/:id"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/web/account/profile/:id"][0]->methods)));

        // /api
        $this->assertArrayHasKey("/api", $routes->declared);
        $this->assertCount(1, $routes->declared["/api"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api"][0]);

        // /api/v1
        $this->assertArrayHasKey("/api/v1", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v1"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v1"][0]);

        // /api/v1/users (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/users", $routes->declared);
        $this->assertCount(3, $routes->declared["/api/v1/users"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v1/users"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/users"][1]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/users"][1]->methods)));
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/users"][2]);
        $this->assertEquals("POST", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/users"][2]->methods)));

        // /api/v1/users/:id
        $this->assertArrayHasKey("/api/v1/users/:id", $routes->declared);
        $this->assertCount(3, $routes->declared["/api/v1/users/:id"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/users/:id"][0]);
        $this->assertEquals("GET", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/users/:id"][0]->methods)));
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/users/:id"][1]);
        $this->assertEquals("PATCH", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/users/:id"][1]->methods)));
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/users/:id"][2]);
        $this->assertEquals("DELETE", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/users/:id"][2]->methods)));

        // /api/v1/articles (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/articles", $routes->declared);
        $this->assertCount(2, $routes->declared["/api/v1/articles"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v1/articles"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/articles"][1]);
        $this->assertEquals("GET,HEAD,POST", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/articles"][1]->methods)));

        // /api/v1/articles/:slug
        $this->assertArrayHasKey("/api/v1/articles/:slug", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v1/articles/:slug"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/articles/:slug"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/articles/:slug"][0]->methods)));

        // /api/v1/articles/:slug/comments (merged group + index route with methods)
        $this->assertArrayHasKey("/api/v1/articles/:slug/comments", $routes->declared);
        $this->assertCount(2, $routes->declared["/api/v1/articles/:slug/comments"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v1/articles/:slug/comments"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/articles/:slug/comments"][1]);
        $this->assertEquals("GET,HEAD,POST", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/articles/:slug/comments"][1]->methods)));

        // /api/v1/articles/:slug/comments/:commentId
        $this->assertArrayHasKey("/api/v1/articles/:slug/comments/:commentId", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v1/articles/:slug/comments/:commentId"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/articles/:slug/comments/:commentId"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/api/v1/articles/:slug/comments/:commentId"][0]->methods)));

        // /api/v1/search/:anyThing
        $this->assertArrayHasKey("/api/v1/search/:anyThing", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v1/search/:anyThing"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v1/search/:anyThing"][0]);

        // /api/v2
        $this->assertArrayHasKey("/api/v2", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v2"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v2"][0]);

        // /api/v2/reports
        $this->assertArrayHasKey("/api/v2/reports", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v2/reports"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/api/v2/reports"][0]);

        // /api/v2/reports/summary
        $this->assertArrayHasKey("/api/v2/reports/summary", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v2/reports/summary"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v2/reports/summary"][0]);
        $this->assertEquals("GET,HEAD", implode(",", $this->httpMethodsToArray($routes->declared["/api/v2/reports/summary"][0]->methods)));

        // /api/v2/reports/:year/:month
        $this->assertArrayHasKey("/api/v2/reports/:year/:month", $routes->declared);
        $this->assertCount(1, $routes->declared["/api/v2/reports/:year/:month"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/api/v2/reports/:year/:month"][0]);

        // /admin (merged group + index route)
        $this->assertArrayHasKey("/admin", $routes->declared);
        $this->assertCount(2, $routes->declared["/admin"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/admin"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/admin"][1]);

        // /admin/users (merged group + index route)
        $this->assertArrayHasKey("/admin/users", $routes->declared);
        $this->assertCount(2, $routes->declared["/admin/users"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/admin/users"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/admin/users"][1]);

        // /admin/users/:id
        $this->assertArrayHasKey("/admin/users/:id", $routes->declared);
        $this->assertCount(1, $routes->declared["/admin/users/:id"]);
        $this->assertInstanceOf(Route::class, $routes->declared["/admin/users/:id"][0]);

        // /admin/users/:id/settings (merged group + index route with methods)
        $this->assertArrayHasKey("/admin/users/:id/settings", $routes->declared);
        $this->assertCount(2, $routes->declared["/admin/users/:id/settings"]);
        $this->assertInstanceOf(RouteGroup::class, $routes->declared["/admin/users/:id/settings"][0]);
        $this->assertInstanceOf(Route::class, $routes->declared["/admin/users/:id/settings"][1]);
        $this->assertEquals("GET,HEAD,POST,PATCH", implode(",", $this->httpMethodsToArray($routes->declared["/admin/users/:id/settings"][1]->methods)));
    }

    public function testInspectRoot(): void
    {
        $snap = $this->routesDto->inspect("/");
        self::assertSame("/", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\HomeController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
    }

    public function testInspectAbout(): void
    {
        $snap = $this->routesDto->inspect("/about");
        self::assertSame("/about", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\PageController", $b->controller->classname);
        self::assertSame(["GET", "HEAD"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectAssetsAny(): void
    {
        $snap = $this->routesDto->inspect("/assets/:anyThing");
        self::assertSame("/assets/:anyThing", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AssetsController", $b->controller->classname);
        self::assertTrue($b->methods);
        self::assertIsArray($b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("anyThing", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebGroup(): void
    {
        $snap = $this->routesDto->inspect("/web");
        self::assertSame("/web", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectWebBlog(): void
    {
        $snap = $this->routesDto->inspect("/web/blog");
        self::assertSame("/web/blog", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\BlogController", $b->controller->classname);
        self::assertSame(["GET", "HEAD"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectWebBlogArchiveAny(): void
    {
        $snap = $this->routesDto->inspect("/web/blog/archive/:year/:month");
        self::assertSame("/web/blog/archive/:year/:month", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\BlogController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(2, $snap->params);
        self::assertContains("year", $snap->params);
        self::assertContains("month", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebBlogPostSlugAny(): void
    {
        $snap = $this->routesDto->inspect("/web/blog/post/:slug");
        self::assertSame("/web/blog/post/:slug", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\BlogController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebBlogPostSlugEdit(): void
    {
        $snap = $this->routesDto->inspect("/web/blog/post/:slug/edit");
        self::assertSame("/web/blog/post/:slug/edit", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\BlogEditorController", $b->controller->classname);
        self::assertSame(["GET", "HEAD", "POST", "PUT"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head", "post", "put"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebShopGroup(): void
    {
        $snap = $this->routesDto->inspect("/web/shop");
        self::assertSame("/web/shop", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectWebShopProducts(): void
    {
        $snap = $this->routesDto->inspect("/web/shop/products");
        self::assertSame("/web/shop/products", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ProductsController", $b->controller->classname);
        self::assertSame(["GET", "HEAD"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectWebShopProductsSlugAny(): void
    {
        $snap = $this->routesDto->inspect("/web/shop/products/:slug");
        self::assertSame("/web/shop/products/:slug", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ProductsController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebShopCart(): void
    {
        $snap = $this->routesDto->inspect("/web/shop/cart");
        self::assertSame("/web/shop/cart", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\CartController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectWebShopCartItemsId(): void
    {
        $snap = $this->routesDto->inspect("/web/shop/cart/items/:id");
        self::assertSame("/web/shop/cart/items/:id", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\CartController", $b->controller->classname);
        self::assertSame(["POST", "DELETE"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["post", "delete"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("id", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectWebAccountGroup(): void
    {
        $snap = $this->routesDto->inspect("/web/account");
        self::assertSame("/web/account", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectWebAccountLoginAny(): void
    {
        $snap = $this->routesDto->inspect("/web/account/login");
        self::assertSame("/web/account/login", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AccountController", $b->controller->classname);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectWebAccountProfileId(): void
    {
        $snap = $this->routesDto->inspect("/web/account/profile/:id");
        self::assertSame("/web/account/profile/:id", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AccountController", $b->controller->classname);
        self::assertSame(["GET", "HEAD"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("id", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiGroup(): void
    {
        $snap = $this->routesDto->inspect("/api");
        self::assertSame("/api", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectApiV1Group(): void
    {
        $snap = $this->routesDto->inspect("/api/v1");
        self::assertSame("/api/v1", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectApiV1Users(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/users");
        self::assertSame("/api/v1/users", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);

        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\UsersController";

        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }

        // Path-specific verbs are the union of all bindings' methods:
        self::assertSame(["GET", "HEAD", "POST"], $this->verbsFromBindings($snap->controllers));

        self::assertNull($snap->params);
    }


    public function testInspectApiV1UsersId(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/users/:id");
        self::assertSame("/api/v1/users/:id", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\UsersController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }
        $verbsSet = [];
        foreach ($snap->controllers as $b) {
            foreach ($b->methods as $m) {
                $verbsSet[$m->name] = true;
            }
        }
        $verbs = array_keys($verbsSet);
        sort($verbs);
        self::assertSame(["DELETE", "GET", "PATCH"], $verbs);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("id", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiV1Articles(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/articles");
        self::assertSame("/api/v1/articles", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ArticlesController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }
        $verbsSet = [];
        foreach ($snap->controllers as $b) {
            foreach ($b->methods as $m) {
                $verbsSet[$m->name] = true;
            }
        }
        $verbs = array_keys($verbsSet);
        sort($verbs);
        self::assertSame(["GET", "HEAD", "POST"], $verbs);
        self::assertNull($snap->params);
    }

    public function testInspectApiV1ArticlesSlug(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/articles/:slug");
        self::assertSame("/api/v1/articles/:slug", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ArticlesController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }
        $verbsSet = [];
        foreach ($snap->controllers as $b) {
            foreach ($b->methods as $m) {
                $verbsSet[$m->name] = true;
            }
        }
        $verbs = array_keys($verbsSet);
        sort($verbs);
        self::assertSame(["GET", "HEAD"], $verbs);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiV1ArticlesSlugComments(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/articles/:slug/comments");
        self::assertSame("/api/v1/articles/:slug/comments", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\CommentsController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }
        $verbsSet = [];
        foreach ($snap->controllers as $b) {
            foreach ($b->methods as $m) {
                $verbsSet[$m->name] = true;
            }
        }
        $verbs = array_keys($verbsSet);
        sort($verbs);
        self::assertSame(["GET", "HEAD", "POST"], $verbs);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiV1ArticlesSlugCommentsCommentId(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/articles/:slug/comments/:commentId");
        self::assertSame("/api/v1/articles/:slug/comments/:commentId", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\CommentsController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }
        $verbsSet = [];
        foreach ($snap->controllers as $b) {
            foreach ($b->methods as $m) {
                $verbsSet[$m->name] = true;
            }
        }
        $verbs = array_keys($verbsSet);
        sort($verbs);
        self::assertSame(["GET", "HEAD"], $verbs);
        self::assertIsArray($snap->params);
        self::assertCount(2, $snap->params);
        self::assertContains("slug", $snap->params);
        self::assertContains("commentId", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiV1SearchAny(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/search/:anyThing");
        self::assertSame("/api/v1/search/:anyThing", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $expectedClass = rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\SearchController";
        foreach ($snap->controllers as $b) {
            self::assertSame($expectedClass, $b->controller->classname);
            self::assertNull($b->controller->defaultEntrypoint);
        }

        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("anyThing", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectApiV2Group(): void
    {
        $snap = $this->routesDto->inspect("/api/v2");
        self::assertSame("/api/v2", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectApiV2ReportsGroup(): void
    {
        $snap = $this->routesDto->inspect("/api/v2/reports");
        self::assertSame("/api/v2/reports", $snap->path);
        self::assertCount(0, $snap->controllers);
        self::assertNull($snap->params);
    }

    public function testInspectApiV2ReportsSummary(): void
    {
        $snap = $this->routesDto->inspect("/api/v2/reports/summary");
        self::assertSame("/api/v2/reports/summary", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ReportsController", $b->controller->classname);
        self::assertSame(["GET", "HEAD"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectApiV2ReportsYearMonthAny(): void
    {
        $snap = $this->routesDto->inspect("/api/v2/reports/:year/:month");
        self::assertSame("/api/v2/reports/:year/:month", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\ReportsController", $b->controller->classname);
        $verbs = $this->httpMethodsToArray($b->methods);
        self::assertGreaterThanOrEqual(2, $verbs);
        self::assertContains("GET", $verbs);
        self::assertContains("HEAD", $verbs);
        self::assertIsArray($b->controller->entryPoints);
        self::assertContains("get", $b->controller->entryPoints);
        self::assertContains("head", $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(2, $snap->params);
        self::assertContains("year", $snap->params);
        self::assertContains("month", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectAdmin(): void
    {
        $snap = $this->routesDto->inspect("/admin");
        self::assertSame("/admin", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AdminDashboardController", $b->controller->classname);
        self::assertTrue($b->methods);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectAdminUsers(): void
    {
        $snap = $this->routesDto->inspect("/admin/users");
        self::assertSame("/admin/users", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AdminUsersController", $b->controller->classname);
        self::assertTrue($b->methods);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertNull($snap->params);
    }

    public function testInspectAdminUsersIdAny(): void
    {
        $snap = $this->routesDto->inspect("/admin/users/:id");
        self::assertSame("/admin/users/:id", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AdminUsersController", $b->controller->classname);
        self::assertTrue($b->methods);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("id", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }

    public function testInspectAdminUsersIdSettings(): void
    {
        $snap = $this->routesDto->inspect("/admin/users/:id/settings");
        self::assertSame("/admin/users/:id/settings", $snap->path);
        self::assertGreaterThan(0, $snap->controllers);
        $b = $snap->controllers[0];
        self::assertSame(rtrim(RoutingFixtures::FAKE_NAMESPACE, "\\") . "\\AdminUserSettingsController", $b->controller->classname);
        self::assertSame(["GET", "HEAD", "POST", "PATCH"], array_map(fn($m) => $m->name, $b->methods));
        self::assertSame(["get", "head", "post", "patch"], $b->controller->entryPoints);
        self::assertNull($b->controller->defaultEntrypoint);
        self::assertIsArray($snap->params);
        self::assertCount(1, $snap->params);
        self::assertContains("id", $snap->params);
        self::assertIsString($snap->matchRegExp);
        self::assertNotSame("", $snap->matchRegExp);
        self::assertStringContainsString("([^/]+)", $snap->matchRegExp);
    }
}