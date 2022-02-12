<?php
namespace EventStore\ValueObjects\Tests\Identity;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\Identity\UUID;
use EventStore\ValueObjects\Tests\TestCase;
use EventStore\ValueObjects\ValueObjectInterface;

class UUIDTest extends TestCase
{
    public function test_generate_as_string()
    {
        $uuidString = UUID::generateAsString();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidString);
    }

    public function test_from_native()
    {
        $uuid1 = new UUID();
        $uuid2 = UUID::fromNative($uuid1->toNative());

        $this->assertTrue($uuid1->sameValueAs($uuid2));
    }

    public function test_same_value_as()
    {
        $uuid1 = new UUID();
        $uuid2 = clone $uuid1;
        $uuid3 = new UUID();

        $this->assertTrue($uuid1->sameValueAs($uuid2));
        $this->assertFalse($uuid1->sameValueAs($uuid3));

        $mock = $this->createMock(ValueObjectInterface::class);
        $this->assertFalse($uuid1->sameValueAs($mock));
    }

    public function test_invalid()
    {
        $this->expectException(InvalidNativeArgumentException::class);
        new UUID('invalid');
    }
}
