<?php

require_once '../common.php';
use Michelf\Markdown;

$all_games_rules_markdown = file_get_contents(
  __DIR__ . '/../rules/all.md');

$game_shortcode = $_GET['game'];
// Get the game details, and validate that this game shortcode exists
// (thus its rules files should exist too).
$game = FserverGame($game_shortcode);

$this_game_rules_markdown = file_get_contents(
  __DIR__ . "/../rules/$game_shortcode.md");
$old_changelog_markdown = file_get_contents(
  __DIR__ . "/../rules/old_changelogs/$game_shortcode.md");

$rules_html = Markdown::defaultTransform(
  $all_games_rules_markdown . "\n\n" . $this_game_rules_markdown
);
$old_changelog_html = Markdown::defaultTransform(
  $old_changelog_markdown
);

$template = $twig->load('rules.html');
echo render_template($template, [
  'page_class' => 'page-rules',
  'game_name' => $game->name,
  'game_shortcode' => $game_shortcode,
  'rules_html' => $rules_html,
  'old_changelog_html' => $old_changelog_html,
  'PAGE_TITLE' => $game->name . " Ladder Submission Rules",
]);
