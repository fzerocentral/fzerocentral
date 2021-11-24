<?php

require_once '../common.php';

$game_shortcode = $_GET['game'];
$game = FserverGame($game_shortcode);

$template = $twig->load($game->rules_template);
echo $template->render([
  'page_class' => 'page-rules',
  'PAGE_TITLE' => $game->name . " time submission rules",
]);
