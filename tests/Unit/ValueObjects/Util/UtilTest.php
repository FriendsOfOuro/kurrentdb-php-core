<?php

declare(strict_types=1);

namespace KurrentDB\Tests\Unit\ValueObjects\Util;

use KurrentDB\ValueObjects\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function test_class_equals(): void
    {
        $util1 = new Util();
        $util2 = new Util();

        $this->assertTrue(Util::classEquals($util1, $util2));
        $this->assertFalse(Util::classEquals($util1, $this));
    }

    public function test_get_class_as_string(): void
    {
        $util = new Util();
        $this->assertEquals(Util::class, Util::getClassAsString($util));
    }
}
