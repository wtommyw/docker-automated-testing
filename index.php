<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . "/class/autoloader.php");

ini_set('display_errors', 'On');
$de = new Docker_Deployer(getenv('GITHUB_TOKEN'), getenv('GITHUB_USERNAME'));
$de->handle($_SERVER['REQUEST_URI']);