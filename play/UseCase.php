<?php

namespace Play {

    class UseCase extends \DCI\Context
    {
        use SelectSceneCallableTrait;
        use PerformOnStageTrait;
        //
        const baseModelClass = '\Stage\Actors\Model';

//        // API "c-role" Interface Discovery in API I/O device
//        static function canRespondForApi($request, $controller)
//        {
//            return true;
//        }
//
//        function modelClass()
//        {
//            return static::model;
//        }
//
//        function paramAt($key, $default = null)
//        {
//            return ($this->getParams())[$key] ?? $default;
//        }
//
//        function getParams()
//        {
//            //cached
//            return $this->params = $this->params ?? $this->request->getParams();
//        }
//
//        function getFullDefinition()
//        {
//            return $this->controller->fullDefinition;
//        }
//
//        function getExtraSpecArgs()
//        {
//            return $this->responder->specArgs;
//        }
    }

}

namespace Play\UseCase\Roles {

    trait NoRole
    {
        
    }

    trait Model
    {
        
    }

    trait Persisting
    {
        
    }

    trait Displaying
    {
        
    }

}

    