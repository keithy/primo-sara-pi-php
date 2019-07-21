<?php

// The IO Context defines the interface onto a framework.
// 
// Potential options:
// a) PSR7 request/response (Slim3/4)
// b) PHP $_REQUEST (no framework)
// c) stdin/stdout/stderr
// d) Redis/Memecached shared memory
// e) Hadera Hashgraph protocol with trust built in.
//
// Roles define framework independent mapping onto a protocol
// Roles also define protocol policies
// e.g. Whether NoData returns 204, 404 or an empty array.
// IO/PSR7_JSON - requests/responses conform to PSR7, and data returned is typically encoded in JSON.
// 
// The ioContext (on creation) informs the Controller that it is "entering" the context.
// this allows the Controller to request protocol roles from the ioContext.
// 
// The default is to request an "Input_Basic" and "Output_Basic" role, saying in effect
// equip me to perform IO functions "within your context", according to a Basic protocol definition.
// 
// The Basic Output Protocol definition is: 
// a) Data => as returned, no-data => 204.  
// b) errors reported as 'errors' => [ array of multiple errors ]

namespace Stage {

    class ContextHTTP extends \DCI\Context
    {
        const codes = [
            'ok' => 200,
            'created' => 201,
            'accepted' => 202,
            'no content' => 204,
            'reset content' => 205,
            'moved permanently' => 301,
            'found' => 302,
            'see other' => 303,
            'not modified' => 304,
            'temporary redirect' => 307,
            'permanent redirect' => 308,
            'bad request' => 400,
            'unauthorized' => 401,
            'payment required' => 402,
            'forbidden' => 403,
            'not found' => 404,
            'method not allowed' => 405,
            'not acceptable' => 406,
            'request timeout' => 408,
            'conflict' => 409,
            'gone' => 410,
            'precondition failed' => 412,
            'payload too large' => 413,
            'unsupported media type' => 415,
            "i'm a teapot" => 418,
            'page expired' => 419,
            'enhance your calm' => 420,
            'unprocessable entity' => 422,
            'locked' => 423,
            'upgrade required' => 426,
            'too many requests' => 429,
            'call the lawyers' => 451,
            'invalid token' => 498,
            'token required' => 499,
            'internal server error' => 500,
            'error' => 500,
            'not implemented' => 501,
            'bad gateway' => 502,
            'service unavailable' => 503,
            'gateway timeout' => 504
        ];

        public $director;
        public $request;
        public $response;

        function __construct($director, $actor = null)
        {
            $this->empowerAsDirector( $director );
            $this->empowerAsActor( $actor ?? $director );
        }

        function empowerAsDirector( $player )
        {
              $this->director = $player->addRole('Request_Input', $this);
        }
        
        function empowerAsActor( $player )
        {
              $player->addRole('Response_Speak', $this);
        }

        function empowerAsAudience( $player )
        {
              $player->addRole('Response_Listen', $this);
        }
        
        function setIO($request, $response)
        {
            $this->request = $request;
            $this->response = $response;
            return $this;
        }
    }

}