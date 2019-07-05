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
// a) Data => array, no-data => 204.  
// b) errors reported as 'errors' => [ array of multiple errors ]

namespace IO {

    class PSR7_Json extends ContextHTTP
    {                       
                
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

    trait Input_Basic
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

    trait Output_Basic
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
}