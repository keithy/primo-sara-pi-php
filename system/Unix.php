<?php

// The System Context defines the interface onto the deployment OS.
// 
// Potential options:
// a) Unix
// b) Something with a remote file system?

namespace System {

    class Unix extends \DCI\Context
    {
        function __construct($controller)
        {
            $controller->context_system($this);
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

namespace System\Unix\Roles {

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
            $path = a_path($path);
            $data = @yaml_parse_file($path);
            if ($data === false) throw new \Exception("Not readable at: {$path}", 500);
            //$this->context->respondErrors(404, "Not readable (yml) at: {$path}");

            return $data;
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
            //try {
            return require($path);
            //} catch (\Exception $ex) {
            //    $this->context->respondErrors(404, $ex->getMessage());
            //}
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
            //try {
            return require($path);
            //} catch (\Exception $ex) {
            //    $this->context->respondErrors(404, $ex->getMessage());
            //}
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
            //try {
            return json5_decode(file_get_contents($path));
            // json5_decode raises Exceptions on parsing problems
            //} catch (\SyntaxError $ex) {
            //   $this->context->respondErrors(404, $ex->getMessage());
            //}
        }
    }

}