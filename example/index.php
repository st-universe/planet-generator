<?php

use Stu\PlanetGenerator\PlanetGenerator;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

require_once __DIR__.'/../vendor/autoload.php';

$planetTypeId = 401;

$planetGenerator = new PlanetGenerator();

//$sep = 10;
 $sep = 7; // moons

echo "<pre>";
$fields = $planetGenerator->generateColony($planetTypeId, 2);

echo "</pre><br />";

foreach ($fields as $key => $field) {
    echo sprintf(
        '<img src="assets/generated/fields/%d.png" />',
        $field
    );
    if (($key + 1) % $sep === 0) {
        echo '<br />';
    }
}