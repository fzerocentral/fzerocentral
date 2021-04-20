<?php

require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
      'cache' => 'cache',
      'debug' => true,
]);

$template = $twig->load('index.html');

echo $template->render(['PAGE_TITLE' => 'Home', 'go' => 'here']);
