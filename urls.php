<?php

require_once 'config.php';


function url($path) {
  global $config;

  return $config['app']['base_url'] . $path;
}
