<?php

use Stu\PlanetGenerator\PlanetGenerator;
use Stu\PlanetGenerator\PlanetGeneratorInterface;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @todo fix several warnings
 */
error_reporting(E_ERROR);

$planetTypeId = $_GET['type'] ?? null;

$planetGenerator = new PlanetGenerator();

function buildSurface(
    PlanetGeneratorInterface $planetGenerator,
    int $planetTypeId
): void {
    $config = $planetGenerator->generateColony($planetTypeId, 2);
    $sep = $config->getSurfaceWidth();

    $surface = '';

    foreach ($config->getFieldArray() as $key => $field) {
        $surface .= sprintf(
            '<img src="assets/generated/fields/%d.png" />',
            $field
        );
        if (((int) $key + 1) % $sep === 0) {
            $surface .= '<br />';
        }
    }

    echo sprintf('<div><h2>%d - %s</h2><div>%s</div></div>', $planetTypeId, $config->getName(), $surface);
}

if ($planetTypeId === null) {
    $typeIds = $planetGenerator->getSupportedPlanetTypes();

    foreach ($typeIds as $typeId) {
        buildSurface($planetGenerator, $typeId);
    }
} else {
    buildSurface($planetGenerator, $planetTypeId);
}
