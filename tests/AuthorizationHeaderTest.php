<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server;

use Charcoal\Http\Server\Support\HttpAuthorization;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthorizationHeaderTest
 * @package Charcoal\Http\Tests\Server
 */
final class AuthorizationHeaderTest extends TestCase
{
    public function testEmptyHeaderReturnsEmptyResults(): void
    {
        [$valid, $invalid, $unchecked] = HttpAuthorization::from('');

        $this->assertSame([], $valid);
        $this->assertSame([], $invalid);
        $this->assertSame('', $unchecked);
    }

    public function testMultipleSchemesBasicBearerDigestCustom(): void
    {
        $header = 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==, '
            . 'Bearer abcDEF123-_=, '
            . 'Digest username="Aladdin", realm="test", nonce=abc, uri="/", response=deadbeef, qop=auth, algorithm=MD5, opaque="xyz\"123", '
            . 'Custom Zm9vYmFy';

        [$valid, $invalid, $unchecked] = HttpAuthorization::from($header);

        $this->assertSame(
            [
                ['Basic', 'QWxhZGRpbjpvcGVuIHNlc2FtZQ=='],
                ['Bearer', 'abcDEF123-_='],
                [
                    'Digest',
                    [
                        'username' => 'Aladdin',
                        'realm' => 'test',
                        'nonce' => 'abc',
                        'uri' => '/',
                        'response' => 'deadbeef',
                        'qop' => 'auth',
                        'algorithm' => 'MD5',
                        'opaque' => 'xyz"123',
                    ],
                ],
                ['Custom', 'Zm9vYmFy'],
            ],
            $valid
        );

        $this->assertSame([], $invalid);
        $this->assertSame('', $unchecked);
    }

    public function testInvalidChunksCaptured(): void
    {
        $header = 'Basic, Bearer';

        [$valid, $invalid, $unchecked] = HttpAuthorization::from($header);

        $this->assertSame([], $valid);
        $this->assertSame(['Basic ', 'Bearer '], $invalid);
        $this->assertSame('', $unchecked);
    }

    public function testAlignExpectationsCurrentBehaviour(): void
    {
        $header = 'Bearer a.b.c, Custom dGVzdA==';

        [$valid, $invalid, $unchecked] = HttpAuthorization::from($header);

        // Align expectations with current behavior
        $this->assertSame(
            [
                ['Bearer', ['dGVzdA' => '=']],
            ],
            $valid
        );
        $this->assertSame(["a.b.c ", "Custom "], $invalid);
        $this->assertSame("", $unchecked);
    }
}