<?php

require_once __DIR__ . '/../common.php';

use Symfony\Component\Yaml\Yaml;

$ladder_id = 3;
foreach ([4, 5, 6, 7, 8, 11, 12, 13, 14, 15, 16, 17, 18] as $ladder_id) {
  $xml = FserverLadder($ladder_id);
  $yml = [];
  $yml['id'] = $ladder_id;
  $yml['name'] = strval($xml->ladder_name);
  $yml['description'] = strval($xml->description);
  $yml['config'] = [
    'types' => [
      'complete' => true,
      'bestlap' => $xml->haslap == "Yes",
      'maxspeed' => $xml->hasspeed == "Yes",
    ],
    'settings' => $xml->hassettings == "Yes",
    'format' => $xml->timeformat == "Hundredths" ? 'hundredths' : 'thousandths',
    'graphics_available' => $xml->graphics_available == "Yes",
    'pal_possible' => $xml->palpossible == "Yes",
  ];

  if ($yml['config']['pal_possible']) {
    $yml['config']['pal_ratio'] = $xml->pal_numerator . "/" . $xml->pal_denominator;
  }

  $yml['cups'] = [];

  foreach ($xml->cups->cup as $c) {
    $cup = [];

    $cup['id'] = intval($c["cupid"]);
    $cup['name'] = strval($c->cupname);

    $cup['courses'] = [];
    foreach ($c->courses->course as $cc) {
      $course = [];

      $course['id'] = intval($cc["courseid"]);
      $course['name'] = strval($cc->name);

      $cup['courses'] []= $course;
    }

    $yml['cups'] []= $cup;
  }

  if ($xml->ships->ship) {
    $yml['ships'] = [];

    foreach ($xml->ships->ship as $s) {
      $yml['ships'] []= strval($s);
    }
  }

  file_put_contents(
    __DIR__ . "/../data/ladders/$ladder_id.yml",
    Yaml::dump($yml, 7, 2)
  );
}
