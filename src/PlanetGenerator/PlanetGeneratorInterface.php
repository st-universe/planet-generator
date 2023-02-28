<?php

namespace Stu\PlanetGenerator;

use Generator;
use Stu\PlanetGenerator\Exception\PlanetGeneratorException;

interface PlanetGeneratorInterface
{
    /**
     *
     * @throws PlanetGeneratorException
     */
    public function loadColonyClassConfig(int $planetTypeId): array;

    /**
     * @return Generator<int>
     */
    public function getSupportedPlanetTypes(): Generator;

    /**
     * @throws PlanetGeneratorException
     */
    public function generateColony(
        int $planetTypeId,
        int $bonusFieldAmount
    ): GeneratedColonyConfigurationInterface;
}
