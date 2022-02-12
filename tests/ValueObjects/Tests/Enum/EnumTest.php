<?php
namespace EventStore\ValueObjects\Tests\Enum;

use EventStore\ValueObjects\Enum\Enum;
use EventStore\ValueObjects\Tests\TestCase;

class EnumTest extends TestCase
{
    public function test_same_value_as()
    {
        $stub1 = $this->createMock(Enum::class, [], [], '', false);
        $stub2 = $this->createMock(Enum::class, [], [], '', false);

        $stub1->expects($this->any())
              ->method('sameValueAs')
              ->will($this->returnValue(true));

        $this->assertTrue($stub1->sameValueAs($stub2));
    }

    public function test_to_string()
    {
        $stub = $this->createMock(Enum::class, [], [], '', false);

        $this->assertEquals('', $stub->__toString());
    }
}
