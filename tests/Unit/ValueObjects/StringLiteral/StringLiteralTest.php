<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\ValueObjects\StringLiteral;

use KurrentDB\ValueObjects\StringLiteral\BasicStringLiteral;
use KurrentDB\ValueObjects\ValueObjectInterface;
use PHPUnit\Framework\TestCase;

class StringLiteralTest extends TestCase
{
    public function test_from_native(): void
    {
        $string = BasicStringLiteral::fromNative('foo');
        $constructedString = new BasicStringLiteral('foo');

        $this->assertTrue($string->sameValueAs($constructedString));
    }

    public function test_to_native(): void
    {
        $string = new BasicStringLiteral('foo');
        $this->assertEquals('foo', $string->toNative());
    }

    public function test_same_value_as(): void
    {
        $foo1 = new BasicStringLiteral('foo');
        $foo2 = new BasicStringLiteral('foo');
        $bar = new BasicStringLiteral('bar');

        $this->assertTrue($foo1->sameValueAs($foo2));
        $this->assertTrue($foo2->sameValueAs($foo1));
        $this->assertFalse($foo1->sameValueAs($bar));

        $mock = $this->createMock(ValueObjectInterface::class);
        $this->assertFalse($foo1->sameValueAs($mock));
    }

    public function test_is_empty(): void
    {
        $string = new BasicStringLiteral('');

        $this->assertTrue($string->isEmpty());
    }

    public function test_to_string(): void
    {
        $foo = new BasicStringLiteral('foo');
        $this->assertSame('foo', $foo->__toString());
    }
}
