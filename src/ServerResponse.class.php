<?php

namespace HttpServer;
use Async\Promise;

if (!\class_exists("HttpServer\\ServerResponse")) {

  class ServerResponse {

    const CHUNK_LENGTH = 0xffff;
    private
      $socket,
      $code = null,
      $message = "",
      $error = null,
      $file = null,
      $head_prom, $head_done = false, $head = array(),
      $body_prom, $body_done = false, $raw = "";


    /**
     * @method __construct
     * @param resource $socket
     * @return HttpServer\ServerResponse
     */

    function __construct ($socket) {
      if (!\is_resource($socket)) {
        throw new Error("First parameter is not a socket");
      }
      $this->socket = $socket;

      $this->head_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->head_done ? $this->head : false;
      })->bindTo($this));
      $this->head_prom->catch(function () {});

      $this->body_prom = Promise::async((function () {
        if ($this->error) throw $this->error;
        return $this->body_done ? $this->raw : false;
      })->bindTo($this));
      $this->body_prom->catch(function () {});
    }


    /**
     * @method setCode
     * @param int $code
     * @return HttpServer\ServerResponse
     * @throws HttpServer\Error if headers are already sent
     */

    function setCode (int $code) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      $this->code = $code;
      return $this;
    }


    /**
     * @method setHeader
     * @param string $field
     * @param string $value
     * @return HttpServer\ServerResponse
     * @throws HttpServer\Error if headers are already sent
     */

    function setHeader (string $field, string $value) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      $this->head[Http::toField($field)] = $value;
      return $this;
    }


    /**
     * @method setHeaders
     * @param array $head
     * @return HttpServer\ServerResponse
     */

    function setHeaders (array $head) {
      foreach($head as $field => $value) {
        $this->setHeader($field, $value);
      }
      return $this;
    }


    /**
     * @method issetHeader
     * @param string $field
     * @return bool
     */

    function issetHeader (string $field) {
      return isset($this->head[Http::toField($field)]);
    }


    /**
     * @method unsetHeader
     * @param string $field
     * @return HttpServer\ServerResponse
     */

    function unsetHeader (string $field) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");
      if (isset($this->head[Http::toField($field)])) {
        unset($this->head[Http::toField($field)]);
      }
      return $this;
    }


    /**
     * @method getHeader
     * @param string $field
     * @return string|null
     */

    function getHeader (string $field) {
      if (isset($this->head[Http::toField($field)])) {
        return $this->head[Http::toField($field)];
      } else {
        return null;
      }
    }


    /**
     * @method getHeaders
     * @return array
     */

    function getHeaders () {
      return $this->head;
    }


    /**
     * @method writeHead
     * @param array $head
     * @param int $code
     * @return HttpServer\ServerResponse
     */

    function writeHead (array $head, int $code) {
      $this->setCode($code);
      $this->setHeaders($head);
      return $this;
    }


    /**
     * @method write
     * @param string text (optional, default=`""`)
     * @return HttpServer\ServerResponse
     * @throws HttpServer\Error if response is already sent.
     */

    function write (string $text = "") {
      if ($this->body_done) throw $this->error = new Error("Response is already sent.");
      $this->raw .= $text;
      return $this;
    }


    /**
     * @method end
     * @param string $text (optional, default=`""`)
     * @return Async\Promise
     * @throws HttpServer\Error if response is already sent.
     */

    function end (string $text = "") {
      if (\strlen($text)) {
        $this->write($text);
      }
      return $this->writeResponse();
    }


    /**
     * @method file
     * @param string $filepath
     * @return Async\Promise
     */

    function file (string $filepath) {
      $this->file = $filepath;
      return $this->writeResponse();
    }


    private function writeResponse () {

      // prepare document
      $len = ($this->file) ? \filesize($this->file) : \strlen($this->raw);
      if (!$this->issetHeader("Date")) $this->setHeader("Date", date(DATE_RSS));
      if ($len) {
        if (\is_null($this->code)) $this->code = 200;
        if (!$this->issetHeader("Content-Type")) $this->setHeader("Content-Type", "text/plain; charset=utf-8");
        if (!$this->issetHeader("Content-Length")) $this->setHeader("Content-Length", $len);
      } else {
        if (\is_null($this->code)) $this->code = 204;
        if (!$this->issetHeader("Content-Length")) $this->setHeader("Content-Length", 0);
      }

      $document = ((!$this->head_done) ? $this->getHeaderToString() : "");
      $handler = null;
      if ($this->file) {
        $document .= Http::EOL;
        $handler = \fopen($this->file, 'r');
      }
      elseif (\strlen($this->raw)) {
        $document .= Http::EOL . $this->raw;
      }

      return new Promise(function ($resolve, $reject) use (&$document, $handler) {
        async((function () use (&$document, $handler) {

          // chunk text
          if (\strlen($document)) {
            $chunk = \substr($document, 0, ServerResponse::CHUNK_LENGTH);
            $document = \substr($document, ServerResponse::CHUNK_LENGTH);

            // complete head chunk with file
            if (!\is_null($handler) && ($len = \strlen($chunk)) < ServerResponse::CHUNK_LENGTH) {
              $str = \fread($handler, ServerResponse::CHUNK_LENGTH - $len);
              if ($str === false) {
                throw $this->error = new Error("Can't read file \"".$this->file."\"");
              } else {
                $chunk .= $str;
              }
            }

            $this->writeChunk($chunk);
            $this->head_done = true;
            return;
          }

          if (\is_null($handler)) {
            return true;
          } else {
            // chunk file
            if (\feof($handler)) return true;
            $chunk = \fread($handler, ServerResponse::CHUNK_LENGTH);
            if ($chunk === false) {
              throw $this->error = new Error("Can't read file \"".$this->file."\"");
            }
            $this->writeChunk($chunk);
          }
        })->bindTo($this), (function ($err) use (&$document, $handler, $resolve, $reject) {

          // close file and socket
          $this->head_done = true;
          $this->body_done = true;

          if (!\is_null($handler)) {
            if (\fclose($handler) === false) {
              $reject($this->error = new Error("Can't close file \"".$this->file."\""));
            }
          }
          if (\socket_close($this->socket) === false) {
            $reject($this->error = Error::auto($this->socket));
          }

          if ($err) {
            $reject($this->error = $err);
          }
          $resolve();

        })->bindTo($this));
      });
    }


    private function writeChunk (string $chunk) {
      if (\socket_write($this->socket, $chunk) === false) {
        throw Error::auto($this->socket);
      }
    }


    /**
     * @method sendHeaders
     * @param int $len (optional, default=`0`)
     * @return HttpServer\ServerResponse
     * @throws HttpServer\Error if headers are already sent.
     */

    function sendHeaders (int $len = 0) {
      if ($this->head_done) throw $this->error = new Error("Headers are already sent.");

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


    private function getHeaderToString (int $len = 0) {
      $lines = array();

      $code = $this->code;
      $phrase = Http::STATUS_CODES[$code][Http::KEY_MSG];
      $message = "HTTP/1.1 $code $phrase";
      $lines []= $message;

      foreach ($this->head as $field => $value) {
        $lines []= "$field: $value";
      }

      return $headers = implode(Http::EOL, $lines) . Http::EOL;
    }


    /**
     * @method headerPromise
     * @return Async\Promise.<array $head>.<Throwable $err>
     */

    function headerPromise () {
      return $this->head_prom;
    }


    /**
     * @method endPromise
     * @return Async\Promise.<string $body>.<Throwable $err>
     */

    function endPromise () {
      return $this->body_prom;
    }


    /**
     * @method abort
     * @return HttpServer\ServerResponse
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
