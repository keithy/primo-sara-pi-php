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

        function setIO($request, $response)
        {
            $this->request = $request;
            $this->response = $response;
            return $this;
        }

        function respondNotFound($info)
        {
            $this->controller->statusNotFound();
            return $this->respondErrors("Not Found", $info);
        }

        function respondInvalidParameter($info)
        {
            $this->controller->statusInvalidParameter();
            $this->controller->errorInfo($info);

            return $this->respondErrors("Invalid or Missing Parameter", $info);
        }

        function respondNoData($info)
        {
            $this->controller->statusNoData();

            $this->controller->errorInfo($info);

            return $this->respondErrors("No Data", $info);
        }

        function respondErrors($message, $info = [])
        {
            $errors = [];
            $info['message'] = $message;

            $this->controller->errorInfo($info);

            $errors['errors'] = $info;

            return $this->controller->respond($errors);
        }

        function respond($data)
        {
            return $this->controller->respond($data);
        }

        function setResponseStatus($code)
        {
            $this->response = $this->response->withStatus($code);
        }

        function getResponseStatus()
        {
            return $this->response->getStatusCode();
        }

        function reply($data)
        {
            return $this->response
                            ->write(json_encode($data))
                            ->withHeader('Content-Type', 'application/json');
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
            return ["request_attributes" => $this->request->getAttributes(),
                "request_params" => $this->request->getParams(),
                "query_params" => $this->request->getQueryParams()
            ];
        }

        function outputEcho()
        {
            return [];
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
        protected $ioMessage;

        function outputEcho() // for debugging/echo
        {
            return $this->context->outputEcho();
        }

        function respond($data)
        {
            switch (true) {
                case (is_array($data));
                    $reply = $data;
                    $reply['success'] ?? $reply['success'] = true;
                case (true === $data);
                    $reply = ["success" => true];
                    break;
                case (false === $data);
                    $reply = ["success" => false];
                    break;
            }
            if ($this->ioMessage) {
                $reply['message'] = $this->ioMessage;
            }

            $reply['status'] = $this->context->getResponseStatus();

            return $this->context->reply($reply);
        }

        function setStatus($code)
        {
            $this->context->setResponseStatus($code);
            return $this;
        }

        function statusOK(...$args) // the slim default - so probably not needed
        {
            if (!empty($args)) $this->ioMessage = sprintf(...$args);
            $this->context->setResponseStatus(200);
        }

        function statusNoData(...$args)
        {
            if (!empty($args)) $this->ioMessage = sprintf(...$args);
            $this->context->setResponseStatus(204);
        }

        function statusInvalidParameter(...$args)
        {
            if (!empty($args)) $this->ioMessage = sprintf(...$args);
            $this->context->setResponseStatus(400);
        }

        function statusNotFound(...$args)
        {
            if (!empty($args)) $this->ioMessage = sprintf(...$args);
            $this->context->setResponseStatus(404);
        }

        function statusInvalidFile(...$args)
        {
            if (!empty($args)) $this->ioMessage = sprintf(...$args);
            $this->context->setResponseStatus(404);
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
                $this->statusInvalidFile();
                $this->context->respondErrors($ex->getMessage());
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
                $this->statusInvalidFile();
                $this->context->respondErrors($ex->getMessage());
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
                $this->statusInvalidFile();
                $this->context->respondErrors($ex->getMessage());
            }
        }
    }

}