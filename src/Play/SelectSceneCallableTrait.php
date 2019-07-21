<?php

namespace Play;

trait SelectSceneCallableTrait
{
    public $selector;
    public $args;
    
    function asCallable($selector = null, ...$args)
    {
        $this->selector = $selector;
        $this->args = $args;
        return $this;
    }

    function __invoke(...$args)
    {        
        return $this->perform(...$args);
    }

    function perform(...$args)
    {
        $callable = $this->selector ? [$this, $this->selector] : $this;
        return $callable(...$args);
    }
}
