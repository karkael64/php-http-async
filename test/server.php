<?php

require_once __DIR__ . "/../vendor/amonite/async/index.php";
require_once __DIR__ . "/../index.php";

$port = isset($argv[1]) && is_numeric($argv[1]) ? +$argv[1] : 8080;

await(function () use ($port) {
  $server = HttpServer\Http::createServer(function (HttpServer\ClientRequest $req, HttpServer\ServerResponse $res) {
    echo "connexion\n";
    $req->endPromise()->then(function () use ($req, $res) {
      echo "request received\n";
      echo json_encode(array(
        "request" => $req->getRequest(),
        "headers" => $req->getHeaders(),
        "body" => $req->getBody()
      ), JSON_PRETTY_PRINT) . "\n";

      $res->setHeader("Set-Cookie", "aze=rty");
      // $res->sendHeaders();
      $res->write("Hello, World!\n");
      $res->end("Yep! " . $req->getBody() . "\n");
    })->catch(function ($err) {
      die("err:$err");
    });
  });

  $server->listen(array("port" => $port), function () use ($port) {
    echo "Server launched at: http://localhost:$port/\n";
  });
});
