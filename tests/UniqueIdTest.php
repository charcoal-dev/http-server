<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;

/**
 * Test case class for verifying the functionality of routes and controllers.
 *
 * This class sets up route mappings for an application, particularly within
 * an admin route group. It defines the structure, handlers, and methods
 * associated with various sub-routes, such as those related to users,
 * user-specific operations, and user security configurations.
 */
final class UniqueIdTest extends \PHPUnit\Framework\TestCase
{
    private AppRoutes $routes;

    public function testSplObjectIds(): void
    {
        $this->assertSame(
            spl_object_id($this->routes->children[0]),
            spl_object_id($this->routes->inspect()->declared["/admin"][0])
        );
    }

    public function uniqueIdOnInstance(): void
    {
        // Todo: write test
    }

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RoutingBuilderException
     * @noinspection PhpUndefinedClassInspection
     */
    public function setUp(): void
    {
        Router::$checkControllerExists = false;
        $this->routes = new AppRoutes(function (RouteGroupBuilder $group) {
            $group->group("/admin", function (RouteGroupBuilder $group) {
                $group->route("/", AdminHomeController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

                $group->group("/users", function (RouteGroupBuilder $group) {
                    $group->route("/", AdminUsersController::class);

                    $group->group("/:userId", function (RouteGroupBuilder $group) {
                        $group->route("/", AdminUserController::class)
                            ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::PUT, HttpMethod::DELETE);

                        $group->group("/security", function (RouteGroupBuilder $group) {
                            $group->route("/", UserSecurityController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

                            $group->group("/2fa", function (RouteGroupBuilder $group) {
                                $group->route("/", UserTwoFaController::class)
                                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST, HttpMethod::DELETE);
                                $group->route("/recovery", UserRecoveryCodesController::class)
                                    ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);
                            });
                        });
                    });
                });
            });

            $group->group("/store", function (RouteGroupBuilder $group) {
                $group->route("/", StoreHomeController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

                $group->group("/products", function (RouteGroupBuilder $group) {
                    $group->route("/", ProductsController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

                    $group->group("/:sku", function (RouteGroupBuilder $group) {
                        $group->route("/", ProductController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                        $group->route("/reviews", ProductReviewsController::class)
                            ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);
                    });
                });
            });
        });
    }
}