<?php

namespace Stu\PlanetGenerator;

final class GeneratedColonyConfiguration implements GeneratedColonyConfigurationInterface
{
    private string $name;
    private int $surfaceWidth;
    private int $surfaceHeight;
    private bool $hasOrbit;
    private bool $hasUnderground;
    private array $fieldArray;

    public function __construct(
        string $name,
        int $surfaceWidth,
        int $surfaceHeight,
        bool $hasOrbit,
        bool $hasUnderground,
        array $fieldArray
    ) {
        $this->name = $name;
        $this->surfaceWidth = $surfaceWidth;
        $this->surfaceHeight = $surfaceHeight;
        $this->hasOrbit = $hasOrbit;
        $this->hasUnderground = $hasUnderground;
        $this->fieldArray = $fieldArray;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurfaceWidth(): int
    {
        return $this->surfaceWidth;
    }

    public function getSurfaceHeight(): int
    {
        return $this->surfaceHeight;
    }

    public function hasOrbit(): bool
    {
        return $this->hasOrbit;
    }

    public function hasUnderground(): bool
    {
        return $this->hasUnderground;
    }

    public function getFieldArray(): array
    {
        return $this->fieldArray;
    }

    public function getExpectedFieldCount(): int
    {
        $width = $this->getSurfaceWidth();
        $height = $this->getSurfaceHeight();

        if ($this->hasOrbit()) {
            $height += 2;
        }

        if ($this->hasUnderground()) {
            $height += 2;
        }

        return $width * $height;
    }
}
