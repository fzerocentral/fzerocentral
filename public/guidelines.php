<?php

require_once '../common.php';

$template = $twig->load('rules/guidelines.html');
echo $template->render([
  'page_class' => 'page-rules',
  'PAGE_TITLE' => 'Ladder guidelines',
]);
