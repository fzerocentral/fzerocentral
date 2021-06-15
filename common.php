<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/fzero.php';
require_once __DIR__ . '/database.php';

$config = parse_ini_file('config.ini', true);

db_open();

class Project_Twig_Extension extends \Twig\Extension\AbstractExtension {
  public function getFilters() {
    return [
      new \Twig\TwigFilter('format_time', function($v) { return format_time($v, ''); }),
      new \Twig\TwigFilter('flag', function($country) {
        $country = htmlspecialchars($country);
        $flag = $country == '' ? 'undefined' : strtolower($country);
        return "<img class='flag' src='images/flags/$flag.gif' title='$country' /></a>";
      }, ['is_safe' => ['html']]),
    ];
  }
}

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
      'cache' => __DIR__ . '/cache',
      'debug' => true,
]);
$twig->AddExtension(new Project_Twig_Extension());
