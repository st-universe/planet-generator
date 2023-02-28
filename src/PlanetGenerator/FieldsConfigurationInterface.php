<?php

namespace Stu\PlanetGenerator;

interface FieldsConfigurationInterface
{
    public function doPhase(array $phase): void;

    public function getHeight(): int;

    public function getWidth(): int;

    public function getFieldArray(): array;
}
