<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Router\Contracts\AuthRealmInterface;

/**
 * Class AbstractAuthorization
 * @package Charcoal\Http\Router\Authorization
 */
abstract class AbstractAuthorization
{
    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param AuthRealmInterface $realm
     */
    public function __construct(protected readonly AuthRealmInterface $realm)
    {
    }
}
