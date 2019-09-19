# PHP Async Http Server

## Asynchronous Http Server for PHP!

It's new available! You can run your server in an async environment, without using multi-threads. You can now accept many HTTP connections and use the same environment. It may help you to use resources better and improve your script performances. You don't need an Apache anymore, control your user stream as you ever wanted to do!

## Installation

You can:

1. run `composer require amonite\async amonite\http-async`, or
2. copy `async.phar` and `http-async.phar` files in your project directory and load it with a `require`.

## Context

As a pure-functional developer and craftmanship worker, I always wanted to trigger my functions at an event and control my environment. I did remove first Apache limited environment, then I copy NodeJS syntax.

# Usage

## Start a server with configuration

You can easily configure 3 parameters: expected request `domain` (default=`"localhost"`), server `port` listening (default=`80`), server `inbound` connections simultaneously (default=`0` means any connection your computer performances can tolerate).

``` php
<?php

await(function () {
  $server = HttpServer\Http::createServer(function (HttpServer\ClientRequest $req, HttpServer\ServerResponse $res) {
    $req->endPromise()->then(function () use ($res) {
      $res->end("Hello, World!");
    });
  });

  $server->listen(array(
    "domain" => "127.0.0.1",
    "port" => 3000,
    "inbound" => 10
  ), function () {
    echo "Server launched at: http://127.0.0.1:3000/";
  });
});

```


## Use promised events

In the example above, you can see the syntax: you enter the function (that did help to build the server) when there is a new connection; that doesn't mean you read the request, the headers and the body! Please wait `$req` to resolve `end` event. The promised events helps you to check data at each chunk of data received.

``` php
<?php

await(function () {
  $server = HttpServer\Http::createServer(function ($req, $res) {

    // when request phrase is received, you can prepare resources.
    $req->requestPromise()->then(function () use ($phrase) {
      list($method, $url, $protocol) = explode(" ", $phrase);
      check_method_allowed($method);
      prepare_resource($url);
    });

    // when headers are received, you can check body length before loading it.
    $req->headerPromise()->then(function () use ($req) {
      $len = $req->getHeader("content-length");
      if (!\is_null($len) && $len > 0xfffff) {
        $req->abort();
      }
    });

    $req->endPromise()->then(function () use ($res) {
      $res->end("Hello, World!");
    });
  });

  $server->listen();  
});
```


## Stock body in temporary file


``` php
<?php

await(function () use () {
  $server = HttpServer\Http::createServer(function ($req, $res) {
    $req->insertBodyInTempfile();
    $req->endPromise()->then(function () use ($req, $res) {
      $tmp = $req->getTempfile();

      echo \json_encode(array($tmp => file_get_contents($tmp))) . "\n";

      $res->end("Yeah!");
    });
  });

  $server->listen();
});
```

# Documentation

## Class `Http`

### Static `createServer`

## Class `Server`

### Method `__construct`

### Method `listen`

### Method `close`

## Class `ClientRequest`

### Method `__construct`

### Method `getHeaders`

### Method `getHeader`

### Method `getRequest`

### Method `getBody`

## Class `ServerResponse`
