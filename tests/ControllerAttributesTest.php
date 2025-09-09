<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server;

use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Enums\ControllerAttribute;
use Charcoal\Http\Server\Enums\RequestConstraint;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Routing\Builder\ControllersBuildCache;
use Charcoal\Http\Server\Routing\Snapshot\ControllerAttributes;
use Charcoal\Http\Tests\Server\Fixture\Controllers\AbstractBaseController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\BasicAttributeController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\ConcreteInheritanceController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\MethodAttributeController;
use Charcoal\Http\Tests\Server\Fixture\Controllers\MixedAttributeController;

/**
 * Unit tests for validating the behavior of controller attributes in various scenarios.
 * The tests ensure correct handling of class-level and method-specific attributes,
 * attribute inheritance, and lookup priority.
 */
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
        $attrSplId = spl_object_id($controller);

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

        // Request Constraints test
        $requestConstraints = $controller->getAttributeFor(ControllerAttribute::constraints, null);
        $this->assertEquals([
            RequestConstraint::maxParamLength->name => 123,
            RequestConstraint::maxBodyBytes->name => 456
        ], $requestConstraints);

        // getAggregatedAttributeFor + pseudo entrypoint
        $this->assertEquals([
            RequestConstraint::maxParamLength->name => 123,
            RequestConstraint::maxBodyBytes->name => 456
        ], $controller->getAggregatedAttributeFor(ControllerAttribute::constraints, "get"));

        // Is body disabled? (DisableRequestBody is not declared in this controller)
        $this->assertFalse($this->isBodyDisabled($controller, null));
        $this->assertFalse($this->isBodyDisabled($controller, "get"));
        $this->assertFalse($this->isBodyDisabled($controller, "post"));
        $this->assertFalse($this->isBodyDisabled($controller, "nonexistent"));

        // Rejects Unrecognized Params?
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, null));
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "get"));
        $this->assertFalse($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "post"));

        // Test cache control is not rebuilt for each request
        $this->assertEquals($attrSplId, spl_object_id($controller));
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

        // Is body disabled? (DisableRequestBody is not declared in this controller)
        $this->assertTrue($this->isBodyDisabled($controller, null));
        $this->assertTrue($this->isBodyDisabled($controller, "get"));
        $this->assertFalse($this->isBodyDisabled($controller, "post"));
        $this->assertTrue($this->isBodyDisabled($controller, "nonexistent"));

        // Rejects Unrecognized Params?
        // No class-level declaration, therefore NULL default
        $this->assertNull($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, null));
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "get"));
        $this->assertFalse($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "post"));
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

        // Request Constraints test
        $this->assertNull($this->getRequestConstraint($controller, RequestConstraint::maxParamLength));
        $this->assertEquals(12, $this->getRequestConstraint($controller, RequestConstraint::dtoMaxDepth));
        $this->assertEquals(4567, $this->getRequestConstraint($controller, RequestConstraint::maxBodyBytes));
        $this->assertEquals(789, $this->getRequestConstraint($controller, RequestConstraint::maxParams));

        // Rejects Unrecognized Params
    }

    public function testLookupPriority(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(MixedAttributeController::class, ["get", "post"]);

        // Test method-specific attribute takes priority
        $getParams = $controller->getAggregatedAttributeFor(ControllerAttribute::allowedParams, "get");
        $this->assertEquals(["common", "base", "method1", "class1", "class2"], $getParams);

        // Method's own declared + inherited from parent (does NOT include class-level)
        $getParams2 = $controller->getAttributeFor(ControllerAttribute::allowedParams, "get");
        $this->assertEquals(["common", "base", "method1"], $getParams2);

        // Test method without a specific attribute falls back to class then parent
        $postParams = $controller->getAggregatedAttributeFor(ControllerAttribute::allowedParams, "post");
        $this->assertIsArray($postParams);
        $this->assertNotContains("method1", $postParams);
        $this->assertContains("class1", $postParams);
        $this->assertContains("class1", $postParams);
        $this->assertContains("class2", $postParams);
        $this->assertContains("common", $postParams); // From parent
        $this->assertContains("base", $postParams);   // From parent

        // RejectUnrecognizedParams=true in parent class
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, null));
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "post"));
        $this->assertTrue($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "nonExistent"));

        // RejectUnrecognizedParams=false in method
        $this->assertFalse($controller->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, "get"));
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

        $this->assertTrue($this->isBodyDisabled($abstractController, null));

        $rejectsUnrecognizedParams = $abstractController->getAttributeFor(ControllerAttribute::rejectUnrecognizedParams, null);
        $this->assertTrue($rejectsUnrecognizedParams);
    }

    public function testNonExistentAttributes(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(BasicAttributeController::class, ["get"]);

        // Test non-existent attribute returns null
        $nonExistent = $controller->getAttributeFor(ControllerAttribute::disableRequestBody, null);
        $this->assertNull($nonExistent);
    }

    public function testParentAttributeLookupPath(): void
    {
        $cache = new ControllersBuildCache();
        $controller = $cache->resolve(ConcreteInheritanceController::class, ["get", "post", "put"]);

        // Test the full lookup path: method -> class -> parent method -> parent class
        // For "get" method, should fall back to parent attributes for allowedParams
        $parentOnlyAttribute = $controller->getAttributeFor(ControllerAttribute::allowedParams, "get");

        // Should find merged parent attributes since child doesn't override allowedParams
        $this->assertIsArray($parentOnlyAttribute);
        $this->assertCount(4, $parentOnlyAttribute);
        $this->assertContains("common", $parentOnlyAttribute);
        $this->assertContains("base", $parentOnlyAttribute);
        $this->assertContains("format", $parentOnlyAttribute);
        $this->assertContains("version", $parentOnlyAttribute);

        $this->assertTrue($this->isBodyDisabled($controller, null));
        $this->assertTrue($this->isBodyDisabled($controller, "get"));
        $this->assertFalse($this->isBodyDisabled($controller, "post"));
        $this->assertFalse($this->isBodyDisabled($controller, "put"));
        $this->assertTrue($this->isBodyDisabled($controller, "nonexistent"));
    }

    private function isBodyDisabled(ControllerAttributes $controller, ?string $entrypoint): bool
    {
        $disabled = $controller->getAttributeFor(ControllerAttribute::disableRequestBody, $entrypoint) ?? false;
        if ($disabled) {
            return !(($controller->getAttributeFor(ControllerAttribute::enableRequestBody, $entrypoint) === true));
        }

        return false;
    }

    private function getRequestConstraint(ControllerAttributes $controller, RequestConstraint $constraint): ?int
    {
        $constraints = $controller->getAttributeFor(ControllerAttribute::constraints, null);
        return $constraints[$constraint->name] ?? null;
    }
}
