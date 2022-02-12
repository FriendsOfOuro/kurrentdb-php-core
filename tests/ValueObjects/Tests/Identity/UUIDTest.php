<?php
namespace EventStore\ValueObjects\Tests\Identity;

use EventStore\ValueObjects\Identity\UUID;
use EventStore\ValueObjects\Tests\TestCase;
use EventStore\ValueObjects\ValueObjectInterface;
use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;

class UUIDTest extends TestCase
{
    public function testGenerateAsString()
    {
        $uuidString = UUID::generateAsString();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidString);
    }

    public function testFromNative()
    {
        $uuid1 = new UUID();
        $uuid2 = UUID::fromNative($uuid1->toNative());

        $this->assertTrue($uuid1->sameValueAs($uuid2));
    }

    public function testSameValueAs()
    {
        $uuid1 = new UUID();
        $uuid2 = clone $uuid1;
        $uuid3 = new UUID();

        $this->assertTrue($uuid1->sameValueAs($uuid2));
        $this->assertFalse($uuid1->sameValueAs($uuid3));

        $mock = $this->createMock(ValueObjectInterface::class);
        $this->assertFalse($uuid1->sameValueAs($mock));
    }

    public function testInvalid()
    {
        $this->expectException(InvalidNativeArgumentException::class);
        new UUID('invalid');
    }
}
