<?php

namespace HttpServer;

if (!\class_exists("HttpServer\\Error")) {

  class Error extends \Error {


    /**
     * @static auto
     * @param resource $socket
     * @return HttpServer\Error
     */

    static function auto ($socket) {
      $code = \socket_last_error($socket);
      $msg = \socket_strerror($code);
      return new self($msg, $code);
    }
  }
}
