<?php

use Stu\PlanetGenerator\PlanetGenerator;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

require_once __DIR__.'/../vendor/autoload.php';

/**
 * @todo fix several warnings
 */
error_reporting(E_ERROR);

$planetTypeId = $_GET['type'] ?? 401;

$planetGenerator = new PlanetGenerator();

$config = $planetGenerator->generateColony($planetTypeId, 2);
$sep = $config['surfaceWidth'];

foreach ($config['surfaceFields'] as $key => $field) {
    echo sprintf(
        '<img src="assets/generated/fields/%d.png" />',
        $field
    );
    if (((int) $key + 1) % $sep === 0) {
        echo '<br />';
    }
}