<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Fixture\Controllers;

use Charcoal\Http\Server\Attributes\AllowedParam;

#[AllowedParam(["class1", "class2"])]
final class MixedAttributeController extends AbstractBaseController
{
    #[AllowedParam(["method1"])]
    public function get()
    {
    }

    public function post()
    {
    } // Should inherit class + parent attributes
}
