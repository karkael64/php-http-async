<?php

namespace HttpServer;
use Async\Promise;

if (!\class_exists("HttpServer\\ClientRequest")) {

  class ClientRequest {

    const
      CHUNK_LENGTH = 0xffff,
      REGEX_REQUEST = '/^([A-Z]+)\\s+(\\/[^\\s]*)\\s+(HTTP\\/\\d+(\\.\\d+)?)$/i';

    const
      ERR_REQUEST_EMPTY = 1,
      ERR_REQUEST_MALFORMED = 2,
      ERR_HEADERS_MALFORMED = 3,
      ERR_WINDOWS_NONBLOCK = 10035;

    private
      $socket, $raw = "", $index = 0, $error = null,
      $method = "", $url = "", $protocol = "",
      $request = "",   $request_prom = null, $request_done = false,
      $head = array(), $head_prom = null,    $head_done = false,
                       $body_prom = null,    $body_done = false,
      $tempfile = null, $tempfile_handler = null;


    /**
     * @method __construct
     * @param resource $socket
     * @return HttpServer\ClientRequest
     * @throws HttpServer\Error if first parameter is not a socket
     */

    function __construct ($socket) {
      if (!\is_resource($socket)) {
        throw new Error("First parameter is not a socket");
      }
      $this->socket = $socket;

      $this->request_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->request_done ? $this->request : false;
      })->bindTo($this));
      $this->request_prom->catch(function () {});

      $this->head_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->head_done ? $this->head : false;
      })->bindTo($this));
      $this->head_prom->catch(function () {});

      $this->body_prom = new Promise((function ($resolve, $reject) {
        async((function () {
          if ($this->error) throw $this->error;
          return $this->body_done;
        })->bindTo($this), (function ($err) use ($resolve, $reject) {
          if ($err) $reject($err);
          else $resolve($this->raw);
        })->bindTo($this));
      })->bindTo($this));
      $this->body_prom->catch(function () {});

      $this->readRequest();
    }


    private function readRequest () {
      async((function () {

        if ($this->error) throw $this->error;
        if (($chunk = \socket_read($this->socket, self::CHUNK_LENGTH)) === false) {
          if(\socket_last_error() === self::ERR_WINDOWS_NONBLOCK) return;
          throw Error::auto($this->socket);
        }
        $this->readChunk($chunk);
        return (\strlen($chunk) < self::CHUNK_LENGTH);

      })->bindTo($this), (function ($err) {
        $this->error = $err;

        $this->request_done = true;
        $this->head_done = true;
        $this->body_done = true;
        if (!\is_null($this->tempfile_handler)) {
          \fclose($this->tempfile_handler);
        }

      })->bindTo($this));
    }


    private function readChunk ($chunk = "") {
      $this->raw .= $chunk;

      if (!$this->request_done) {
        if (!\strlen($this->raw)) {
          throw $this->error = new Error("Request empty", self::ERR_REQUEST_EMPTY);
        }
        elseif (($pos = \strpos($this->raw, "\n")) !== false) {
          $this->request = \trim(\substr($this->raw, 0, $pos));
          if (!\preg_match_all(self::REGEX_REQUEST, $this->request, $split)) {
            throw $this->error = new Error("Request is not well formed", self::ERR_REQUEST_MALFORMED);
          }
          $this->method = \strtoupper($split[1][0]);
          $this->url = $split[2][0];
          $this->protocol = \strtoupper($split[3][0]);

          $this->raw = \substr($this->raw, $pos + 1);
          $this->request_done = true;
        }
        else {
          return;
        }
      }

      $offset = 0;
      if (!$this->head_done) {
        while (($pos = \strpos($this->raw, "\n", $offset)) !== false) {
          $line = \trim(\substr($this->raw, $offset, $pos-$offset));
          $offset = $pos + 1;
          if (\strlen($line)) {
            $posKey = \strpos($line, ":");
            if ($posKey === false) {
              throw $this->error = new Error("Request headers are not well formed", self::ERR_HEADERS_MALFORMED);
            }
            $key = \substr($line, 0, $posKey);
            $value = \substr($line, $posKey+1);
            $this->head[Http::toField(\trim($key))] = \trim($value);
          }
          else {
            $this->head_done = true;
            break;
          }
        }
        $this->raw = \substr($this->raw, $offset);
      }

      if (!\is_null($this->tempfile_handler)) {
        \fwrite($this->tempfile_handler, $this->raw);
        $this->raw = "";
      }
    }


    /**
     * @method insertBodyInTempfile
     * @param string $filepath (optional, default=`""`)
     * @return HttpServer\ClientRequest self instance
     */

    function insertBodyInTempfile (string $filepath = "") {
      if (!\strlen($filepath)) {
        $filepath = tempnam(sys_get_temp_dir(), "php-http-async.");
      }
      $this->tempfile = $filepath;
      $this->tempfile_handler = fopen($filepath, "w");
      return $this;
    }


    /**
     * @method getTempfile
     * @return string
     */

    function getTempfile () {
      return $this->tempfile;
    }


    /**
     * @method getHeaders
     * @return array
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getHeaders () {
      if (!$this->head_done) throw new Error("Client hasn't sent header yet");
      return $this->head;
    }


    /**
     * @method getHeader
     * @param string $field
     * @return string|null
     */

    function getHeader (string $field) {
      if (isset($this->head[Http::toField($field)])) {
        return $this->head[Http::toField($field)];
      }
      return null;
    }


    /**
     * @method getRequest
     * @return string
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getRequest () {
      if (!$this->request_done) throw new Error("Client hasn't sent request yet");
      return $this->request;
    }


    /**
     * @method getMethod
     * @return string
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getMethod () {
      if (!$this->request_done) throw new Error("Client hasn't sent request yet");
      return $this->method;
    }


    /**
     * @method getUrl
     * @return string
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getUrl () {
      if (!$this->request_done) throw new Error("Client hasn't sent request yet");
      return $this->url;
    }


    /**
     * @method getProtocol
     * @return string
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getProtocol () {
      if (!$this->request_done) throw new Error("Client hasn't sent request yet");
      return $this->protocol;
    }


    /**
     * @method getBody
     * @return string
     * @throws HttpServer\Error if client hasn't sent header yet
     */

    function getBody () {
      if (!$this->body_done) throw new Error("Client hasn't sent body yet");
      return $this->raw;
    }


    /**
     * @method requestPromise
     * @return Async\Promise.<string $request>.<Throwable $err>
     */

    function requestPromise () {
      return $this->request_prom;
    }


    /**
     * @method headerPromise
     * @return Async\Promise.<string $request>.<Throwable $err>
     */

    function headerPromise () {
      return $this->head_prom;
    }


    /**
     * @method endPromise
     * @return Async\Promise.<string $request>.<Throwable $err>
     */

    function endPromise () {
      return $this->body_prom;
    }


    /**
     * @method abort
     * @return HttpServer\ClientRequest
     * @throws Throwable if socket can't be closed
     */

    function abort () {
      try {
        if (\socket_close($this->socket) === false) {
          throw Error::auto($this->socket);
        }
      } catch (\Throwable $err) {
        throw $this->error = $err;
      }
      return $this;
    }
  }
}
