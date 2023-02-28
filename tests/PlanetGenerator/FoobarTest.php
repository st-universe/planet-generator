<?php

declare(strict_types=1);

namespace Stu\PlanetGenerator;

class FoobarTest extends StuTestCase
{
    public function testClearUnicodeRemoveUnicode(): void
    {
        $subject = new PlanetGenerator();

        $result = $subject->generateColony(211, 2);

        echo print_r($result, true);

        $this->assertTrue(empty($result));
    }
}
