<?php

require_once __DIR__ . "/../vendor/amonite/async/index.php";
require_once __DIR__ . "/../index.php";

$port = isset($argv[1]) && is_numeric($argv[1]) ? +$argv[1] : 8080;

await(function () use ($port) {
  $server = HttpServer\Http::createServer(function ($req, $res) {
    $req->insertBodyInTempfile();
    $req->endPromise()->then(function () use ($req, $res) {
      $tmp = $req->getTempfile();
      
      echo \json_encode(array($tmp => file_get_contents($tmp))) . "\n";

      $res->end("Yeah!");
    });
  });

  $server->listen(array("port" => $port), function () use ($port) {
    echo "Server launched at: http://localhost:$port/\n";
  });
});
