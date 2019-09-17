<?php

namespace HttpServer;
use Async\Promise;

if (!\class_exists("HttpServer\\ServerResponse")) {

  class ServerResponse {

    private
      $socket,
      $code = 200,
      $message = "",
      $error = null,
      $head_prom, $head_done = false, $head = array(),
      $body_prom, $body_done = false, $body = "";

    function __construct ($socket) {
      if (!\is_resource($socket)) {
        throw new Error("First parameter is not a socket");
      }
      $this->socket = $socket;

      $this->head_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->head_done ? $this->head : false;
      })->bindTo($this));

      $this->body_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->body_done ? $this->body : false;
      })->bindTo($this));
    }

    function setCode(int $code) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      $this->code = $code;
    }

    function setHeader(string $field, string $value) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      $this->head[Http::toField($field)] = $value;
      return $this;
    }

    function setHeaders(array $head) {
      foreach($head as $field => $value) {
        $this->setHeader($field, $value);
      }
      return $this;
    }

    function issetHeader(string $field) {
      return isset($this->head[Http::toField($field)]);
    }

    function unsetHeader(string $field) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      if (isset($this->head[Http::toField($field)])) {
        unset($this->head[Http::toField($field)]);
      }
      return $this;
    }

    function getHeader(string $field) {
      if (isset($this->head[Http::toField($field)])) {
        return $this->head[Http::toField($field)];
      } else {
        return null;
      }
    }

    function getHeaders() {
      return $this->head;
    }

    function writeHead(array $head, int $code) {
      $this->setCode($code);
      $this->setHeaders($head);
      return $this;
    }

    function write(string $text = "") {
      if ($this->body_done) throw $this->error = new Error("Response is already sent.");
      $this->body .= $text;
      return $this;
    }

    function end(string $text = "") {
      if (\strlen($text)) $this->write($text);

      try {
        if (!$this->head_done) {
          if (!isset($this->head['Date'])) $this->head['Date'] = date(DATE_RSS);
          if (!isset($this->head['Content-Type'])) $this->head['Content-Type'] = "text/html; charset=utf-8";
          if (!isset($this->head['Content-Length'])) $this->head['Content-Length'] = \strlen($this->body);
          if (!isset($this->head['Connexion'])) $this->head['Connexion'] = "close";

          $chunk = $this->getHeaderToString() . Http::EOL . $this->body;
        } else {
          $chunk = HTTP::EOL . $this->body;
        }

        if (\socket_write($this->socket, $chunk) === false) {
          throw Error::auto($this->socket);
        }
        $this->head_done = true;
        $this->body_done = true;

        if (\socket_close($this->socket) === false) {
          throw Error::auto($this->socket);
        }
      } catch (\Throwable $err) {
        throw $this->error = $err;
      }

      return $this;
    }

    function sendHeaders() {
      try {
        $headers = $this->getHeaderToString();

        if (\socket_write($this->socket, $headers) === false) {
          throw Error::auto($this->socket);
        }
        $this->head_done = true;
      } catch (\Throwable $err) {
        throw $this->error = $err;
      }

      return $this;
    }

    private function getHeaderToString() {
      $lines = array();

      $code = $this->code;
      $phrase = Http::STATUS_CODES[$code];
      $message = "HTTP/1.1 $code $phrase";
      $lines []= $message;

      foreach ($this->head as $field => $value) {
        $lines []= "$field: $value";
      }

      return $headers = implode(Http::EOL, $lines) . Http::EOL;
    }


    function headerPromise() {
      return $this->head_prom;
    }


    function endPromise() {
      return $this->body_prom;
    }
  }
}
