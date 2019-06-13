<?php

// The Roles define framework independent mapping onto the HTTP protocol
// The IO Context defines the interface onto the framework, e.g. 
// e.g. PSR7 request/response or stdin/stdout/stderr or Redis

namespace IO {

    class PSR7_Json extends \DCI\Context
    {
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

    trait InputFrom_PSR7
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

    trait OutputTo_PSR7_JSON
    {

        function outputEcho() // for debugging/echo
        {
            return $this->context->outputEcho();
        }

        function respond($data)
        {
            switch (true) {
                case (is_array($data));
                    $reply = $data;
                    $reply['success'] = true;
                case (true === $data);
                    $reply = ["success" => true];
                    break;
                case (false === $data);
                    $reply = ["success" => false];
                    break;
            }
            if ($this->message) {
                $reply['message'] = $this->message;
            }
            return $this->context->reply($reply);
        }

        function statusOK($message = null) // the slim default - so probably not needed
        {
            $this->withMessage($message);
            $this->context->setResponseStatus(200);
        }

        function statusNoData($message = null)
        {
            $this->withMessage($message);
            $this->context->setResponseStatus(204);
        }

        function statusInvalidParameter($message = null)
        {
            $this->withMessage($message);
            $this->context->setResponseStatus(400);
        }

        function statusNotFound($message = null)
        {
            $this->withMessage($message);
            $this->context->setResponseStatus(404);
        }

        function statusInvalidFile($message = null)
        {
            $this->withMessage($message);
            $this->context->setResponseStatus(404);
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