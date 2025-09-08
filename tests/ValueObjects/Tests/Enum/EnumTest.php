<?php

namespace EventStore\ValueObjects\Tests\Enum;

use EventStore\ValueObjects\Enum\Enum;
use EventStore\ValueObjects\Tests\TestCase;

class EnumTest extends TestCase
{
    public function test_same_value_as(): void
    {
        $stub1 = $this->createMock(Enum::class);
        $stub2 = $this->createMock(Enum::class);

        $stub1
              ->method('sameValueAs')
              ->willReturn(true);

        $this->assertTrue($stub1->sameValueAs($stub2));
    }

    public function test_to_string(): void
    {
        $stub = $this->createMock(Enum::class);

        $this->assertEquals('', $stub->__toString());
    }
}
