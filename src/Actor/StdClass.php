<?php

namespace Actor;

class StdClass implements \DCI\RolePlayerInterface {
   
    use \DCI\RolePlayer;

    function toArray()
    {
        return json_decode(json_encode($this), true);
    }
}
