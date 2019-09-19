<?php

require_once __DIR__ . "/../vendor/amonite/async/index.php";
require_once __DIR__ . "/../index.php";

$port = isset($argv[1]) && is_numeric($argv[1]) ? +$argv[1] : 8080;

function check_method_allowed(string $method) {
  return \array_search(\strtoupper($method), array("GET", "POST")) !== false;
}

function prepare_resource(string $url) {}

await(function () use ($port) {
  $server = HttpServer\Http::createServer(function ($req, $res) {

    // when request phrase is received, then you can prepare resources.
    $req->requestPromise()->then(function ($phrase) use ($req) {
      list($method, $url, $protocol) = explode(" ", $phrase);
      if (!check_method_allowed($method)) $req->abort();
      prepare_resource($url);
    });

    // when request headers are received, then you can check body length before loading it.
    $req->headerPromise()->then(function () use ($req) {
      $len = $req->getHeader("content-length");
      if (!\is_null($len) && $len > 0xfffff) {
        $req->abort();
      }
    });

    // when request ends, then you can send response
    $req->endPromise()->then(function () use ($res) {
      $res->end("Hello, World!\n");
    });
  });

  $server->listen(array("port" => $port));
});
