<?php

declare(strict_types=1);

namespace Stu\PlanetGenerator;

use Stu\StuTestCase;

class FoobarTest extends StuTestCase
{
    public function testClearUnicodeRemoveUnicode(): void
    {
        $subject = new PlanetGenerator();

        $result = $subject->generateColony(211, 2);

        echo print_r($result, true);

        $this->assertFalse(empty($result));
    }
}
