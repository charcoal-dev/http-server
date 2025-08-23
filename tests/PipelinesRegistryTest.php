<?php /** @noinspection PhpUndefinedClassInspection */
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;
use Charcoal\Http\Router\Middleware\MiddlewareConstructor;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Test case class for verifying the functionality of routes and controllers.
 *
 * This class sets up route mappings for an application, particularly within
 * an admin route group. It defines the structure, handlers, and methods
 * associated with various sub-routes, such as those related to users,
 * user-specific operations, and user security configurations.
 */
final class PipelinesRegistryTest extends \PHPUnit\Framework\TestCase
{
    private AppRoutes $routes;
    private AppRoutingSnapshot $routesDto;

    public function testSplObjectIds(): void
    {
        $this->assertSame(
            spl_object_id($this->routes->children[0]),
            spl_object_id($this->routes->inspect()->declared["/"][0]->children[0])
        );
    }

    public function testMiddlewareInheritance_Root(): void
    {
        $snap = $this->routesDto->inspect("/");
        $b = $snap->controllers[0];

        // No route-level pipeline at "/", so group-only; if your builder sets none, a bag is NULL
        self::assertNull($b->middleware);
    }

    public function testMiddlewareInheritance_Assets(): void
    {
        $snap = $this->routesDto->inspect("/assets/:anyThing");
        $b = $snap->controllers[0];

        // Root group + route pipeline (StaticCache), in order
        self::assertSame([
            StaticCache::class,
        ], $this->mwFlatten($b->middleware));
    }

    public function testMiddlewareInheritance_AdminUsersId(): void
    {
        $snap = $this->routesDto->inspect("/admin/users/:id");
        $b = $snap->controllers[0];

        // Root group + /admin group + route pipeline (ETag)
        self::assertSame([
            AdminAuth::class,
            AuditTrail::class,
            ETag::class,
        ], $this->mwFlatten($b->middleware));
    }

    public function testMiddlewareInheritance_ApiV1Users(): void
    {
        $snap = $this->routesDto->inspect("/api/v1/users");
        $b = $snap->controllers[0];

        // Root group + /api/v1 group (no route-level pipeline here)
        self::assertSame([
            ApiBase::class,
            JwtAuthGuard::class,
            RateLimiter::class,
        ], $this->mwFlatten($b->middleware));
    }

    /** @return list<class-string> */
    private function mwFlatten(?SealedBag $bag): array
    {
        if ($bag === null) {
            return [];
        }

        // Collect classnames from each MiddlewareConstructor in execution order
        /** @var list<MiddlewareConstructor> $ctors */
        $ctors = iterator_to_array($bag, false);
        return array_map(
            static fn (MiddlewareConstructor $c) => $c->classname,
            $ctors
        );
    }

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RoutingBuilderException
     * @noinspection PhpUndefinedClassInspection
     */
    public function setUp(): void
    {
        Router::toggleTestMode(true);
        $this->routes = new AppRoutes(function (RouteGroupBuilder $group): void {
            // /
            $group->route("/", HomeController::class)
                ->methods(HttpMethod::GET, HttpMethod::HEAD);

            // /assets/:anyThing
            $group->route("/assets/:anyThing", AssetsController::class)
                ->methods(HttpMethod::GET, HttpMethod::HEAD)
                ->pipelines(StaticCache::class);

            // /admin
            $group->group("/admin", function (RouteGroupBuilder $group): void {
                $group->pipelines(
                    AdminAuth::class,
                    AuditTrail::class,
                );

                $group->route("/", AdminHomeController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD);

                $group->route("/users", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);

                $group->route("/users/:id", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::PATCH, HttpMethod::DELETE)
                    ->pipelines(ETag::class);
            });

            // /api/v1
            $group->group("/api/v1", function (RouteGroupBuilder $group): void {
                $group->pipelines(
                    ApiBase::class,
                    JwtAuthGuard::class,
                    RateLimiter::class,
                );

                $group->route("/users", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);

                $group->route("/users/:id", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::PATCH, HttpMethod::DELETE);

                $group->route("/search/:anyThing", SearchController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD);
            });
        });

        $this->routesDto = $this->routes->snapshot();
    }
}