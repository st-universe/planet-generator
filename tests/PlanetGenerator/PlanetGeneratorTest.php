<?php

declare(strict_types=1);

namespace Stu\PlanetGenerator;

class PlanetGeneratorTest extends StuTestCase
{
    private PlanetGeneratorInterface $subject;

    protected function setUp(): void
    {
        $this->subject = new PlanetGenerator();
    }

    public function testGenerateColonyForAllColData(): void
    {
        $typeIds = $this->subject->getSupportedPlanetTypes();

        foreach ($typeIds as $typeId) {
            $result = $this->subject->generateColony($typeId, 2);

            $this->assertEquals($result->getExpectedFieldCount(), count($result->getFieldArray()), sprintf('error in typeId: %d', $typeId));
        }
    }
}
