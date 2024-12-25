<?php

require_once '../common.php';
use Michelf\Markdown;

$game_shortcode = $_GET['game'];
$game = FserverGame($game_shortcode);
$this_game_rules_markdown = file_get_contents(
  __DIR__ . '/../rules/' . $game->rules_file);
$all_games_rules_markdown = file_get_contents(
  __DIR__ . '/../rules/all.md');
$rules_html = Markdown::defaultTransform(
  $all_games_rules_markdown . "\n\n" . $this_game_rules_markdown);

$template = $twig->load('rules.html');
echo render_template($template, [
  'page_class' => 'page-rules',
  'rules_content' => $rules_html,
  'PAGE_TITLE' => $game->name . " Ladder Submission Rules",
]);
