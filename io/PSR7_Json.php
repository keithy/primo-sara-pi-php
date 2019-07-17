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

        function getQueryParam($key, $default)
        {
            return ($this->request->getQueryParams()[$key]) ?? $default;
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

        function getBody()
        {
            return $this->request->getBody();
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

        function getQueryParam($key, $default)
        {
            return $this->context->getQueryParam($key, $default);
        }

        function getQueryParams()
        {
            return $this->context->getQueryParams();
        }

        function getParams()
        {
            return $this->context->getPostedParams();
        }

        function paramAt($key, $default = null)
        {
            return ($this->context->getPostedParams())[$key] ?? $default;
        }

        function getDataAsArray()
        {
            return json_decode($this->context->getBody(), true);
        }

        function getDataAsObject()
        {
            return json_decode($this->context->getBody());
        }

        function getDataAsObjectClass($className)
        {   // total hack - for a simple become
            return unserialize(sprintf(
                            'O:%d:"%s"%s',
                            \strlen($className),
                            $className,
                            strstr(strstr(serialize(json_decode($this->context->getBody())), '"'), ':')
            ));
        }

        function getForm()
        {
            return json_decode($this->context->getBody(), true);
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
                // delayed reply & closure supplies encoding
                case ($reply instanceof \Closure);
                    return $this->context->reply($reply, $this->contentType);
                    break;
                case (true === $reply);
                // true and false are direct return values
                case (false === $reply);
                // true and false are direct return values
                case (is_array($reply));
                    if (empty($this->errors)) {
                        // $reply['success'] ?? $reply['success'] = true;
                    } else {
                        $reply['status'] = $this->errors[0]['status'];
                        $reply['errors'] = $this->errors;
                        $reply = $this->errorInfo($reply);
                    }
                    break;
            }

            // delayed reply & supply encoding
            return $this->context->reply(function() use ( $reply ) {
                        return json_encode($reply);
                    }, $this->contentType);
        }

        function setStatus($code, $reason = '', $report = [])
        {
            if (is_string($code)) {
                if (!isset(\IO\PSR7_Json::codes[$code]))
                        throw new \Exception("Error mnemonic not found '$code'");
                $code = \IO\PSR7_Json::codes[$code];
            }

            if (empty($this->errors)) $this->context->setResponseStatus($code, $reason);

            if ($reason) {
                $this->errors[] = array_merge(['status' => $code, 'message' => $reason], $report);
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

        function statusNoData($reason = null, $report = [])
        {
            $this->setStatus(204, $reason, $report);
        }

        function statusInvalidParameter($reason = null, $report = [])
        {
            $this->setStatus(400, $reason, $report);
        }

        function statusNotFound($reason = null, $report = [])
        {
            $this->setStatus(404, $reason, $report);
        }

        function statusInvalidFile($reason = null, $report = [])
        {
            $this->setStatus(404, $reason, $report);
        }

        function isStatusOK($expected = 200)
        {
            return ($expected === $this->context->getResponseStatus());
        }
    }

}