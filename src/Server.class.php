<?php

namespace HttpServer;
use Async\Promise;

if (!\class_exists("HttpServer\\Server")) {

  class Server {

    private $fn, $handler, $closing = null;


    /**
    * @method __construct Create a server instance that await for listening a port.
    * @param $fn {Closure.<HttpServer\ClientRequest $req, HttpServer\ServerResponse $res>}
    * @return {HttpServer\Server} new instance.
    */

    function __construct (\Closure $fn) {
      $this->fn = $fn;
    }


    /**
    * @method listen Starts server listening at port and address in `$options`,
    *    then call `$then` function.
    * @param $options {array}
    * @param $then {Closure}
    * @return {HttpServer\Server} this instance.
    * @throws {HttpServer\Error} if server is not created.
    */

    function listen ($options = array(), $then = null) {
      if (($this->handler = $handler = \socket_create(\AF_INET, \SOCK_STREAM, \SOL_TCP)) === false) {
        throw new Error("Can't create a socket, please verify your admin privileges");
      }

      if (!\is_array($options)) $options = array();
      $address = isset($options["domain"]) ? $options["domain"] : (
        isset($options["host"]) ? $options["host"] : (
          isset($options["address"]) ? $options["address"] : "localhost" ) );
      $port = isset($options["port"]) ? $options["port"] : 80;
      $inbound = isset($options["inbound"]) ? $options["inbound"] : (
        isset($options["count"]) ? $options["count"] : 0 );

      if (\socket_bind($handler, $address, $port) === false) {
        throw Error::auto($handler);
      }

      if (\socket_listen($handler, $inbound) === false) {
        throw Error::auto($handler);
      }

      if (\socket_set_nonblock($handler) === false) {
        throw Error::auto($handler);
      }

      if ($then instanceof \Closure) {
        $then();
      }

      $self = $this;
      return new Promise(function ($resolve, $reject) use ($self) {
        async((function () {
          if ($this->closing) return $this->closing;
          if ($socket = \socket_accept($this->handler)) {
            $this->call($socket);
          }
        })->bindTo($this), function ($error, $fn) use ($resolve, $reject) {
          if (\socket_close($this->handler) === false) {
            $reject(Error::auto($this->handler));
          }
          if ($error) {
            $reject($error);
          }
          if ($fn instanceof \Closure) {
            $fn();
          }
          $resolve();
        });
      });
    }


    private function call($socket) {
      if (!\is_resource($socket)) {
        throw new Error("First parameter is not a socket");
      }
      $fn = $this->fn;
      $fn(new ClientRequest($socket), new ServerResponse($socket));
      return $this;
    }


    /**
    * @method close Close the server, then execute `$fn`.
    * @param $then {Closure}
    * @return {HttpServer\Server}
    * @throws {HttpServer\Error} if server can't be closed.
    */

    function close($then = null) {
      $this->closing = $then instanceof \Closure ? $then : true;
      return $this;
    }
  }
}
