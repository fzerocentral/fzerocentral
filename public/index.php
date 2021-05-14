<?php

require_once '../common.php';

$template = $twig->load('index.html');
echo $template->render([
  'PAGE_TITLE' => 'Home',
]);
