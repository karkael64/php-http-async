<?php

namespace HttpServer;

if (!\class_exists("HttpServer\\Http")) {

  class Http {

    const METHODS = [
      'ACL',
      'BIND',
      'CHECKOUT',
      'CONNECT',
      'COPY',
      'DELETE',
      'GET',
      'HEAD',
      'LINK',
      'LOCK',
      'M-SEARCH',
      'MERGE',
      'MKACTIVITY',
      'MKCALENDAR',
      'MKCOL',
      'MOVE',
      'NOTIFY',
      'OPTIONS',
      'PATCH',
      'POST',
      'PROPFIND',
      'PROPPATCH',
      'PURGE',
      'PUT',
      'REBIND',
      'REPORT',
      'SEARCH',
      'SUBSCRIBE',
      'TRACE',
      'UNBIND',
      'UNLINK',
      'UNLOCK',
      'UNSUBSCRIBE'
    ];

    const
      KEY_COD = "code",
      KEY_DSC = "description",
      KEY_MSG = "message",
      KEY_BDY = "has_body";

    const STATUS_CODES = [

      // #REQUEST PROCESS
      '100' => array(
        self::KEY_COD => 100,
        self::KEY_MSG => 'Continue',
        self::KEY_DSC => 'Server expects client to send its body.',
        self::KEY_BDY => false
      ),
      '101' => array(
        self::KEY_COD => 101,
        self::KEY_MSG => 'Switching Protocols',
        self::KEY_DSC => 'Client has asked to switch protocol and server agrred to do so.',
        self::KEY_BDY => false
      ),
      '102' => array(
        self::KEY_COD => 102,
        self::KEY_MSG => 'Processing',
        self::KEY_DSC => 'The server is working on the client request, please wait.',
        self::KEY_BDY => false
      ),

      // #SUCCESS
      '200' => array(
        self::KEY_COD => 200,
        self::KEY_MSG => 'OK',
        self::KEY_DSC => 'Server response succeded.',
        self::KEY_BDY => true
      ),
      '201' => array(
        self::KEY_COD => 201,
        self::KEY_MSG => 'Created',
        self::KEY_DSC => 'Server successfully created a new resource.',
        self::KEY_BDY => true
      ),
      '202' => array(
        self::KEY_COD => 202,
        self::KEY_MSG => 'Accepted',
        self::KEY_DSC => 'The server accepted the request, but the processing has not been completed.',
        self::KEY_BDY => true
      ),
      '203' => array(
        self::KEY_COD => 203,
        self::KEY_MSG => 'Non-Authoritative Information',
        self::KEY_DSC => 'The server is a transforming proxy that received a successful response, but modified response',
        self::KEY_BDY => true
      ),
      '204' => array(
        self::KEY_COD => 204,
        self::KEY_MSG => 'No Content',
        self::KEY_DSC => 'The server successfully processed the request and is not returning any content.',
        self::KEY_BDY => false
      ),
      '205' => array(
        self::KEY_COD => 205,
        self::KEY_MSG => 'Reset Content',
        self::KEY_DSC => 'The server successfully processed the request, but is not returning any content.',
        self::KEY_BDY => false
      ),
      '206' => array(
        self::KEY_COD => 206,
        self::KEY_MSG => 'Partial Content',
        self::KEY_DSC => 'Server is delivering only part of the resource, due to client expecteations.',
        self::KEY_BDY => true
      ),
      '207' => array(
        self::KEY_COD => 207,
        self::KEY_MSG => 'Multi-Status',
        self::KEY_DSC => 'Server send a number of separate response codes, depending on how many sub-requests were made.',
        self::KEY_BDY => true
      ),
      '226' => array(
        self::KEY_COD => 226,
        self::KEY_MSG => 'IM Used',
        self::KEY_DSC => 'Server send status in body of sub-services used to generate a response.',
        self::KEY_BDY => true
      ),

      // #REDIRECTION
      '300' => array(
        self::KEY_COD => 300,
        self::KEY_MSG => 'Multiple Choices',
        self::KEY_DSC => 'Server offers several redirection choices',
        self::KEY_BDY => true
      ),
      '301' => array(
        self::KEY_COD => 301,
        self::KEY_MSG => 'Moved Permanently',
        self::KEY_DSC => 'The resource client expected has been moved to another location.',
        self::KEY_BDY => true
      ),
      '302' => array(
        self::KEY_COD => 302,
        self::KEY_MSG => 'Found',
        self::KEY_DSC => 'The resource client expected is found in another location.',
        self::KEY_BDY => true
      ),
      '303' => array(
        self::KEY_COD => 303,
        self::KEY_MSG => 'See Other',
        self::KEY_DSC => 'The server response come from another location.',
        self::KEY_BDY => true
      ),
      '304' => array(
        self::KEY_COD => 304,
        self::KEY_MSG => 'Not Modified',
        self::KEY_DSC => 'The response has not been modified yet since client previous request.',
        self::KEY_BDY => false
      ),
      '305' => array(
        self::KEY_COD => 305,
        self::KEY_MSG => 'Use Proxy',
        self::KEY_DSC => 'The request should be sent through a proxy.',
        self::KEY_BDY => true
      ),
      '307' => array(
        self::KEY_COD => 307,
        self::KEY_MSG => 'Temporary Redirect',
        self::KEY_DSC => 'The client should move to another location.',
        self::KEY_BDY => true
      ),
      '308' => array(
        self::KEY_COD => 308,
        self::KEY_MSG => 'Permanent Redirect',
        self::KEY_DSC => 'The client shoud move once to another location.',
        self::KEY_BDY => true
      ),

      // #CLIENT ERRORS
      '400' => array(
        self::KEY_COD => 400,
        self::KEY_MSG => 'Bad Request',
        self::KEY_DSC => 'The client request contains errors and server cannot proccess.',
        self::KEY_BDY => true
      ),
      '401' => array(
        self::KEY_COD => 401,
        self::KEY_MSG => 'Unauthorized',
        self::KEY_DSC => 'The resource is only available for authenticated client.',
        self::KEY_BDY => true
      ),
      '402' => array(
        self::KEY_COD => 402,
        self::KEY_MSG => 'Payment Required',
        self::KEY_DSC => 'The resource is only available when client payment is received.',
        self::KEY_BDY => true
      ),
      '403' => array(
        self::KEY_COD => 403,
        self::KEY_MSG => 'Forbidden',
        self::KEY_DSC => 'The client has not permission to access this resource.',
        self::KEY_BDY => true
      ),
      '404' => array(
        self::KEY_COD => 404,
        self::KEY_MSG => 'Not Found',
        self::KEY_DSC => 'The resource is not found.',
        self::KEY_BDY => true
      ),
      '405' => array(
        self::KEY_COD => 405,
        self::KEY_MSG => 'Method Not Allowed',
        self::KEY_DSC => 'The request method is not allowed for this resource.',
        self::KEY_BDY => true
      ),
      '406' => array(
        self::KEY_COD => 406,
        self::KEY_MSG => 'Not Acceptable',
        self::KEY_DSC => 'The resource is capable of generating only content not acceptable according to the headers sent in the request.',
        self::KEY_BDY => true
      ),
      '407' => array(
        self::KEY_COD => 407,
        self::KEY_MSG => 'Proxy Authentication Required',
        self::KEY_DSC => 'The client must first authenticate itself with the proxy.',
        self::KEY_BDY => true
      ),
      '408' => array(
        self::KEY_COD => 408,
        self::KEY_MSG => 'Request Timeout',
        self::KEY_DSC => 'Server timed out waiting for the request.',
        self::KEY_BDY => true
      ),
      '409' => array(
        self::KEY_COD => 409,
        self::KEY_MSG => 'Conflict',
        self::KEY_DSC => 'The request could not be processed because of a conflict. Please try again.',
        self::KEY_BDY => true
      ),
      '410' => array(
        self::KEY_COD => 410,
        self::KEY_MSG => 'Gone',
        self::KEY_DSC => 'The resource requested is no longer available and will not be available again.',
        self::KEY_BDY => true
      ),
      '411' => array(
        self::KEY_COD => 411,
        self::KEY_MSG => 'Length Required',
        self::KEY_DSC => 'The request did not specify the length of its content, which is required by the requested resource.',
        self::KEY_BDY => true
      ),
      '412' => array(
        self::KEY_COD => 412,
        self::KEY_MSG => 'Precondition Failed',
        self::KEY_DSC => 'The server does not meet one or more of request header fields preconditions.',
        self::KEY_BDY => true
      ),
      '413' => array(
        self::KEY_COD => 413,
        self::KEY_MSG => 'Payload Too Large',
        self::KEY_DSC => 'The request is larger than the server is willing or able to process.',
        self::KEY_BDY => true
      ),
      '414' => array(
        self::KEY_COD => 414,
        self::KEY_MSG => 'URI Too Long',
        self::KEY_DSC => 'The URI provided was too long for the server to process.',
        self::KEY_BDY => true
      ),
      '415' => array(
        self::KEY_COD => 415,
        self::KEY_MSG => 'Unsupported Media Type',
        self::KEY_DSC => 'The resource does not support a media type expected by the client.',
        self::KEY_BDY => true
      ),
      '416' => array(
        self::KEY_COD => 416,
        self::KEY_MSG => 'Range Not Satisfiable',
        self::KEY_DSC => 'The client has asked for a portion of the file (byte serving), but the server cannot supply that portion.',
        self::KEY_BDY => true
      ),
      '417' => array(
        self::KEY_COD => 417,
        self::KEY_MSG => 'Expectation Failed',
        self::KEY_DSC => 'The server cannot meet the requirements of the Expect request-header field.',
        self::KEY_BDY => true
      ),
      '418' => array(
        self::KEY_COD => 418,
        self::KEY_MSG => 'I\'m a teapot',
        self::KEY_DSC => 'I\'m a teapot',
        self::KEY_BDY => true
      ),
      '421' => array(
        self::KEY_COD => 421,
        self::KEY_MSG => 'Misdirected Request',
        self::KEY_DSC => 'The request was directed at a server that is not able to produce a response. Please try again.',
        self::KEY_BDY => true
      ),
      '422' => array(
        self::KEY_COD => 422,
        self::KEY_MSG => 'Unprocessable Entity',
        self::KEY_DSC => 'The request was well-formed but was unable to be followed due to semantic errors.',
        self::KEY_BDY => true
      ),
      '423' => array(
        self::KEY_COD => 423,
        self::KEY_MSG => 'Locked',
        self::KEY_DSC => 'The resource that is being accessed is locked.',
        self::KEY_BDY => true
      ),
      '424' => array(
        self::KEY_COD => 424,
        self::KEY_MSG => 'Failed Dependency',
        self::KEY_DSC => 'The request failed because it depended on another request and that request failed.',
        self::KEY_BDY => true
      ),
      '425' => array(
        self::KEY_COD => 425,
        self::KEY_MSG => 'Unordered Collection',
        self::KEY_DSC => 'Server is unwilling to risk processing a request that might be replayed.',
        self::KEY_BDY => true
      ),
      '426' => array(
        self::KEY_COD => 426,
        self::KEY_MSG => 'Upgrade Required',
        self::KEY_DSC => 'The client should switch to a different protocol.',
        self::KEY_BDY => true
      ),
      '428' => array(
        self::KEY_COD => 428,
        self::KEY_MSG => 'Precondition Required',
        self::KEY_DSC => 'Server requires the request to be conditional.',
        self::KEY_BDY => true
      ),
      '429' => array(
        self::KEY_COD => 429,
        self::KEY_MSG => 'Too Many Requests',
        self::KEY_DSC => 'The user has sent too many requests in a given amount of time.',
        self::KEY_BDY => true
      ),
      '431' => array(
        self::KEY_COD => 431,
        self::KEY_MSG => 'Request Header Fields Too Large',
        self::KEY_DSC => 'Server is unwilling to process the request because request header is too large.',
        self::KEY_BDY => true
      ),
      '451' => array(
        self::KEY_COD => 451,
        self::KEY_MSG => 'Unavailable For Legal Reasons',
        self::KEY_DSC => 'The resource is unavailable for legal reasons',
        self::KEY_BDY => true
      ),

      // #SERVER ERRORS
      '500' => array(
        self::KEY_COD => 500,
        self::KEY_MSG => 'Internal Server Error',
        self::KEY_DSC => 'Server triggered errors and is not able to process them.',
        self::KEY_BDY => true
      ),
      '501' => array(
        self::KEY_COD => 501,
        self::KEY_MSG => 'Not Implemented',
        self::KEY_DSC => 'Server is not able to fulfil the request yet.',
        self::KEY_BDY => true
      ),
      '502' => array(
        self::KEY_COD => 502,
        self::KEY_MSG => 'Bad Gateway',
        self::KEY_DSC => 'This server as a proxy or gateway triggered errors and is not able to process them.',
        self::KEY_BDY => true
      ),
      '503' => array(
        self::KEY_COD => 503,
        self::KEY_MSG => 'Service Unavailable',
        self::KEY_DSC => 'Server can not handler the request. Please try again.',
        self::KEY_BDY => true
      ),
      '504' => array(
        self::KEY_COD => 504,
        self::KEY_MSG => 'Gateway Timeout',
        self::KEY_DSC => 'This server as a proxy or gateway did not received response at time.',
        self::KEY_BDY => true
      ),
      '505' => array(
        self::KEY_COD => 505,
        self::KEY_MSG => 'HTTP Version Not Supported',
        self::KEY_DSC => 'Server does not support the HTTP protocol version used in the request.',
        self::KEY_BDY => true
      ),
      '506' => array(
        self::KEY_COD => 506,
        self::KEY_MSG => 'Variant Also Negotiates',
        self::KEY_DSC => 'The request is redirected in a circular reference.',
        self::KEY_BDY => true
      ),
      '507' => array(
        self::KEY_COD => 507,
        self::KEY_MSG => 'Insufficient Storage',
        self::KEY_DSC => 'Server is unable to store the representation needed to complete the request.',
        self::KEY_BDY => true
      ),
      '508' => array(
        self::KEY_COD => 508,
        self::KEY_MSG => 'Loop Detected',
        self::KEY_DSC => 'Server detected an infinite loop while processing the request.',
        self::KEY_BDY => true
      ),
      '509' => array(
        self::KEY_COD => 509,
        self::KEY_MSG => 'Bandwidth Limit Exceeded',
        self::KEY_DSC => 'The server has exceeded the bandwidth specified by the server administrator.',
        self::KEY_BDY => true
      ),
      '510' => array(
        self::KEY_COD => 510,
        self::KEY_MSG => 'Not Extended',
        self::KEY_DSC => 'Further extensions to the request are required for the server to fulfil it.',
        self::KEY_BDY => true
      ),
      '511' => array(
        self::KEY_COD => 511,
        self::KEY_MSG => 'Network Authentication Required',
        self::KEY_DSC => 'The client needs to authenticate to gain network access.',
        self::KEY_BDY => true
      ),
    ];

    const EOL = "\r\n";


    /**
     * @static createServer
     * @param Closure $fn
     * @return HttpServer\Server
     */

    static function createServer (\Closure $fn) {
      return new Server($fn);
    }


    /**
     * @static toField
     * @param string $field
     * @return string
     */

    static function toField (string $field) {
      $result = "";
      $first = true;
      $len = \strlen($field);
      for ($i = 0; $i < $len; $i++) {
        $lo = \strtolower($field[$i]);
        $up = \strtoupper($field[$i]);
        if ($lo === $up) {
          if ($first) continue;
          else {
            $first = true;
            $result .= "-";
          }
        } else {
          $result .= $first ? $up : $lo;
          $first = false;
        }
      }
      return $result;
    }
  }
}
