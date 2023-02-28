<?php

namespace Stu\PlanetGenerator;

final class FieldsConfiguration implements FieldsConfigurationInterface
{
    private array $fieldArray = [];
    private int $height;
    private int $width;

    public function __construct(int $height, int $width)
    {
        $this->height = $height;
        $this->width = $width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getFieldArray(): array
    {
        return $this->fieldArray;
    }

    public function initBaseFields(int $baseFieldType): void
    {
        for ($i = 0; $i < $this->getHeight(); $i++) {
            for ($j = 0; $j < $this->getWidth(); $j++) {
                $this->setFieldValue($j, $i, $baseFieldType);
            }
        }
    }

    /**
     * @param array{
     *  mode: string,
     *  num: int,
     *  from: array<int, int>,
     *  to: array<int, int>,
     *  adjacent: int,
     *  noadjacent: int,
     *  noadjacentlimit: int,
     *  fragmentation: int
     * } $phase
     *
     */
    public function doPhase(array $phase): void
    {
        if ($phase[PlanetGenerator::COLGEN_MODE] == "fullsurface") {
            $k = 0;
            for ($ih = 0; $ih < $this->getHeight(); $ih++) {
                for ($iw = 0; $iw < $this->getWidth(); $iw++) {

                    $k++;

                    $this->setFieldValue($iw, $ih, $phase[PlanetGenerator::COLGEN_TYPE] * 100 + $k);
                }
            }
        } else {
            for ($i = 0; $i < $phase[PlanetGenerator::COLGEN_NUM]; $i++) {
                $arr = $this->getWeightingList(
                    $phase[PlanetGenerator::COLGEN_MODE],
                    $phase[PlanetGenerator::COLGEN_FROM],
                    $phase[PlanetGenerator::COLGEN_TO],
                    $phase[PlanetGenerator::COLGEN_ADJACENT],
                    $phase[PlanetGenerator::COLGEN_NOADJACENT],
                    $phase[PlanetGenerator::COLGEN_NOADJACENTLIMIT]
                );
                if ($arr === null || count($arr) == 0) {
                    break;
                }

                $field = $this->weightedDraw($arr, $phase[PlanetGenerator::COLGEN_FRAGMENTATION]);
                $ftype = $this->getFieldArray()[$field[PlanetGenerator::COLGEN_X]][$field[PlanetGenerator::COLGEN_Y]];

                $t = 0;
                unset($ta);
                for ($c = 0; $c < count($phase[PlanetGenerator::COLGEN_FROM]); $c++) {

                    if ($ftype == $phase[PlanetGenerator::COLGEN_FROM][$c]) {
                        $ta[$t] = $phase[PlanetGenerator::COLGEN_TO][$c];
                        $t++;
                    }
                }
                if ($t > 0) {
                    $this->setFieldValue($field[PlanetGenerator::COLGEN_X], $field[PlanetGenerator::COLGEN_Y], $ta[rand(0, $t - 1)]);
                }
            }
        }
    }

    private function setFieldValue(int $w, int $h, int $fieldType): void
    {
        $this->fieldArray[$w][$h] = $fieldType;
    }

    private function weightedDraw(array $a, int $fragmentation = 0): array
    {
        for ($i = 0; $i < count($a); $i++) {
            $a[$i][PlanetGenerator::COLGEN_WEIGHT] = rand(1, (int) ceil($a[$i][PlanetGenerator::COLGEN_BASEWEIGHT] + $fragmentation));
        }
        usort($a, function ($a, $b) {
            if ($a[PlanetGenerator::COLGEN_WEIGHT] < $b[PlanetGenerator::COLGEN_WEIGHT]) {
                return +1;
            }
            if ($a[PlanetGenerator::COLGEN_WEIGHT] > $b[PlanetGenerator::COLGEN_WEIGHT]) {
                return -1;
            }
            return (rand(1, 3) - 2);
        });

        return $a[0];
    }

    /**
     * @param int|array<int, int> $adjacent
     * @param int|array<int, int> $no_adjacent
     *
     * @return array|null
     */
    private function getWeightingList(
        string $mode,
        array $from,
        array $to,
        $adjacent,
        $no_adjacent,
        int $noadjacentlimit = 0
    ): ?array {
        $res = null;

        $c = 0;
        for ($h = 0; $h < $this->getHeight(); $h++) {
            for ($w = 0; $w < $this->getWidth(); $w++) {

                //check if field is FROM
                if (!in_array($this->getFieldArray()[$w][$h], $from)) {
                    continue;
                }

                //and now?
                $bw = 1;
                if ((($mode == GeneratorModeEnum::POLAR) || ($mode == GeneratorModeEnum::STRICT_POLAR)) && ($h == 0 || $h == $this->getHeight() - 1)) {
                    $bw += 1;
                }
                if (($mode == GeneratorModeEnum::TOP_LEFT) && ($h == 0) && ($w == 0)) {
                    $bw += 2;
                }
                if (($mode == GeneratorModeEnum::POLAR_SEEDING_NORTH) && ($h == 0)) {
                    $bw += 2;
                }
                if (($mode == GeneratorModeEnum::POLAR_SEEDING_SOUTH) && ($h == $this->getHeight() - 1)) {
                    $bw += 2;
                }

                if (($mode == GeneratorModeEnum::EQUATORIAL) && (($h == 2 && $this->getHeight() == 5) || (($h == 2 || $h == 3) && $this->getHeight() == 6))) {
                    $bw += 1;
                }

                if (
                    $mode != "nocluster"
                    && $mode != GeneratorModeEnum::FORCED_ADJACENCY
                    && $mode != GeneratorModeEnum::FORCED_RIM
                    && $mode != GeneratorModeEnum::POLAR_SEEDING_NORTH
                    && $mode != GeneratorModeEnum::POLAR_SEEDING_SOUTH
                ) {
                    for ($k = 0; $k < count($to); $k++) {
                        if ($this->isFieldEqual($w - 1, $h, $to[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w + 1, $h, $to[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w, $h - 1, $to[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w, $h + 1, $to[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w - 1, $h - 1, $to[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h + 1, $to[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h - 1, $to[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w - 1, $h + 1, $to[$k])) {
                            $bw += 0.5;
                        }
                    }
                }

                if ((($mode == GeneratorModeEnum::POLAR_SEEDING_NORTH) && ($h == 0)) || (($mode == GeneratorModeEnum::POLAR_SEEDING_SOUTH) && ($h == $this->getHeight() - 1))) {
                    for ($k = 0; $k < count($to); $k++) {
                        if ($this->isFieldEqual($w - 1, $h, $to[$k])) {
                            $bw += 2;
                        }
                        if ($this->isFieldEqual($w + 1, $h, $to[$k])) {
                            $bw += 2;
                        }
                    }
                }

                if (is_array($adjacent)) {
                    for ($k = 0; $k < count($adjacent); $k++) {
                        if ($this->isFieldEqual($w - 1, $h, $adjacent[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w + 1, $h, $adjacent[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w, $h - 1,  $adjacent[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w, $h + 1, $adjacent[$k])) {
                            $bw += 1;
                        }
                        if ($this->isFieldEqual($w - 1, $h - 1, $adjacent[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h + 1, $adjacent[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h - 1, $adjacent[$k])) {
                            $bw += 0.5;
                        }
                        if ($this->isFieldEqual($w - 1, $h + 1, $adjacent[$k])) {
                            $bw += 0.5;
                        }
                    }
                }

                if (is_array($no_adjacent)) {
                    for ($k = 0; $k < count($no_adjacent); $k++) {
                        $ad = 0;
                        if ($this->isFieldEqual($w - 1, $h, $no_adjacent[$k])) {
                            $ad += 1;
                        }
                        if ($this->isFieldEqual($w + 1, $h, $no_adjacent[$k])) {
                            $ad += 1;
                        }
                        if ($this->isFieldEqual($w, $h - 1, $no_adjacent[$k])) {
                            $ad += 1;
                        }
                        if ($this->isFieldEqual($w, $h + 1, $no_adjacent[$k])) {
                            $ad += 1;
                        }
                        if ($this->isFieldEqual($w - 1, $h - 1, $no_adjacent[$k])) {
                            $ad += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h + 1, $no_adjacent[$k])) {
                            $ad += 0.5;
                        }
                        if ($this->isFieldEqual($w + 1, $h - 1, $no_adjacent[$k])) {
                            $ad += 0.5;
                        }
                        if ($this->isFieldEqual($w - 1, $h + 1, $no_adjacent[$k])) {
                            $ad += 0.5;
                        }

                        if ($ad > $noadjacentlimit) {
                            $bw = 0;
                        }
                    }
                }

                if (($mode == GeneratorModeEnum::FORCED_ADJACENCY) && ($bw < 2)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::FORCED_RIM) && ($bw < 1.5)) {
                    $bw = 0;
                }

                if (($mode == GeneratorModeEnum::POLAR) && ($h > 1) && ($h < $this->getHeight() - 2)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::STRICT_POLAR) && ($h > 0) && ($h < $this->getHeight() - 1)) {
                    $bw = 0;
                }
                if ($mode == GeneratorModeEnum::POLAR_SEEDING_NORTH && ($h > 1)) {
                    $bw = 0;
                }
                if ($mode == GeneratorModeEnum::POLAR_SEEDING_SOUTH && ($h < $this->getHeight() - 2)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::EQUATORIAL) && (($h < 2) || ($h > 3)) && ($this->getHeight() == 6)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::EQUATORIAL) && (($h < 2) || ($h > 3)) && ($this->getHeight() == 5)) {
                    $bw = 0;
                }

                if (($mode == GeneratorModeEnum::LOWER_ORBIT) && ($h != 1)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::UPPER_ORBIT) && ($h != 0)) {
                    $bw = 0;
                }

                if (($mode == GeneratorModeEnum::TIDAL_SEEDING) && ($w != 0)) {
                    $bw = 0;
                }

                if (($mode == GeneratorModeEnum::TOP_LEFT) && (($h != 0) || $w != 0)) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::RIGHT) && ($this->isFieldUnequal($w - 1, $h, $adjacent[0]))) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::BELOW) && ($this->isFieldUnequal($w, $h - 1, $adjacent[0]))) {
                    $bw = 0;
                }
                if (($mode == GeneratorModeEnum::CRATER_SEEDING) && (($w == $this->getWidth() - 1) || ($h == $this->getHeight() - 1))) {
                    $bw = 0;
                }

                if ($bw > 0) {
                    $res[$c][PlanetGenerator::COLGEN_X] = $w;
                    $res[$c][PlanetGenerator::COLGEN_Y] = $h;
                    $res[$c][PlanetGenerator::COLGEN_BASEWEIGHT] = $bw;
                    $c++;
                }
            }
        }
        return $res;
    }

    private function isFieldEqual(int $w, int $h, int $other): bool
    {
        //check for boundaries
        if ($w < 0 || $w >= $this->getWidth()) {
            return false;
        }
        if ($h < 0 || $h >= $this->getHeight()) {
            return false;
        }

        //check for existing value in field array
        if (!array_key_exists($w, $this->getFieldArray())) {
            return false;
        }
        if (!array_key_exists($h, $this->getFieldArray()[$w])) {
            return false;
        }

        return $this->getFieldArray()[$w][$h] == $other;
    }

    private function isFieldUnequal(int $w, int $h, int $other): bool
    {
        //check for boundaries
        if ($w < 0 || $w >= $this->getWidth()) {
            return true;
        }
        if ($h < 0 || $h >= $this->getHeight()) {
            return true;
        }

        //check for existing value in field array
        if (!array_key_exists($w, $this->getFieldArray())) {
            return true;
        }
        if (!array_key_exists($h, $this->getFieldArray()[$w])) {
            return true;
        }

        return $this->getFieldArray()[$w][$h] != $other;
    }
}
