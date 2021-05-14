<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/fzero.php';
require_once __DIR__ . '/database.php';

db_open();

class Project_Twig_Extension extends \Twig\Extension\AbstractExtension {
  public function getFilters() {
    return [
      new \Twig\TwigFilter('format_time', function($v) { return format_time($v, ''); }),
    ];
  }
}

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
      'cache' => __DIR__ . '/cache',
      'debug' => true,
]);
$twig->AddExtension(new Project_Twig_Extension());


