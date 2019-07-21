<?php

namespace Play;

trait PerformOnStageTrait
{
    // app "c-cole"

    protected $director;
    protected $audience;

    function toArray()
    {
        return json_decode(json_encode($this), true);
    }

    function whenPerforming($director, $audience = null)
    {
        $this->director = $director;
        $this->audience = $audience ?? $director;
    }

    // director

    function paramAt($key, $default = null)
    {
        return ($this->director->getParams())[$key] ?? $default;
    }

    function queryAt($key, $default = null)
    {
        return $this->director->getQueryParam($key, $default);
    }

    function configAt($key, $default = null)
    {
        return $this->director->configAt($key, $default);
    }

    function sessionId()
    {
        return $this->director->sessionId();
    }

    // audience

    function respond($arrayOrFn)
    {
        return $this->audience->respond($arrayOrFn);
    }
    
    function respondNoData($reason = null)
    {
        return $this->audience->statusNoData($reason);
    }

    function respondError($reason = 'Error Not Specified')
    {
        return $this->audience->statusError($reason)->respond(false);
    }
    
    function respondSuccess($reason = 'Success')
    {
        // responding with an empty array - respond formats the $reason into the response
        // according to the policy defined by the chosen ioContext
        return $this->audience->statusSuccess($reason)->respond(true);
    }
    
    function statusInvalidParameter($reason = null, $report = null)
    {
        return $this->audience->statusInvalidParameter($reason, $report);
    }

    function ping()
    {
        return $this->audience->respond(true);
    }

    function echo(...$routeArgs)
    {
        $routeArgs = implode(', ', $routeArgs);
        $payload = null;

        $this->audience->respond(
                function() use ($routeArgs, &$payload) {
            return json_encode($payload = [
                "action" => "{$this->selector}({$routeArgs})",
                "stage" => json_encode($this),
                "director" => $this->director->stateInfo(),
                "input" => $this->director->inputEcho(),
                "output" => $this->director->outputEcho()
            ]);
        });

        return $payload;
    }
}
