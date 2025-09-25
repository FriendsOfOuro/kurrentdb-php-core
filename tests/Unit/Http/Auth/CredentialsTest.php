<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\Http\Auth;

use KurrentDB\Http\Auth\Credentials;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class CredentialsTest extends TestCase
{
    #[Test]
    #[TestWith([''])]
    #[TestWith(['gregorio'])]
    #[TestWith(['gregorio:secret'])]
    public function back_and_forth(string $credentials): void
    {
        $boxed = Credentials::fromString($credentials);

        $this->assertSame($credentials, (string) $boxed);
    }

    #[Test]
    #[TestWith(['', new Credentials('')])]
    #[TestWith(['gregorio', new Credentials('gregorio')])]
    #[TestWith(['gregorio:secret', new Credentials('gregorio', 'secret')])]
    public function boxing(string $credentials, Credentials $expected): void
    {
        $this->assertEquals($expected, Credentials::fromString($credentials));
    }
}
