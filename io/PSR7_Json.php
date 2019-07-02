<?php

// The Roles define framework independent mapping onto the HTTP protocol
// The IO Context defines the interface onto the framework, e.g. 
// e.g. PSR7 request/response or stdin/stdout/stderr or Redis

namespace IO {

    class PSR7_Json extends \DCI\Context
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

        public $controller;
        public $request;
        public $response;
        public $route;

        // $model
        // 
        // A) an instance representing a data object (to be inserted or updated to the database)
        // or
        // B) a partial instance with the defining characteristics needed to retrieve the full object
        //    from the database
        // $controller
        // 
        // Our connection to the big wide world, who is asking 
        // Construct
        function __construct($controller)
        {
            $this->state = $controller->state;
            $this->controller = $controller->entering($this);
        }

        // route should be an attribute of request
        function setIO($request, $response)
        {
            $this->request = $request;
            $this->response = $response;
            return $this;
        }

//
//        function respondNotFound($info)
//        {
//            $this->controller->statusNotFound();
//            return $this->respondErrors("Not Found", $info);
//        }
//
//        function respondInvalidParameter($info)
//        {
//            $this->controller->statusInvalidParameter();
//            $this->controller->errorInfo($info);
//
//            return $this->respondErrors("Invalid or Missing Parameter", $info);
//        }
//
//        function respondNoData($info)
//        {
//            $this->controller->statusNoData();
//
//            $this->controller->errorInfo($info);
//
//            return $this->respondErrors("No Data", $info);
//        }
//
        function respondErrors($code, $message)
        {
            $this->setResponseStatus($code, $message);

            $info['message'] = $message;
            $info['status'] = $code;

            $this->reply(function() use( $info ) {
                return json_encode(['errors' => $this->controller->errorInfo($info)]);
            });
        }

//
//        function respond($data)
//        {
//            return $this->controller->respond($data);
//        }

        function setResponseStatus($code, $reason)
        {
            $this->response = $this->response->withStatus($code, $reason);
        }

        function getResponseStatus()
        {
            return $this->response->getStatusCode();
        }

        function reply(\Closure $fn, $type = null)
        {
            $type ?? $type = $this->controller->contentType();

            $this->response = $this->response->withHeader('Content-Type', $type);

            $this->response = $this->response->write($fn());

            return $this->response;
        }

        function getServerRequestHeaderAt($key)
        {
            return $this->request->getHeaderLine($key) ?: null;
        }

        function getResourceQueryParam($key)
        {
            return $this->request->getQueryParam($key, null);
        }

        function getPostedParams()
        {
            return $this->request->getParams();
        }

        function inputEcho()
        {
            return [
                //"request_attributes" => $this->request->getAttributes(),
                "route" => $this->request->getAttribute('route'),
                "type" => $this->request->getHeader('Content-Type')[0],
                "query_params" => json_encode($this->request->getQueryParams()),
                "form_params" => json_encode($this->request->getParsedBody())
            ];
        }

        function outputEcho()
        {
            return [
                "type" => $this->response->getHeader('Content-Type')[0],
            ];
        }
    }

}

/**
 * Roles are defined in a sub-namespace of the context as a workaround for the fact that
 * PHP doesn't support inner classes.
 *
 * We use the trait keyword to define roles because the trait keyword is native to PHP (PHP 5.4+).
 * In an ideal world it would be better to have a "role" keyword -- think of "trait" as just
 * our implementation technique for roles in PHP.
 * (This particular implmentation for PHP actually uses a separate class for the role behind the scenes,
 * but that programmer needn't be aware of that.)
 */

namespace IO\PSR7_Json\Roles {

    trait Input
    {

        function inputEcho() // for debugging/echo
        {
            return $this->context->inputEcho();
        }

        function getContext() // for debugging/echo
        {
            return $this->context;
        }

        function getServerRequestHeaderAt($key)
        {
            return $this->context->getServerRequestHeaderAt($key);
        }

        function getResourceQueryParam($key)
        {
            return $this->context->getServerRequestHeaderAt($key);
        }

        function getParams()
        {
            return $this->context->getPostedParams();
        }
    }

    trait Output
    {
        protected $errors = [];
        protected $contentType;

        function outputEcho() // for debugging/echo
        {
            return $this->context->outputEcho();
        }

        function respond($reply = false)
        {
            switch (true) {
                case ($reply instanceof \Closure);
                    return $this->context->reply($reply, $this->contentType);
                    break;
                case (true === $reply);
                    $reply = ["success" => true];
                case (false === $reply);
                    $reply = ["success" => false];
                case (is_array($reply));
                    if (empty($this->errors)) {
                        // $reply['success'] ?? $reply['success'] = true;
                    } else {
                        $reply['errors'] = $this->errors;
                        $reply = $this->errorInfo($reply);
                    }
                    break;
            }

            // if there is a status field we could set it at this point
            // if (isset($reply['status'])) $reply['status'] = $this->context->getResponseStatus();

            return $this->context->reply(function() use ( $reply ) {
                        return json_encode($reply);
                    }, $this->contentType);
        }

        function setStatus($code, $reason = '')
        {
            if (is_string($code)) {
                if (!isset(\IO\PSR7_Json::codes[$code]))
                        throw new \Exception("Error mnemonic not found '$code'");
                $code = \IO\PSR7_Json::codes[$code];
            }

            if (empty($this->errors)) $this->context->setResponseStatus($code, $reason);

            if ($reason) {
                $this->errors[] = ['status' => $code, 'message' => $reason];
            }
        }

        function setContentType($type)
        {
            $this->contentType = $type;
        }

        function statusOK($reason = null) // the slim default - so probably not needed
        {
            $this->context->setResponseStatus(200, $reason);
        }

        function statusNoData($reason = null)
        {
            $this->setStatus(204, $reason);
        }

        function statusInvalidParameter($reason = null)
        {
            $this->setStatus(400, $reason);
        }

        function statusNotFound($reason = null)
        {
            $this->setStatus(404, $reason);
        }

        function statusInvalidFile($reason = null)
        {
            $this->setStatus(404, $reason);
        }

        function isStatusOK($expected = 200)
        {
            return ($expected === $this->context->getResponseStatus());
        }
    }

    trait ReadFile_json
    {

        function readFormat()
        {
            return 'json';
        }

        function readFor($path)
        {
            return json_decode(file_get_contents($path), true);
        }
    }

    trait ReadFile_yml
    {

        function readFormat()
        {
            return 'yml';
        }

        function readFor($path)
        {

            return yaml_parse_file(a_path($path));
        }
    }

    trait ReadFile_php
    {

        function readFormat()
        {
            return "php";
        }

        function readFor($path)
        {
            try {
                return require($path);
            } catch (\Exception $ex) {
                $this->context->respondErrors(404, $ex->getMessage());
            }
        }
    }

    trait ReadFile_inc
    {

        function readFormat()
        {
            return "php";
        }

        function readFor($path)
        {
            try {
                return require($path);
            } catch (\Exception $ex) {
                $this->context->respondErrors(404, $ex->getMessage());
            }
        }
    }

    trait ReadFile_json5
    {

        function readFormat()
        {
            return "json5";
        }

        function readFor($path)
        {
            try {
                return json5_decode(file_get_contents($path));
                // json5_decode raises Exceptions on parsing problems
            } catch (\SyntaxError $ex) {

                $this->context->respondErrors(404, $ex->getMessage());
            }
        }
    }

}