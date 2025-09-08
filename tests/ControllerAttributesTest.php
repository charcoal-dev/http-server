<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server;

use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Enums\ControllerAttribute;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Routing\Builder\ControllersBuildCache;
use Charcoal\Http\Tests\Server\Fixture\Controllers\AbstractBaseController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\BasicAttributeController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\ConcreteInheritanceController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\MethodAttributeController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\MixedAttributeController;

final class ControllerAttributesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        HttpServer::$validateControllerClasses = true;
    }

    public function testBasicClassAttributes(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(BasicAttributeController::class, ["get", "post"]);

        // Test class-level cache control
        $cacheControl = $controller->getAttributeFor(ControllerAttribute::cacheControl, null);
        $this->assertInstanceOf(CacheControlDirectives::class, $cacheControl);
        $this->assertContains("public", $cacheControl->directives);
        $this->assertContains("max-age=300", $cacheControl->directives);

        // Test class-level allowed params
        $allowedParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, null);
        $this->assertEquals(["id", "name"], $allowedParams);

        // Test method inherits class attributes
        $methodCache = $controller->getAttributeFor(ControllerAttribute::cacheControl, "get");
        $this->assertInstanceOf(CacheControlDirectives::class, $methodCache);
        $this->assertContains("public", $methodCache->directives);
        $this->assertContains("max-age=300", $methodCache->directives);
    }

    public function testMethodAttributeOverrides(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(MethodAttributeController::class, ["get", "post"]);

        // Test method-specific cache control
        $getCache = $controller->getAttributeFor(ControllerAttribute::cacheControl, "get");
        $postCache = $controller->getAttributeFor(ControllerAttribute::cacheControl, "post");

        $this->assertInstanceOf(CacheControlDirectives::class, $getCache);
        $this->assertInstanceOf(CacheControlDirectives::class, $postCache);
        $this->assertNotEquals($getCache->directives, $postCache->directives);

        // GET should have public cache with max-age=3600
        $this->assertContains("public", $getCache->directives);
        $this->assertContains("max-age=3600", $getCache->directives);

        // POST should have no-store
        $this->assertEquals(["no-store", "no-cache", "must-revalidate"], $postCache->directives);

        // Test method-specific allowed params
        $postParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, "post");
        $this->assertEquals(["filter"], $postParams);

        // Test method without attributes returns null
        $getParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, "get");
        $this->assertNull($getParams);
    }

    public function testInheritanceChain(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(ConcreteInheritanceController::class, ["get", "post"]);

        // Test child class overrides parent cache control
        $classCache = $controller->getAttributeFor(ControllerAttribute::cacheControl, null);
        $this->assertInstanceOf(CacheControlDirectives::class, $classCache);
        $this->assertContains("private", $classCache->directives);
        $this->assertContains("no-cache", $classCache->directives);

        // Test parent allowed params are inherited and merged
        $allowedParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, null);
        $this->assertIsArray($allowedParams);
        $this->assertContains("common", $allowedParams);
        $this->assertContains("base", $allowedParams);
        $this->assertContains("format", $allowedParams);
        $this->assertContains("version", $allowedParams);

        // Test method overrides everything
        $postCache = $controller->getAttributeFor(ControllerAttribute::cacheControl, "post");
        $this->assertInstanceOf(CacheControlDirectives::class, $postCache);
        $this->assertContains("private", $postCache->directives);
        $this->assertContains("max-age=1800", $postCache->directives);
        $this->assertNotContains("no-cache", $postCache->directives); // Method doesn't have no-cache
    }

    public function testLookupPriority(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(MixedAttributeController::class, ["get", "post"]);

        // Test method-specific attribute takes priority
        $getParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, "get");
        $this->assertEquals(["method1"], $getParams);

        // Test method without a specific attribute falls back to class then parent
        $postParams = $controller->getAttributeFor(ControllerAttribute::allowedParams, "post");
        $this->assertIsArray($postParams);
        $this->assertContains("class1", $postParams);
        $this->assertContains("class2", $postParams);
        $this->assertContains("common", $postParams); // From parent
        $this->assertContains("base", $postParams);   // From parent
    }

    public function testAbstractClassAttributes(): void
    {
        $cache = new ControllersBuildCache();

        // Test abstract classes can be resolved (for inheritance)
        $abstractController = $cache->resolve(AbstractBaseController::class, null);
        $this->assertNotNull($abstractController);

        $cacheControl = $abstractController->getAttributeFor(ControllerAttribute::cacheControl, null);
        $this->assertInstanceOf(CacheControlDirectives::class, $cacheControl);
        $this->assertContains("public", $cacheControl->directives);
        $this->assertContains("max-age=600", $cacheControl->directives);
    }

    public function testNonExistentAttributes(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(BasicAttributeController::class, ["get"]);

        // Test non-existent attribute returns null
        $nonExistent = $controller->getAttributeFor(ControllerAttribute::disableRequestBody, null);
        $this->assertNull($nonExistent);

        // Test non-existent method returns null
        $nonExistentMethod = $controller->getAttributeFor(ControllerAttribute::cacheControl, "nonexistent");
        $this->assertNull($nonExistentMethod);
    }

    public function testParentAttributeLookupPath(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(ConcreteInheritanceController::class, ["get"]);

        // Test the full lookup path: method -> class -> parent method -> parent class
        // For "get" method, should fall back to parent attributes for allowedParams
        $parentOnlyAttribute = $controller->getAttributeFor(ControllerAttribute::allowedParams, "get");

        // Should find merged parent attributes since child doesn"t override allowedParams
        $this->assertIsArray($parentOnlyAttribute);
        $this->assertContains("common", $parentOnlyAttribute);
        $this->assertContains("base", $parentOnlyAttribute);
        $this->assertContains("format", $parentOnlyAttribute);
        $this->assertContains("version", $parentOnlyAttribute);
    }
}
