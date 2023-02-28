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
 *    3: int,
 *    4: int
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
 *  6: int,
 *  7: int
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
    ): array {
        $config = $this->loadColonyClassConfig($planetTypeId);
        [$ophase, $phase, $uphase, $hasGround, $hasOrbit] = $config[0];

        // start bonus
        if ($config[self::CONFIG_COLGEN_SIZEW] != 10) {
            $bonusFieldAmount = $bonusFieldAmount - 1;
        }

        $bftaken = 0;
        $phaseSuperCount = 0;
        $phasesResourceCount = 0;

        if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 15)) {
            $phaseSuperCount += 1;
            $bftaken += 1;
        }
        if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 80)) {
            $phasesResourceCount += 1;
            $bftaken += 1;
        }
        if (($phaseSuperCount == 0) && ($config[self::CONFIG_COLGEN_SIZEW] > 7)) {
            if (($bftaken < $bonusFieldAmount) && (rand(1, 100) <= 10)) {
                $phasesResourceCount += 1;
            }
        }

        $bonusPhaseCount = 0;

        // Bonus Phases

        $bphase = [];

        for ($i = 0; $i < $phaseSuperCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_SUPER);
            $bonusPhaseCount++;
        }

        for ($i = 0; $i < $phasesResourceCount; $i++) {
            $bphase[$bonusPhaseCount] = $this->createBonusPhase(self::BONUS_ANYRESOURCE);
            $bonusPhaseCount++;
        }

        // end bonus
        $phases = [
            self::PHASE_COLONY => $phase,
            self::PHASE_ORBIT => $ophase,
            self::PHASE_UNDERGROUND => $uphase,
            self::PHASE_BONUS => $bphase,
        ];

        $surfaceFieldsConfiguration = $this->doPhases($config, $phases, self::PHASE_COLONY);
        $orbitFieldsConfiguration = $hasOrbit ? $this->doPhases($config, $phases, self::PHASE_ORBIT) : null;
        $undergroundFieldsConfiguration = $hasGround ?  $this->doPhases($config, $phases, self::PHASE_UNDERGROUND) : null;

        return [
            'name' => $config[self::CONFIG_NAME],
            'surfaceWidth' => $config[self::CONFIG_COLGEN_SIZEW],
            'surfaceFields' => $this->combine($surfaceFieldsConfiguration, $orbitFieldsConfiguration, $undergroundFieldsConfiguration),
        ];
    }

    private function doPhases(
        array $config,
        array $phases,
        int $fieldTypeCategory
    ): FieldsConfigurationInterface {

        $fieldsConfiguration = $this->initFieldsConfiguration($config, $fieldTypeCategory);

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

    private function initBaseFields(int $height, int $width, int $baseFieldType): array
    {
        $baseFieldArray = [];

        for ($i = 0; $i < $height; $i++) {
            for ($j = 0; $j < $width; $j++) {
                $baseFieldArray[$j][$i] = $baseFieldType;
            }
        }

        return $baseFieldArray;
    }

    private function initFieldsConfiguration(array $config, int $fieldTypeCategory): FieldsConfigurationInterface
    {
        $h = $config[self::CONFIG_COLGEN_SIZEH];
        $w = $config[self::CONFIG_COLGEN_SIZEW];

        return new FieldsConfiguration(
            $this->initBaseFields($h, $w, $config[$fieldTypeCategory]),
            $h,
            $w
        );
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
        $res = array();
        $res[self::COLGEN_FROM] = [];
        $res[self::COLGEN_TO] = [];

        if ($btype == self::BONUS_HABITAT) {
            $res = $this->shadd($res, 101, "03");
            $res = $this->shadd($res, 111, "03");
            $res = $this->shadd($res, 112, "03");
            $res = $this->shadd($res, 601, "03");
            $res = $this->shadd($res, 601, "04");
            $res = $this->shadd($res, 602, "03");
            $res = $this->shadd($res, 611, "03");
            $res = $this->shadd($res, 611, "04");
            $res = $this->shadd($res, 713, "04");
            $res = $this->shadd($res, 715, "04");
            $res = $this->shadd($res, 725, "04");
        }

        // solar
        if (($btype == self::BONUS_SENERGY) || ($btype == self::BONUS_AENERGY)) {
            $res = $this->shadd($res, 401, "31");
            $res = $this->shadd($res, 402, "31");
            $res = $this->shadd($res, 403, "31");
            $res = $this->shadd($res, 404, "31");
            $res = $this->shadd($res, 713, "31");
        }

        // strömung
        if (($btype == self::BONUS_WENERGY) || ($btype == self::BONUS_AENERGY)) {
            $res = $this->shadd($res, 201, "32");
            $res = $this->shadd($res, 221, "32");
        }

        if (($btype == self::BONUS_ORE) || ($btype == self::BONUS_ANYRESOURCE)) {
            $res = $this->shadd($res, 701, "12");
            $res = $this->shadd($res, 702, "12");
            $res = $this->shadd($res, 703, "12");
            $res = $this->shadd($res, 704, "12");
            $res = $this->shadd($res, 705, "12");
            $res = $this->shadd($res, 706, "12");
        }

        if (($btype == self::BONUS_DEUTERIUM) || ($btype == self::BONUS_ANYRESOURCE)) {
            $res = $this->shadd($res, 201, "11");
            $res = $this->shadd($res, 210, "11");
            $res = $this->shadd($res, 211, "11");
            $res = $this->shadd($res, 221, "11");
            $res = $this->shadd($res, 501, "11");
            $res = $this->shadd($res, 511, "11");
        }

        if ($btype == self::BONUS_SUPER) {

            // dili
            $res = $this->shadd($res, 701, "21");
            $res = $this->shadd($res, 702, "21");
            $res = $this->shadd($res, 703, "21");
            $res = $this->shadd($res, 704, "21");
            $res = $this->shadd($res, 705, "21");
            $res = $this->shadd($res, 706, "21");
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
        for ($i = 0; $i < $surfaceFieldsConfiguration->getHeight(); $i++) {
            for ($j = 0; $j < $surfaceFieldsConfiguration->getWidth(); $j++) {
                $res[$q] = $surfaceFieldsConfiguration->getFieldArray()[$j][$i];
                $q++;
            }
        }
        if ($orbitFieldsConfiguration !== null) {
            for ($i = 0; $i < $orbitFieldsConfiguration->getHeight(); $i++) {
                for ($j = 0; $j < $orbitFieldsConfiguration->getWidth(); $j++) {
                    $res[$q] = $orbitFieldsConfiguration->getFieldArray()[$j][$i];
                    $q++;
                }
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

        echo 'combine';

        return $res;
    }
}
