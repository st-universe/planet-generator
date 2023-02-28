<?php

namespace Stu\PlanetGenerator;

interface GeneratedColonyConfigurationInterface
{
    public function getName(): string;

    public function getSurfaceWidth(): int;

    public function getSurfaceHeight(): int;

    public function hasOrbit(): bool;

    public function hasUnderground(): bool;

    public function getFieldArray(): array;

    public function getExpectedFieldCount(): int;
}
