<?php

namespace Persistence;

class NoData extends \Exception {

    function __construct($id, $code = 204, Exception $previous = null) {

        parent::__construct("No data", $code, $previous);
    }

    function reportOn($array) {
        $array['message'] = $this->getMessage();
        return $array;
    }

    function traceOn($array) {
        $array['trace'] = $this->getTrace()[0];
        return $array;
    }

}
