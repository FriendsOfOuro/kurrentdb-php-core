<?php

namespace EventStore\ValueObjects\Tests\StringLiteral;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\StringLiteral\StringLiteral;
use EventStore\ValueObjects\Tests\TestCase;
use EventStore\ValueObjects\ValueObjectInterface;

class StringLiteralTest extends TestCase
{
    public function test_from_native(): void
    {
        $string = StringLiteral::fromNative('foo');
        $constructedString = new StringLiteral('foo');

        $this->assertTrue($string->sameValueAs($constructedString));
    }

    public function test_to_native(): void
    {
        $string = new StringLiteral('foo');
        $this->assertEquals('foo', $string->toNative());
    }

    public function test_same_value_as(): void
    {
        $foo1 = new StringLiteral('foo');
        $foo2 = new StringLiteral('foo');
        $bar = new StringLiteral('bar');

        $this->assertTrue($foo1->sameValueAs($foo2));
        $this->assertTrue($foo2->sameValueAs($foo1));
        $this->assertFalse($foo1->sameValueAs($bar));

        $mock = $this->createMock(ValueObjectInterface::class);
        $this->assertFalse($foo1->sameValueAs($mock));
    }

    public function test_invalid_native_argument(): void
    {
        $this->expectException(InvalidNativeArgumentException::class);
        new StringLiteral(12);
    }

    public function test_is_empty(): void
    {
        $string = new StringLiteral('');

        $this->assertTrue($string->isEmpty());
    }

    public function test_to_string(): void
    {
        $foo = new StringLiteral('foo');
        $this->assertSame('foo', $foo->__toString());
    }
}
