<?php

require_once '../common.php';

$template = $twig->load('index.html');
echo render_template($template, [
  'PAGE_TITLE' => 'Home',
  'current_user' => $current_user,
]);
