<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router\Fixture;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;

/**
 * Class RoutingFixtures
 * @package Charcoal\Http\Tests\Router\Fixture
 */
final class RoutingFixtures
{
    public const string FAKE_NAMESPACE = "Charcoal\Http\Tests\Router\Fixture\\";

    /**
     * @throws RoutingBuilderException
     * @noinspection PhpUndefinedClassInspection
     */
    public static function webBlogShipApi2AccountAdmin(): AppRoutes
    {
        return new AppRoutes(function (RouteGroupBuilder $group) {
            // Top-level
            $group->route("/about", PageController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
            $group->route("/assets/:anyThing", AssetsController::class);
            $group->route("/", HomeController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

            // Web area
            $group->group("/web", function (RouteGroupBuilder $group) {
                // Blog
                $group->group("/blog", function (RouteGroupBuilder $group) {
                    $group->route("/", BlogController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                    $group->route("/archive/:year/:month", BlogController::class);
                    $group->route("/post/:slug", BlogController::class);

                    $group->group("/post/:slug/edit", function (RouteGroupBuilder $group) {
                        $group->route("/", BlogEditorController::class)
                            ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST, HttpMethod::PUT);
                    });
                });

                // Account
                $group->group("/account", function (RouteGroupBuilder $group) {
                    $group->route("/login", AccountController::class);
                    $group->route("/profile/:id", AccountController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                });

                // Shop
                $group->group("/shop", function (RouteGroupBuilder $group) {
                    $group->route("/products", ProductsController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                    $group->route("/products/:slug", ProductsController::class);

                    $group->group("/cart", function (RouteGroupBuilder $group) {
                        $group->route("/", CartController::class);
                        $group->route("/items/:id", CartController::class)
                            ->methods(HttpMethod::POST, HttpMethod::DELETE);
                    });
                });
            });

            // Admin area
            $group->group("/admin", function (RouteGroupBuilder $group) {
                $group->route("/", AdminDashboardController::class);

                $group->group("/users", function (RouteGroupBuilder $group) {
                    $group->route("/", AdminUsersController::class);
                    $group->route("/:id", AdminUsersController::class);
                    $group->group("/:id/settings", function (RouteGroupBuilder $group) {
                        $group->route("/", AdminUserSettingsController::class)
                            ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST, HttpMethod::PATCH);
                    });
                });
            });

            // API v1
            $group->group("/api", function (RouteGroupBuilder $group) {
                $group->group("/v1", function (RouteGroupBuilder $group) {
                    $group->group("/users", function (RouteGroupBuilder $group) {
                        $group->route("/", UsersController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                        $group->route("/", UsersController::class)->methods(HttpMethod::POST);
                        $group->route("/:id", UsersController::class)->methods(HttpMethod::GET);
                        $group->route("/:id", UsersController::class)->methods(HttpMethod::PATCH);
                        $group->route("/:id", UsersController::class)->methods(HttpMethod::DELETE);
                    });

                    $group->group("/articles", function (RouteGroupBuilder $group) {
                        $group->route("/", ArticlesController::class)
                            ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);
                        $group->route("/:slug", ArticlesController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);

                        $group->group("/:slug/comments", function (RouteGroupBuilder $group) {
                            $group->route("/", CommentsController::class)
                                ->methods(HttpMethod::GET, HttpMethod::HEAD, HttpMethod::POST);
                            $group->route("/:commentId", CommentsController::class)
                                ->methods(HttpMethod::GET, HttpMethod::HEAD);
                        });
                    });

                    $group->route("/search/:anyThing", SearchController::class);
                });

                // API v2
                $group->group("/v2", function (RouteGroupBuilder $group) {
                    $group->group("/reports", function (RouteGroupBuilder $group) {
                        $group->route("/summary", ReportsController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                        $group->route("/:year/:month", ReportsController::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
                    });
                });
            });
        });
    }
}