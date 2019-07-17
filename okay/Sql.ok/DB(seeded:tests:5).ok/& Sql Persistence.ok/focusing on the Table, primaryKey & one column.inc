<?php

namespace fixture {

    global $PERSISTENCE;
    
    require_once __DIR__ . "/TestClass.php";

    $db = $PERSISTENCE->withTableEtc('ok\TestClass', 'tests', 'id', 'name');
    
}

namespace ok {

    EXPECT("selectAll()");

    $db->modelClass = 'Actor\StdClass';
    
    _('to retrieve 5 items into instances of Actor\StdClass');

    $set = $db->selectAll();

    assert(5 == count($set), count($set));

    assert('Actor\StdClass' == get_class($set[0]));

    _('and have only 2 properties {id, name}');

    assert(2 == count(array_keys($set[0]->toArray())), count(array_keys($set[0]->toArray())));
}

 