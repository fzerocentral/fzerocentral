<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/fzero.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/urls.php';

$config = parse_ini_file('config.ini', true);

db_open();

session_start();

if (isset($_SESSION['current_user_id'])) {
  $current_user_id = intval($_SESSION['current_user_id']);
  $current_user = mysqli_fetch_assoc(db_query("SELECT * FROM phpbb_users WHERE phpbb_users.user_id = $current_user_id"));
}

class Project_Twig_Extension extends \Twig\Extension\AbstractExtension {
  public function getFilters() {
    return [
      new \Twig\TwigFilter('format_time', function($v) { return format_time($v, ''); }),
      new \Twig\TwigFilter('flag', function($country) {
        $country = htmlspecialchars($country);
        $flag = $country == '' ? 'undefined' : strtolower($country);
        return "<img class='flag' src='images/flags/$flag.gif' title='$country' /></a>";
      }, ['is_safe' => ['html']]),
      new \Twig\TwigFilter('proof_link', function($record, $prefix = '') {
        if ($record["${prefix}verified"]) {
          $url = "/images/proof_statuses/verified-proof.png";
        } else {
          $url = "/images/proof_statuses/unverified-proof.png";
        }

        $proof = htmlspecialchars($record["${prefix}videourl"]);
        return "<a href='$proof'><img src='$url' /></a>";
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

function render_template ($template, $args) {
  $args['fzero_css_mtime'] = filemtime('fzero.css');
  return $template->render($args);
}
