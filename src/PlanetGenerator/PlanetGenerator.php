<?php

namespace Stu\PlanetGenerator;

use DirectoryIterator;
use Generator;
use SplFileInfo;
use Stu\PlanetGenerator\Exception\PlanetGeneratorException;
use Stu\PlanetGenerator\Exception\PlanetGeneratorFileMissingException;

/**
 * @template TConfig of array{
 *  0: array{
 *    0: TPhase,
 *    1: TPhase,
 *    2: TPhase,
 *    3: bool,
 *    4: bool
 *  },
 *  sizew: int,
 *  sizeh: int,
 *  name: string,
 *  1: int,
 *  2: int,
 *  3: int
 * }
 * @template TPhase of array{
 *  mode: string,
 *  description: string,
 *  num: int,
 *  from: array<int, int>,
 *  to: array<int, int>,
 *  adjacent: array<int>,
 *  noadjacent: int,
 *  noadjacentlimit: int,
 *  fragmentation: int
 * }
 * @template TPlanetConfig of array{
 *  0: array{basefield: int},
 *  1: array{
 *    details: string,
 *    sizew: int,
 *    sizeh: int,
 *    basefield: int
 *  },
 *  2: array{basefield: int},
 *  3: array<int, TPhase>,
 *  4: array<int, TPhase>,
 *  5: array<int, TPhase>,
 *  6: bool,
 *  7: bool
 * }
 */
final class PlanetGenerator implements PlanetGeneratorInterface
{
    //phase settings
    public const COLGEN_MODE = 'mode';
    public const COLGEN_DESCRIPTION = 'description';
    public const COLGEN_NUM = 'num';
    public const COLGEN_FROM = 'from';
    public const COLGEN_TO = 'to';
    public const COLGEN_ADJACENT = 'adjacent';
    public const COLGEN_NOADJACENT = 'noadjacent';
    public const COLGEN_NOADJACENTLIMIT = 'noadjacentlimit';
    public const COLGEN_FRAGMENTATION = 'fragmentation';

    //other
    public const COLGEN_BASEWEIGHT = 'baseweight';
    public const COLGEN_WEIGHT = 'weight';
    public const COLGEN_DETAILS = 'details';
    public const COLGEN_BASEFIELD = 'basefield';
    public const COLGEN_TYPE = 'type';
    public const COLGEN_Y = 'y';
    public const COLGEN_X = 'x';
    public const BONUS_HABITAT = 10;
    public const BONUS_ANYRESOURCE = 20;
    public const BONUS_ORE = 22;
    public const BONUS_DEUTERIUM = 21;
    public const BONUS_AENERGY = 30;
    public const BONUS_SENERGY = 31;
    public const BONUS_WENERGY = 32;
    public const BONUS_SUPER = 99;
    public const BONUS_QUALITY = 40;

    //phases enum
    private const PHASE_COLONY = 1;
    private const PHASE_ORBIT = 2;
    private const PHASE_UNDERGROUND = 3;
    private const PHASE_BONUS = 4;

    //config values
    public const CONFIG_COLGEN_SIZEW = 'sizew';
    public const CONFIG_COLGEN_SIZEH = 'sizeh';
    private const CONFIG_NAME = 'name';

    /**
     * @throws PlanetGeneratorException
     *
     * @return TConfig
     */
    public function loadColonyClassConfig(int $planetTypeId): array
    {
        [$odata, $data, $udata, $ophase, $phase, $uphase, $hasGround, $hasOrbit] = $this->loadColonyClass($planetTypeId);

        return [
            [$ophase, $phase, $uphase, $hasGround, $hasOrbit],
            self::CONFIG_COLGEN_SIZEW => $data[self::CONFIG_COLGEN_SIZEW],
            self::CONFIG_COLGEN_SIZEH => $data[self::CONFIG_COLGEN_SIZEH],
            self::PHASE_COLONY => $data[self::COLGEN_BASEFIELD],
            self::PHASE_ORBIT => $odata[self::COLGEN_BASEFIELD],
            self::PHASE_UNDERGROUND => $udata[self::COLGEN_BASEFIELD],
            self::CONFIG_NAME => $data[self::COLGEN_DETAILS],
        ];
    }

    /**
     * @throws PlanetGeneratorException
     */
    public function generateColony(
        int $planetTypeId,
        int $bonusFieldAmount
    ): GeneratedColonyConfigurationInterface {
        $config = $this->loadColonyClassConfig($planetTypeId);
        [$ophase, $phase, $uphase, $hasGround, $hasOrbit] = $config[0];

        // Startbonus
        if ($config[self::CONFIG_COLGEN_SIZEW] != 10) {
            $bonusFieldAmount = $bonusFieldAmount - 1;
        }

        $bftaken = 0;
        $phaseSuperCount = 0;
        $phasesResourceCount = 0;
        $phaseEnergyCount = 0;
        $phaseHabitatCount = 0;
        $phaseQualityCount = 0;

        // Funktionen für die Bonus-Generierung
        $bonusGenerators = [
            function () use (&$phaseSuperCount, &$bftaken, $bonusFieldAmount) {
                if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 75)) {
                    $phaseSuperCount += 1;
                    $bftaken += 1;
                }
            },
            function () use (&$phasesResourceCount, &$bftaken, $bonusFieldAmount) {
                if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 75)) {
                    $phasesResourceCount += 1;
                    $bftaken += 1;
                }
            },
            function () use (&$phaseEnergyCount, &$bftaken, $bonusFieldAmount) {
                if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 75)) {
                    $phaseEnergyCount += 1;
                    $bftaken += 1;
                }
            },
            function () use (&$phaseHabitatCount, &$bftaken, $bonusFieldAmount) {
                if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 75)) {
                    $phaseHabitatCount += 1;
                    $bftaken += 1;
                }
            },
            function () use (&$phaseQualityCount, &$bftaken, $bonusFieldAmount) {
                if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 75)) {
                    $phaseQualityCount += 1;
                    $bftaken += 1;
                }
            },
        ];

        shuffle($bonusGenerators);
        foreach ($bonusGenerators as $bonusGenerator) {
            $bonusGenerator();
        }

        if (($phaseSuperCount == 0) && ($config[self::CONFIG_COLGEN_SIZEW] > 7)) {
            if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 40)) {
                $phasesResourceCount += 1;
            }
        }

        // Bonus-Phasen
        $bonusPhaseCount = 0;
        $bphase = [];

        for ($i = 0; $i < $phaseSuperCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_SUPER);
            $bonusPhaseCount++;
        }

        for ($i = 0; $i < $phasesResourceCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_ANYRESOURCE);
            $bonusPhaseCount++;
        }

        for ($i = 0; $i < $phaseEnergyCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_AENERGY);
            $bonusPhaseCount++;
        }

        for ($i = 0; $i < $phaseHabitatCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_HABITAT);
            $bonusPhaseCount++;
        }

        for ($i = 0; $i < $phaseQualityCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_QUALITY);
            $bonusPhaseCount++;
        }

        // end bonus
        $phases = [
            self::PHASE_COLONY => $phase,
            self::PHASE_ORBIT => $ophase,
            self::PHASE_UNDERGROUND => $uphase,
            self::PHASE_BONUS => $bphase,
        ];

        $surfaceFieldsConfiguration = $this->doPhases(
            $config[self::CONFIG_COLGEN_SIZEW],
            $config[self::CONFIG_COLGEN_SIZEH],
            $phases,
            $config[self::PHASE_COLONY],
            self::PHASE_COLONY
        );
        $orbitFieldsConfiguration = $hasOrbit ? $this->doPhases(
            $config[self::CONFIG_COLGEN_SIZEW],
            2,
            $phases,
            $config[self::PHASE_ORBIT],
            self::PHASE_ORBIT
        ) : null;
        $undergroundFieldsConfiguration = $hasGround ? $this->doPhases(
            $config[self::CONFIG_COLGEN_SIZEW],
            2,
            $phases,
            $config[self::PHASE_UNDERGROUND],
            self::PHASE_UNDERGROUND
        ) : null;

        return new GeneratedColonyConfiguration(
            $config[self::CONFIG_NAME],
            $config[self::CONFIG_COLGEN_SIZEW],
            $config[self::CONFIG_COLGEN_SIZEH],
            $hasOrbit,
            $hasGround,
            $this->combine($surfaceFieldsConfiguration, $orbitFieldsConfiguration, $undergroundFieldsConfiguration)
        );
    }

    private function doPhases(
        int $width,
        int $height,
        array $phases,
        int $baseFieldType,
        int $fieldTypeCategory
    ): FieldsConfigurationInterface {

        $fieldsConfiguration = $this->initFieldsConfiguration($width, $height, $baseFieldType);

        if (!empty($phases[$fieldTypeCategory])) {
            $phaseCounts = count($phases[$fieldTypeCategory]);
            for ($i = 0; $i < $phaseCounts; $i++) {
                $fieldsConfiguration->doPhase($phases[$fieldTypeCategory][$i]);
            }
        }

        if ($fieldTypeCategory === self::PHASE_COLONY && !empty($phases[self::PHASE_BONUS])) {
            $bonusPhaseCounts = count($phases[self::PHASE_BONUS]);
            for ($i = 0; $i < $bonusPhaseCounts; $i++) {
                $fieldsConfiguration->doPhase($phases[self::PHASE_BONUS][$i]);
            }
        }

        return $fieldsConfiguration;
    }

    /**
     * @return Generator<int>
     */
    public function getSupportedPlanetTypes(): Generator
    {
        $list = new DirectoryIterator(__DIR__ . '/coldata');

        /** @var SplFileInfo $file */
        foreach ($list as $file) {
            if (!$file->isDir()) {
                yield (int) str_replace('.php', '', $file->getFilename());
            }
        }
    }

    private function initFieldsConfiguration(int $width, int $height, int $baseFieldType): FieldsConfigurationInterface
    {
        $fieldsConfiguration = new FieldsConfiguration($height, $width);
        $fieldsConfiguration->initBaseFields($baseFieldType);

        return $fieldsConfiguration;
    }

    /**
     * @throws PlanetGeneratorFileMissingException
     *
     * @return TPlanetConfig
     */
    private function loadColonyClass(int $id): array
    {
        $fileName = sprintf(
            '%s/coldata/%d.php',
            __DIR__,
            $id
        );
        if (!file_exists($fileName)) {
            throw new PlanetGeneratorFileMissingException('Planetgenerator description file missing for id ' . $id);
        }
        $requireResult = require $fileName;

        if (is_bool($requireResult)) {
            throw new PlanetGeneratorFileMissingException('Error loading planetgenerator description file for id ' . $id);
        }

        if (is_int($requireResult)) {
            throw new PlanetGeneratorFileMissingException('Error loading planetgenerator description file for id ' . $id);
        }

        return $requireResult;
    }

    private function shadd(array $arr, int $fld, string $bonus): array
    {
        array_push($arr[self::COLGEN_FROM], $fld);
        array_push($arr[self::COLGEN_TO], $fld . $bonus);

        return $arr;
    }

    private function getBonusFieldTransformations(int $btype): array
    {
        $res = [
            self::COLGEN_FROM => [],
            self::COLGEN_TO => [],
        ];

        $habitatFields = [
            101 => "03", 111 => "03", 112 => "03", 601 => "03", 601 => "04", 602 => "03",
            611 => "03", 611 => "04", 713 => "04", 715 => "04", 725 => "04"
        ];

        $qualityFields = [
            101 => "01", 111 => "01", 112 => "01", 121 => "01", 122 => "02",
            201 => "02", 211 => "02", 212 => "02", 221 => "02", 222 => "02",
            611 => "01"
        ];

        $solarFields = [
            401 => "31", 402 => "31", 403 => "31", 404 => "31", 713 => "31"
        ];

        $stromungFields = [
            201 => "32", 211 => "32", 212 => "32", 221 => "32", 222 => "32"
        ];

        $oreFields = [
            701 => "12", 702 => "12", 703 => "12", 704 => "12", 705 => "12", 706 => "12"
        ];

        $deuteriumFields = [
            201 => "11", 210 => "11", 211 => "11", 212 => "11", 221 => "11", 222 => "11",
            231 => "11", 232 => "11", 501 => "11", 511 => "11"
        ];

        $superFields = [
            701 => "21", 702 => "21", 703 => "21", 704 => "21", 705 => "21", 706 => "21"
        ];

        if ($btype == self::BONUS_HABITAT) {
            $selectedField = array_rand($habitatFields);
            $suffix = $habitatFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if ($btype == self::BONUS_QUALITY) {
            $selectedField = array_rand($qualityFields);
            $suffix = $qualityFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if (($btype == self::BONUS_SENERGY) || ($btype == self::BONUS_AENERGY)) {
            $selectedField = array_rand($solarFields);
            $suffix = $solarFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if (($btype == self::BONUS_WENERGY) || ($btype == self::BONUS_AENERGY)) {
            $selectedField = array_rand($stromungFields);
            $suffix = $stromungFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if (($btype == self::BONUS_ORE) || ($btype == self::BONUS_ANYRESOURCE)) {
            $selectedField = array_rand($oreFields);
            $suffix = $oreFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if (($btype == self::BONUS_DEUTERIUM) || ($btype == self::BONUS_ANYRESOURCE)) {
            $selectedField = array_rand($deuteriumFields);
            $suffix = $deuteriumFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        if ($btype == self::BONUS_SUPER) {
            $selectedField = array_rand($superFields);
            $suffix = $superFields[$selectedField];
            $res = $this->shadd($res, $selectedField, $suffix);
        }

        return $res;
    }

    private function createBonusPhase($btype)
    {
        $bphase = [];

        $bphase[self::COLGEN_MODE] = "nocluster";
        $bphase[self::COLGEN_DESCRIPTION] = "Bonusfeld";

        $br = $this->getBonusFieldTransformations($btype);

        $bphase[self::COLGEN_NUM] = 1;
        $bphase[self::COLGEN_FROM] = $br[self::COLGEN_FROM];
        $bphase[self::COLGEN_TO] = $br[self::COLGEN_TO];

        $bphase[self::COLGEN_ADJACENT] = 0;
        $bphase[self::COLGEN_NOADJACENT] = 0;
        $bphase[self::COLGEN_NOADJACENTLIMIT] = 0;
        $bphase[self::COLGEN_FRAGMENTATION] = 100;

        return $bphase;
    }

    /**
     * @return array<int, int>
     */
    private function combine(
        FieldsConfigurationInterface $surfaceFieldsConfiguration,
        ?FieldsConfigurationInterface $orbitFieldsConfiguration,
        ?FieldsConfigurationInterface $undergroundFieldsConfiguration
    ): array {
        $res = [];

        $q = 0;
        if ($orbitFieldsConfiguration !== null) {
            for ($i = 0; $i < $orbitFieldsConfiguration->getHeight(); $i++) {
                for ($j = 0; $j < $orbitFieldsConfiguration->getWidth(); $j++) {
                    $res[$q] = $orbitFieldsConfiguration->getFieldArray()[$j][$i];
                    $q++;
                }
            }
        }
        for ($i = 0; $i < $surfaceFieldsConfiguration->getHeight(); $i++) {
            for ($j = 0; $j < $surfaceFieldsConfiguration->getWidth(); $j++) {
                $res[$q] = $surfaceFieldsConfiguration->getFieldArray()[$j][$i];
                $q++;
            }
        }

        if ($undergroundFieldsConfiguration !== null) {
            for ($i = 0; $i < $undergroundFieldsConfiguration->getHeight(); $i++) {
                for ($j = 0; $j < $undergroundFieldsConfiguration->getWidth(); $j++) {
                    $res[$q] = $undergroundFieldsConfiguration->getFieldArray()[$j][$i];
                    $q++;
                }
            }
        }

        return $res;
    }
}
