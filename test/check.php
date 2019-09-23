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
    $req->requestPromise()->then(function (string $phrase) use ($req) {
      $method = $req->getMethod();
      $url = $req->getUrl();
      $protocol = $req->getProtocol();
      echo "request recieved: $method $url $protocol\n";

      if (!check_method_allowed($method)) {
        $req->abort();
      }
      prepare_resource($url);
    });

    // when request headers are received, then you can check body length before loading it.
    $req->headerPromise()->then(function (array $heads) use ($req) {
      \is_null($len = $req->getHeader("content-length")) and ($len = 0);
      echo "headers recieved: $len bytes expected\n";

      if (!\is_null($len) && $len > 0xfffff) {
        $req->abort();
      }
    });

    // when request ends, then you can send response
    $req->endPromise()->then(function (string $body) use ($res) {
      $len = \strlen($body);
      echo "body recieved ($len bytes recieved): " . \json_encode($body) . "\n";

      $res->end();
    });
  });

  $server->listen(array("port" => $port));
});
