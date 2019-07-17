<?php

namespace fixture {

    global $PERSISTENCE;
    
    require_once __DIR__ . "/TestClass.php";

    $db = $PERSISTENCE->withTableEtc('ok\TestClass', 'tests', 'id');
    
}

namespace ok {

    EXPECT("selectAll()");

    $db->modelClass = 'Actor\StdClass';
    
    _('to retrieve 5 items into instances of Actor\StdClass');

    $set = $db->selectAll();

    assert(5 == count($set), count($set));

    assert('Actor\StdClass' == get_class($set[0]));

    _('and have 3 properties');

    assert(3 == count(array_keys($set[0]->toArray())), count(array_keys($set[0]->toArray())));
}

namespace ok {
 
    $db->modelClass = 'ok\TestClass';

    EXPECT("selectAll()");

    _('to retrieve 5 items into instances of ok\TestClass');

    $set = $db->selectAll();

    assert(5 == count($set), count($set));

    assert('ok\TestClass' == get_class($set[0]));
}

namespace ok {

    EXPECT("selectAll('name')");

    _('to retrieve 5 items into instances of ok\TestClass');

    $set = $db->selectAll('name', 2, 2);

    assert(2 == count($set), count($set));

    assert('ok\TestClass' == get_class($set[0]));

    _('but only have the one property');

    assert(1 == count(array_keys($set[0]->toArray())), count(array_keys((array) $set[0])));
}
