<?php
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
use PHPUnit\Framework\TestCase;

/**
 * These tests focus on verifying that:
 * 1) Group pipelines are inherited in order, and route-level pipelines are appended after group-level ones.
 * 2) The same controller class used under different groups receives the correct middleware for its context (no leakage).
 * 3) Routes that have only route-level pipelines (no groups) still report correct order.
 * 4) The root path with no pipelines yields no middleware.
 */
final class MiddlewareRegistryBehaviorTest extends TestCase
{
    private AppRoutingSnapshot $snapshot;

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RoutingBuilderException
     * @noinspection PhpUndefinedClassInspection
     */
    protected function setUp(): void
    {
        Router::toggleTestMode(true);

        // Build a small app tree that covers:
        // - root with no pipelines
        // - route-only pipeline
        // - nested group with route-level pipeline
        // - another group tree using the same controller class to check isolation
        $routes = new AppRoutes(function (RouteGroupBuilder $group): void {
            // /
            $group->route("/", HomeController::class)
                ->methods(HttpMethod::GET, HttpMethod::HEAD);

            // /assets/:anyThing (route-only middleware)
            $group->route("/assets/:anyThing", AssetsController::class)
                ->methods(HttpMethod::GET, HttpMethod::HEAD)
                ->pipelines(StaticCache::class);

            // /admin (group-level)
            $group->group("/admin", function (RouteGroupBuilder $group): void {
                $group->pipelines(
                    AdminAuth::class,
                    AuditTrail::class,
                );

                $group->route("/", AdminHomeController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD);

                // Same controller as in another group on purpose for isolation tests
                $group->route("/users", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);

                // Route-level pipeline appended after group-level
                $group->route("/users/:id", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::PATCH, HttpMethod::DELETE)
                    ->pipelines(ETag::class);
            });

            // /api/v1 (another group tree using the same UsersController to verify no leakage)
            $group->group("/api/v1", function (RouteGroupBuilder $group): void {
                $group->pipelines(
                    ApiBase::class,
                    JwtAuthGuard::class,
                    RateLimiter::class,
                );

                // Same controller class as in /admin group
                $group->route("/users", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);

                $group->route("/users/:id", UsersController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::PATCH, HttpMethod::DELETE);

                $group->route("/search/:anyThing", SearchController::class)
                    ->methods(HttpMethod::GET, HttpMethod::HEAD);
            });
        });

        $this->snapshot = $routes->snapshot();
    }

    public function testRootHasNoPipelines(): void
    {
        $snap = $this->snapshot->inspect("/");
        $controllerDto = $snap->controllers[0];

        self::assertNull($controllerDto->middleware);
        self::assertSame([], $this->flatten($controllerDto->middleware));
    }

    /** @noinspection PhpUndefinedClassInspection */
    public function testRouteOnlyPipeline_Assets(): void
    {
        $snap = $this->snapshot->inspect("/assets/:anyThing");
        $controllerDto = $snap->controllers[0];

        self::assertSame([
            StaticCache::class,
        ], $this->flatten($controllerDto->middleware));
    }

    /** @noinspection PhpUndefinedClassInspection */
    public function testGroupOnlyPipeline_AdminUsers(): void
    {
        $snap = $this->snapshot->inspect("/admin/users");
        $controllerDto = $snap->controllers[0];

        // Only group-level (no route-level pipelines)
        self::assertSame([
            AdminAuth::class,
            AuditTrail::class,
        ], $this->flatten($controllerDto->middleware));
    }

    /** @noinspection PhpUndefinedClassInspection */
    public function testGroupPlusRoutePipelineOrder_AdminUsersId(): void
    {
        $snap = $this->snapshot->inspect("/admin/users/:id");
        $controllerDto = $snap->controllers[0];

        // Group-level (in declared order) then route-level (ETag)
        self::assertSame([
            AdminAuth::class,
            AuditTrail::class,
            ETag::class,
        ], $this->flatten($controllerDto->middleware));
    }

    /** @noinspection PhpUndefinedClassInspection */
    public function testIsolation_SameControllerDifferentGroups_NoLeakage(): void
    {
        // Same controller class under /admin group
        $admin = $this->snapshot->inspect("/admin/users");
        $adminC = $admin->controllers[0];

        // Same controller class under /api/v1 group
        $api = $this->snapshot->inspect("/api/v1/users");
        $apiC = $api->controllers[0];

        // Verify distinct pipelines for the same controller class in different group contexts
        self::assertSame([
            AdminAuth::class,
            AuditTrail::class,
        ], $this->flatten($adminC->middleware));

        self::assertSame([
            ApiBase::class,
            JwtAuthGuard::class,
            RateLimiter::class,
        ], $this->flatten($apiC->middleware));
    }

    /** @noinspection PhpUndefinedClassInspection */
    public function testRouteUnderApiV1_UsersId_InheritsGroupOnly(): void
    {
        $snap = $this->snapshot->inspect("/api/v1/users/:id");
        $controllerDto = $snap->controllers[0];

        // No route-level pipelines declared for this one; inherits api group
        self::assertSame([
            ApiBase::class,
            JwtAuthGuard::class,
            RateLimiter::class,
        ], $this->flatten($controllerDto->middleware));
    }

    /**
     * Helper: flatten MiddlewareConstructor bag to a class-string list in declared execution order.
     *
     * @return list<class-string>
     */
    private function flatten(?SealedBag $bag): array
    {
        if ($bag === null) {
            return [];
        }

        /** @var list<MiddlewareConstructor> $ctors */
        $ctors = iterator_to_array($bag, false);
        return array_map(
            static fn (MiddlewareConstructor $c) => $c->classname,
            $ctors
        );
    }
}