<?php

namespace Actors;

class Model implements \DCI\RolePlayerInterface
{
    const plural = 'models';
    const singular = 'model';

    use \DCI\RolePlayer;
 
    function myClass()
    {
        return get_class($this->getDataObject());
    }

    function modelClass()
    {
        return static::class;
    }
 
}
