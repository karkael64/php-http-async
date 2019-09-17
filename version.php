<?php

$this_file = $argv[0];
$rank_version = isset($argv[1]) ? $argv[1] : null;

$timestamp = microtime(true) * 1000;
$composer_file = __DIR__ . "/composer.json";
$composer = json_decode(file_get_contents($composer_file), true);
$init_version = $composer["version"];

if ($rank_version) {
	$ms = $timestamp % 1000;
	$composer["timestamp"] = $timestamp;
	$composer["date"] = date("Y-m-d\TH:i:s.") . $ms . "Z";
	$version = explode(".", $composer["version"]);
	switch($rank_version) {
		case "m":
		case "minor":
		case "fix":
			$version[2]++;
			break;
		case "f":
		case "mid":
		case "medium":
		case "func":
			$version[1]++;
			$version[2] = 0;
			break;
		case "M":
		case "major":
		case "refacto":
			$version[0]++;
			$version[1] = 0;
			$version[2] = 0;
			break;
	}
	$composer["version"] = implode(".", $version);
	file_put_contents($composer_file, json_encode($composer, JSON_PRETTY_PRINT));
}

echo "Building..." . PHP_EOL;

$f = "http-async.phar";
if (file_exists($f))
	unlink($f);

$p = new Phar($f);

$files = array(
  __DIR__ . "/src/Error.class.php",
  __DIR__ . "/src/Http.class.php",
  __DIR__ . "/src/Server.class.php",
  __DIR__ . "/src/ClientRequest.class.php",
	__DIR__ . "/src/ServerResponse.class.php"
);

foreach($files as $file) {
	$p->addFile($file, basename($file));
}

$p->setStub('<?php
$pharname = basename(__FILE__);

require_once "phar://$pharname/Error.class.php";
require_once "phar://$pharname/Http.class.php";
require_once "phar://$pharname/Server.class.php";
require_once "phar://$pharname/ClientRequest.class.php";
require_once "phar://$pharname/ServerResponse.class.php";

__HALT_COMPILER();');

echo "Phar build !" . PHP_EOL;
echo "Version: " . $init_version . "->" . $composer["version"] . PHP_EOL;
echo "At: " . $timestamp . PHP_EOL;
