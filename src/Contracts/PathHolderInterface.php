<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

/**
 * Provides a contract for implementing classes to handle and return
 * a unique identifier that can be used to distinguish paths or related entities.
 */
interface PathHolderInterface
{
    public function getUniqueId(): string;
}