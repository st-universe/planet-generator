<?php

namespace Stu\PlanetGenerator;

use Stu\PlanetGenerator\Exception\PlanetGeneratorException;

interface PlanetGeneratorInterface
{
    /**
     *
     * @throws PlanetGeneratorException
     */
    public function loadColonyClassConfig(int $planetTypeId): array;

    /**
     * @return array{name: string, surfaceWidth: int, surfaceFields: array<int, int>}
     *
     * @throws PlanetGeneratorException
     */
    public function generateColony(
        int $planetTypeId,
        int $bonusFieldAmount
    ): array;
}
